<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Matakuliah;
use App\Models\Ajaran;
use App\Models\User;
use App\Models\Prodi;
use App\Models\Kelas;
use App\Models\TemplateLaporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class DataMasterController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('gkm.data-master.index', compact('user'));
    }

    public function dosenPengajar()
    {
        $user = Auth::user();
        
        // Filter dosen berdasarkan prodi GKM
        $query = Dosen::with('prodi')->orderBy('nama_lengkap');
        
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }
        
        $dosenList = $query->paginate(10);
        
        // Get daftar kelas berdasarkan prodi
        $kelasList = Dosen::getKelasListByProdi($user->prodi_id);
        
        return view('gkm.data-master.dosen', compact('user', 'dosenList', 'kelasList'));
    }

    public function storeDosen(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nidn' => 'required|string',
            'kontak_email' => 'required|email',
            'gelar_akademik' => 'nullable|string|max:100',
            'jabatan_akademik' => 'nullable|string|max:100',
            'status' => 'required|in:aktif,tidak_aktif,pensiun',
            'is_kaprodi' => 'nullable|boolean',
            'is_dosen_wali' => 'nullable|boolean',
            'kelas_wali' => 'nullable|string|max:50',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
            'nidn.required' => 'NIDN wajib diisi',
            'kontak_email.required' => 'Email wajib diisi',
            'kontak_email.email' => 'Format email tidak valid',
            'status.required' => 'Status wajib dipilih',
        ]);

        try {
            DB::beginTransaction();

            // Ambil prodi dari user yang sedang login
            $currentUser = Auth::user();
            $prodiId = $currentUser->prodi_id;

            // Jika user tidak memiliki prodi_id, coba ambil dari kelas_wali yang dipilih
            if (!$prodiId && $request->is_dosen_wali && $request->kelas_wali) {
                $kelas = Kelas::where('kode_kelas', $request->kelas_wali)->first();
                if ($kelas) {
                    $prodiId = $kelas->prodi_id;
                }
            }

            // Jika masih tidak ada prodi_id, return error
            if (!$prodiId) {
                return redirect()->back()
                    ->with('error', 'Tidak dapat menentukan prodi. Silakan pilih kelas wali atau hubungi administrator.')
                    ->withInput();
            }

            // Validasi: Hanya boleh ada 1 Kaprodi per prodi
            if ($request->is_kaprodi) {
                $existingKaprodi = Dosen::where('prodi_id', $prodiId)
                    ->where('is_kaprodi', true)
                    ->first();

                if ($existingKaprodi) {
                    return redirect()->back()
                        ->with('error', 'Sudah ada Kaprodi untuk prodi ini: ' . $existingKaprodi->nama_lengkap)
                        ->withInput();
                }
            }

            // Validasi: Jika dosen wali, kelas_wali harus diisi
            if ($request->is_dosen_wali && empty($request->kelas_wali)) {
                return redirect()->back()
                    ->with('error', 'Kelas wali harus diisi jika dosen ditandai sebagai Dosen Wali')
                    ->withInput();
            }

            // Validasi: Kelas wali tidak boleh duplikat
            if ($request->is_dosen_wali && $request->kelas_wali) {
                $existingWali = Dosen::where('prodi_id', $prodiId)
                    ->where('kelas_wali', $request->kelas_wali)
                    ->first();

                if ($existingWali) {
                    return redirect()->back()
                        ->with('error', 'Kelas ' . $request->kelas_wali . ' sudah memiliki dosen wali: ' . $existingWali->nama_lengkap)
                        ->withInput();
                }
            }

            // Cek apakah dosen dengan NIDN ini sudah ada di prodi yang sama
            $existingDosenSameProdi = Dosen::where('nidn', $request->nidn)
                ->where('prodi_id', $prodiId)
                ->first();

            if ($existingDosenSameProdi) {
                return redirect()->back()
                    ->with('error', 'Dosen dengan NIDN ini sudah terdaftar di prodi Anda')
                    ->withInput();
            }

            // Cek apakah dosen dengan email ini sudah ada di prodi yang sama
            $existingEmailSameProdi = Dosen::where('kontak_email', $request->kontak_email)
                ->where('prodi_id', $prodiId)
                ->first();

            if ($existingEmailSameProdi) {
                return redirect()->back()
                    ->with('error', 'Dosen dengan email ini sudah terdaftar di prodi Anda')
                    ->withInput();
            }

            // Cek apakah dosen sudah ada di prodi lain (berdasarkan NIDN atau email)
            $existingDosenOtherProdi = Dosen::where(function($query) use ($request) {
                    $query->where('nidn', $request->nidn)
                          ->orWhere('kontak_email', $request->kontak_email);
                })
                ->where('prodi_id', '!=', $prodiId)
                ->with('prodi')
                ->first();

            if ($existingDosenOtherProdi) {
                $otherProdiName = $existingDosenOtherProdi->prodi->nama_prodi ?? 'prodi lain';
                return redirect()->back()
                    ->with('error', "Dosen dengan NIDN/Email ini sudah terdaftar di {$otherProdiName}. Saat ini sistem belum mendukung dosen yang mengajar di multiple prodi.")
                    ->withInput();
            }

            // Cek apakah user dengan email ini sudah ada
            $existingUser = User::where('email', $request->kontak_email)->first();

            if ($existingUser) {
                // Jika user sudah ada, gunakan user tersebut
                $userId = $existingUser->id;
                
                // Update prodi_id user jika belum ada
                if (!$existingUser->prodi_id) {
                    $existingUser->update(['prodi_id' => $prodiId]);
                }
            } else {
                // Generate username dari email (bagian sebelum @)
                $username = explode('@', $request->kontak_email)[0];
                
                // Pastikan username unik
                $baseUsername = $username;
                $counter = 1;
                while (User::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                // Buat user baru
                $newUser = User::create([
                    'name' => $request->nama_lengkap,
                    'username' => $username,
                    'email' => $request->kontak_email,
                    'password' => Hash::make('password123'), // Default password
                    'role' => 'dosen',
                    'prodi_id' => $prodiId,
                ]);

                $userId = $newUser->id;
            }

            // Buat data dosen dengan prodi yang sama
            Dosen::create([
                'user_id' => $userId,
                'prodi_id' => $prodiId,
                'nama_lengkap' => $request->nama_lengkap,
                'nidn' => $request->nidn,
                'kontak_email' => $request->kontak_email,
                'gelar_akademik' => $request->gelar_akademik,
                'jabatan_akademik' => $request->jabatan_akademik,
                'status' => $request->status,
                'is_kaprodi' => $request->is_kaprodi ?? false,
                'is_dosen_wali' => $request->is_dosen_wali ?? false,
                'kelas_wali' => $request->is_dosen_wali ? $request->kelas_wali : null,
            ]);

            DB::commit();

            return redirect()->route('gkm.data-master.dosen')
                ->with('success', 'Dosen berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menambahkan dosen: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function updateDosen(Request $request, $id)
    {
        $dosen = Dosen::findOrFail($id);

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nidn' => 'required|string|unique:dosen,nidn,' . $id,
            'kontak_email' => 'required|email|unique:dosen,kontak_email,' . $id,
            'gelar_akademik' => 'nullable|string|max:100',
            'jabatan_akademik' => 'nullable|string|max:100',
            'status' => 'required|in:aktif,tidak_aktif,pensiun',
            'is_kaprodi' => 'nullable|boolean',
            'is_dosen_wali' => 'nullable|boolean',
            'kelas_wali' => 'nullable|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            $currentUser = Auth::user();
            $prodiId = $currentUser->prodi_id;

            // Jika user tidak memiliki prodi_id, gunakan prodi_id dari dosen yang sedang diedit
            if (!$prodiId) {
                $prodiId = $dosen->prodi_id;
            }

            // Jika masih tidak ada prodi_id, return error
            if (!$prodiId) {
                return redirect()->back()
                    ->with('error', 'Tidak dapat menentukan prodi. Silakan hubungi administrator.')
                    ->withInput();
            }

            // Validasi: Hanya boleh ada 1 Kaprodi per prodi
            if ($request->is_kaprodi) {
                $existingKaprodi = Dosen::where('prodi_id', $prodiId)
                    ->where('is_kaprodi', true)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingKaprodi) {
                    return redirect()->back()
                        ->with('error', 'Sudah ada Kaprodi untuk prodi ini: ' . $existingKaprodi->nama_lengkap);
                }
            }

            // Validasi: Jika dosen wali, kelas_wali harus diisi
            if ($request->is_dosen_wali && empty($request->kelas_wali)) {
                return redirect()->back()
                    ->with('error', 'Kelas wali harus diisi jika dosen ditandai sebagai Dosen Wali');
            }

            // Validasi: Kelas wali tidak boleh duplikat
            if ($request->is_dosen_wali && $request->kelas_wali) {
                $existingWali = Dosen::where('prodi_id', $prodiId)
                    ->where('kelas_wali', $request->kelas_wali)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingWali) {
                    return redirect()->back()
                        ->with('error', 'Kelas ' . $request->kelas_wali . ' sudah memiliki dosen wali: ' . $existingWali->nama_lengkap);
                }
            }

            $dosen->update([
                'nama_lengkap' => $request->nama_lengkap,
                'nidn' => $request->nidn,
                'kontak_email' => $request->kontak_email,
                'gelar_akademik' => $request->gelar_akademik,
                'jabatan_akademik' => $request->jabatan_akademik,
                'status' => $request->status,
                'is_kaprodi' => $request->is_kaprodi ?? false,
                'is_dosen_wali' => $request->is_dosen_wali ?? false,
                'kelas_wali' => $request->is_dosen_wali ? $request->kelas_wali : null,
            ]);

            // Update user juga
            if ($dosen->user) {
                $dosen->user->update([
                    'name' => $request->nama_lengkap,
                    'email' => $request->kontak_email,
                ]);
            }

            DB::commit();

            return redirect()->route('gkm.data-master.dosen')
                ->with('success', 'Data dosen berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memperbarui data dosen: ' . $e->getMessage());
        }
    }

    public function destroyDosen($id)
    {
        try {
            $dosen = Dosen::findOrFail($id);
            
            DB::beginTransaction();
            
            // Hapus user terkait
            if ($dosen->user) {
                $dosen->user->delete();
            }
            
            $dosen->delete();
            
            DB::commit();

            return redirect()->route('gkm.data-master.dosen')
                ->with('success', 'Dosen berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menghapus dosen: ' . $e->getMessage());
        }
    }

    public function matakuliah()
    {
        $user = Auth::user();
        
        // Filter matakuliah berdasarkan prodi GKM
        $query = Matakuliah::with(['prodi', 'dosen'])->orderBy('kode_mk');
        
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }
        
        $matakuliahList = $query->paginate(10);
        
        // Filter dosen berdasarkan prodi GKM
        $dosenQuery = Dosen::where('status', 'aktif')->orderBy('nama_lengkap');
        
        if ($user->prodi_id) {
            $dosenQuery->where('prodi_id', $user->prodi_id);
        }
        
        $dosenList = $dosenQuery->get();
        
        return view('gkm.data-master.matakuliah', compact('user', 'matakuliahList', 'dosenList'));
    }

    public function storeMatakuliah(Request $request)
    {
        $request->validate([
            'kode_mk' => 'required|string|max:20',
            'nama_mk' => 'required|string|max:255',
            'dosen_ids' => 'nullable|array',
            'dosen_ids.*' => 'exists:dosen,id',
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'required|integer|min:1|max:8',
            'jenis_mk' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:aktif,tidak_aktif',
        ], [
            'kode_mk.required' => 'Kode mata kuliah wajib diisi',
            'nama_mk.required' => 'Nama mata kuliah wajib diisi',
            'sks.required' => 'SKS wajib diisi',
            'sks.min' => 'SKS minimal 1',
            'sks.max' => 'SKS maksimal 6',
            'semester.required' => 'Semester wajib diisi',
            'semester.min' => 'Semester minimal 1',
            'semester.max' => 'Semester maksimal 8',
            'status.required' => 'Status wajib dipilih',
        ]);

        try {
            DB::beginTransaction();

            // Gunakan prodi_id dari user yang login
            $user = Auth::user();
            $prodiId = $user->prodi_id;

            // Cek apakah kode_mk sudah ada di prodi yang sama
            $existingMK = Matakuliah::where('kode_mk', $request->kode_mk)
                ->where('prodi_id', $prodiId)
                ->first();

            if ($existingMK) {
                return redirect()->back()
                    ->with('error', 'Kode mata kuliah sudah terdaftar di prodi Anda')
                    ->withInput();
            }

            $matakuliah = Matakuliah::create([
                'prodi_id' => $prodiId,
                'kode_mk' => $request->kode_mk,
                'nama_mk' => $request->nama_mk,
                'sks' => $request->sks,
                'semester' => $request->semester,
                'jenis_mk' => $request->jenis_mk,
                'deskripsi' => $request->deskripsi,
                'status' => $request->status,
            ]);

            // Attach dosen ke matakuliah
            if ($request->dosen_ids) {
                $matakuliah->dosen()->attach($request->dosen_ids);
            }

            DB::commit();

            return redirect()->route('gkm.data-master.matakuliah')
                ->with('success', 'Mata kuliah berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal menambahkan mata kuliah: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function updateMatakuliah(Request $request, $id)
    {
        $matakuliah = Matakuliah::findOrFail($id);

        $request->validate([
            'kode_mk' => 'required|string|max:20|unique:matakuliah,kode_mk,' . $id,
            'nama_mk' => 'required|string|max:255',
            'dosen_ids' => 'nullable|array',
            'dosen_ids.*' => 'exists:dosen,id',
            'sks' => 'required|integer|min:1|max:6',
            'semester' => 'required|integer|min:1|max:8',
            'jenis_mk' => 'nullable|string|max:50',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:aktif,tidak_aktif',
        ]);

        try {
            DB::beginTransaction();

            $matakuliah->update([
                'kode_mk' => $request->kode_mk,
                'nama_mk' => $request->nama_mk,
                'sks' => $request->sks,
                'semester' => $request->semester,
                'jenis_mk' => $request->jenis_mk,
                'deskripsi' => $request->deskripsi,
                'status' => $request->status,
            ]);

            // Sync dosen (hapus yang lama, tambah yang baru)
            if ($request->has('dosen_ids')) {
                $matakuliah->dosen()->sync($request->dosen_ids);
            } else {
                $matakuliah->dosen()->detach();
            }

            DB::commit();

            return redirect()->route('gkm.data-master.matakuliah')
                ->with('success', 'Mata kuliah berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal memperbarui mata kuliah: ' . $e->getMessage());
        }
    }

    public function destroyMatakuliah($id)
    {
        try {
            $matakuliah = Matakuliah::findOrFail($id);
            $matakuliah->delete();

            return redirect()->route('gkm.data-master.matakuliah')
                ->with('success', 'Mata kuliah berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus mata kuliah: ' . $e->getMessage());
        }
    }

    public function templateLaporan()
    {
        $user = Auth::user();
        
        // Filter template berdasarkan prodi GKM
        $query = TemplateLaporan::with('prodi')->orderBy('created_at', 'desc');
        
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }
        
        $templateList = $query->paginate(10);
        
        return view('gkm.data-master.template', compact('user', 'templateList'));
    }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'nama_template' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx|max:10240', // max 10MB
            'deskripsi' => 'nullable|string',
        ], [
            'nama_template.required' => 'Nama template wajib diisi',
            'file.required' => 'File wajib diupload',
            'file.mimes' => 'File harus berformat PDF, DOC, DOCX, PPT, PPTX, XLS, atau XLSX',
            'file.max' => 'Ukuran file maksimal 10MB',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileSize = $file->getSize();
            
            // Generate unique filename
            $fileName = time() . '_' . str_replace(' ', '_', $originalName);
            
            // Store file
            $filePath = $file->storeAs('templates', $fileName, 'public');

            // Gunakan prodi_id dari user yang login
            $user = Auth::user();
            $prodiId = $user->prodi_id;

            TemplateLaporan::create([
                'prodi_id' => $prodiId,
                'nama_template' => $request->nama_template,
                'nama_file' => $originalName,
                'jenis_file' => strtoupper($extension),
                'file_path' => $filePath,
                'ukuran_file' => $fileSize,
                'deskripsi' => $request->deskripsi,
                'uploaded_by' => Auth::id(),
            ]);

            return redirect()->route('gkm.data-master.template')
                ->with('success', 'Template berhasil diupload');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengupload template: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function downloadTemplate($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);
            
            if (!Storage::disk('public')->exists($template->file_path)) {
                return redirect()->back()
                    ->with('error', 'File tidak ditemukan');
            }

            return Storage::disk('public')->download($template->file_path, $template->nama_file);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mendownload template: ' . $e->getMessage());
        }
    }

    public function destroyTemplate($id)
    {
        try {
            $template = TemplateLaporan::findOrFail($id);
            
            // Hapus file dari storage
            if (Storage::disk('public')->exists($template->file_path)) {
                Storage::disk('public')->delete($template->file_path);
            }

            $template->delete();

            return redirect()->route('gkm.data-master.template')
                ->with('success', 'Template berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus template: ' . $e->getMessage());
        }
    }

    public function periodeAkademik()
    {
        $user = Auth::user();
        
        // Filter periode berdasarkan prodi GKM
        $query = Ajaran::with('prodi')->orderBy('tahun_ajaran', 'desc')->orderBy('semester', 'desc');
        
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }
        
        $periodeList = $query->paginate(10);
        
        return view('gkm.data-master.periode', compact('user', 'periodeList'));
    }

    public function storePeriode(Request $request)
    {
        $request->validate([
            'tahun_ajaran' => 'required|integer|min:2020|max:2100',
            'semester' => 'required|in:ganjil,genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date|after:tanggal_mulai',
        ], [
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi',
            'semester.required' => 'Semester wajib dipilih',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi',
            'tanggal_akhir.required' => 'Tanggal akhir wajib diisi',
            'tanggal_akhir.after' => 'Tanggal akhir harus setelah tanggal mulai',
        ]);

        try {
            // Gunakan prodi_id dari user yang login
            $user = Auth::user();
            $prodiId = $user->prodi_id;

            // Cek apakah periode dengan tahun dan semester yang sama sudah ada di prodi ini
            $existingPeriode = Ajaran::where('tahun_ajaran', $request->tahun_ajaran)
                ->where('semester', $request->semester)
                ->where('prodi_id', $prodiId)
                ->first();

            if ($existingPeriode) {
                return redirect()->back()
                    ->with('error', 'Periode akademik dengan tahun dan semester yang sama sudah ada di prodi Anda')
                    ->withInput();
            }

            Ajaran::create([
                'prodi_id' => $prodiId,
                'tahun_ajaran' => $request->tahun_ajaran,
                'semester' => $request->semester,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_akhir' => $request->tanggal_akhir,
                'status' => 'non_aktif',
            ]);

            return redirect()->route('gkm.data-master.periode')
                ->with('success', 'Periode akademik berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan periode akademik: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function updatePeriode(Request $request, $id)
    {
        $periode = Ajaran::findOrFail($id);

        $request->validate([
            'tahun_ajaran' => 'required|integer|min:2020|max:2100',
            'semester' => 'required|in:ganjil,genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_akhir' => 'required|date|after:tanggal_mulai',
        ]);

        try {
            $periode->update([
                'tahun_ajaran' => $request->tahun_ajaran,
                'semester' => $request->semester,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_akhir' => $request->tanggal_akhir,
            ]);

            return redirect()->route('gkm.data-master.periode')
                ->with('success', 'Periode akademik berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui periode akademik: ' . $e->getMessage());
        }
    }

    public function activatePeriode($id)
    {
        try {
            DB::beginTransaction();

            $periode = Ajaran::findOrFail($id);
            $user = Auth::user();

            // Nonaktifkan semua periode di prodi yang sama
            Ajaran::where('prodi_id', $user->prodi_id)
                ->update(['status' => 'non_aktif']);

            // Aktifkan periode yang dipilih
            $periode->update(['status' => 'aktif']);

            DB::commit();

            return redirect()->route('gkm.data-master.periode')
                ->with('success', 'Periode akademik berhasil diaktifkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal mengaktifkan periode akademik: ' . $e->getMessage());
        }
    }

    public function destroyPeriode($id)
    {
        try {
            $periode = Ajaran::findOrFail($id);
            
            if ($periode->status == 'aktif') {
                return redirect()->back()
                    ->with('error', 'Tidak dapat menghapus periode yang sedang aktif');
            }

            $periode->delete();

            return redirect()->route('gkm.data-master.periode')
                ->with('success', 'Periode akademik berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus periode akademik: ' . $e->getMessage());
        }
    }

    // ==================== KELAS MANAGEMENT ====================

    public function kelas()
    {
        $user = Auth::user();
        
        $query = Kelas::with('prodi')->orderBy('kode_kelas');
        
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }
        
        $kelasList = $query->paginate(10);
        
        return view('gkm.data-master.kelas', compact('user', 'kelasList'));
    }

    public function storeKelas(Request $request)
    {
        $validated = $request->validate([
            'kode_kelas' => 'required|string|max:50',
            'tingkat' => 'required|integer|min:1|max:4',
            'program_studi' => 'required|in:TRPL,TI,NM',
            'tahun_angkatan' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'status' => 'required|in:aktif,tidak_aktif',
        ]);

        try {
            $currentUser = Auth::user();
            $prodiId = $currentUser->prodi_id;

            // Jika user tidak memiliki prodi_id, ambil dari program_studi yang dipilih
            if (!$prodiId) {
                $prodiMap = [
                    'TRPL' => 1,
                    'TI' => 2,
                    'NM' => 3,
                ];
                $prodiId = $prodiMap[$validated['program_studi']] ?? null;

                if (!$prodiId) {
                    return redirect()->back()
                        ->with('error', 'Program studi tidak valid.')
                        ->withInput();
                }
            }

            // Cek duplikasi kode kelas
            $existing = Kelas::where('prodi_id', $prodiId)
                ->where('kode_kelas', $validated['kode_kelas'])
                ->first();

            if ($existing) {
                return redirect()->back()
                    ->with('error', 'Kode kelas sudah digunakan')
                    ->withInput();
            }

            Kelas::create([
                'prodi_id' => $prodiId,
                'kode_kelas' => $validated['kode_kelas'],
                'tingkat' => $validated['tingkat'],
                'program_studi' => $validated['program_studi'],
                'tahun_angkatan' => $validated['tahun_angkatan'],
                'status' => $validated['status'],
            ]);

            return redirect()->route('gkm.data-master.kelas')
                ->with('success', 'Kelas berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan kelas: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function updateKelas(Request $request, $id)
    {
        $validated = $request->validate([
            'kode_kelas' => 'required|string|max:50',
            'tingkat' => 'required|integer|min:1|max:4',
            'program_studi' => 'required|in:TRPL,TI,NM',
            'tahun_angkatan' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'status' => 'required|in:aktif,tidak_aktif',
        ]);

        try {
            $kelas = Kelas::findOrFail($id);
            $currentUser = Auth::user();
            $prodiId = $currentUser->prodi_id;

            // Jika user tidak memiliki prodi_id, ambil dari program_studi yang dipilih
            if (!$prodiId) {
                $prodiMap = [
                    'TRPL' => 1,
                    'TI' => 2,
                    'NM' => 3,
                ];
                $prodiId = $prodiMap[$validated['program_studi']] ?? null;

                if (!$prodiId) {
                    return redirect()->back()
                        ->with('error', 'Program studi tidak valid.')
                        ->withInput();
                }
            }

            // Cek duplikasi kode kelas (kecuali kelas yang sedang diedit)
            $existing = Kelas::where('prodi_id', $prodiId)
                ->where('kode_kelas', $validated['kode_kelas'])
                ->where('id', '!=', $id)
                ->first();

            if ($existing) {
                return redirect()->back()
                    ->with('error', 'Kode kelas sudah digunakan')
                    ->withInput();
            }

            $kelas->update($validated);

            return redirect()->route('gkm.data-master.kelas')
                ->with('success', 'Kelas berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui kelas: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroyKelas($id)
    {
        try {
            $kelas = Kelas::findOrFail($id);
            
            // Cek apakah kelas sedang digunakan oleh dosen wali
            $dosenWali = Dosen::where('kelas_wali', $kelas->kode_kelas)->count();
            
            if ($dosenWali > 0) {
                return redirect()->back()
                    ->with('error', 'Tidak dapat menghapus kelas yang sedang digunakan oleh dosen wali');
            }

            $kelas->delete();

            return redirect()->route('gkm.data-master.kelas')
                ->with('success', 'Kelas berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus kelas: ' . $e->getMessage());
        }
    }

    public function penugasanDosen(Request $request)
    {
        $user = Auth::user();
        
        // Handle AJAX request for dosen list (autocomplete)
        if ($request->ajax() || $request->has('get_dosen_list')) {
            try {
                $apiService = app(\App\Services\ExternalAPIService::class);
                $dosenData = $apiService->getFilteredDosen();
                
                // Return only nama and email for autocomplete
                $dosenList = array_map(function($dosen) {
                    return [
                        'nama' => $dosen['nama'] ?? '',
                        'email' => $dosen['email'] ?? ''
                    ];
                }, $dosenData);
                
                return response()->json(['dosen' => $dosenList]);
            } catch (\Exception $e) {
                \Log::error('AJAX Dosen List Error: ' . $e->getMessage());
                return response()->json(['dosen' => []]);
            }
        }
        
        // Initialize empty collection
        $dosenList = new \Illuminate\Pagination\LengthAwarePaginator(
            [],
            0,
            10,
            1,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        // Only fetch data if search is provided or form is submitted
        if (!$request->has('search') && !$request->has('submitted')) {
            return view('gkm.data-master.penugasan-dosen', compact('user', 'dosenList'));
        }
        
        try {
            $apiService = app(\App\Services\ExternalAPIService::class);
            
            // Fetch data from API using service
            $dosenData = $apiService->getFilteredDosen();
            
            // Search by name
            if ($request->search) {
                $searchTerm = strtolower($request->search);
                $dosenData = array_filter($dosenData, function($dosen) use ($searchTerm) {
                    return isset($dosen['nama']) && 
                           str_contains(strtolower($dosen['nama']), $searchTerm);
                });
            }
            
            // Convert to collection for pagination
            $dosenCollection = collect(array_values($dosenData));
            
            // PAGINATION FIRST - before fetching jadwal
            $perPage = 10;
            $currentPage = $request->get('page', 1);
            
            // Get only current page items
            $currentPageDosen = $dosenCollection->forPage($currentPage, $perPage)->all();
            
            // Get semester and year from request or use defaults
            $currentMonth = date('n');
            $defaultSemTa = $currentMonth >= 8 ? 1 : 2; // 1 = Ganjil (Aug-Dec), 2 = Genap (Jan-Jul)
            
            $semTa = $request->get('sem_ta', $defaultSemTa);
            $ta = $request->get('ta', 2020); // Default to 2020 as API only has 2020 data
            
            // Fetch jadwal ONLY for current page dosen (not all dosen)
            foreach ($currentPageDosen as &$dosen) {
                $pegawaiId = $dosen['pegawai_id'] ?? null;
                
                if ($pegawaiId) {
                    // Get jadwal from API with cache
                    $jadwal = \Cache::remember(
                        "jadwal_{$pegawaiId}_{$semTa}_{$ta}",
                        3600,
                        function() use ($apiService, $pegawaiId, $semTa, $ta) {
                            return $apiService->getJadwalByDosen($pegawaiId, $semTa, $ta);
                        }
                    );
                    
                    // Remove duplicates based on kode_mk
                    $uniqueJadwal = [];
                    $seenKodeMk = [];
                    
                    foreach ($jadwal as $mk) {
                        $kodeMk = $mk['kode_mk'] ?? null;
                        
                        if ($kodeMk && !in_array($kodeMk, $seenKodeMk)) {
                            $uniqueJadwal[] = $mk;
                            $seenKodeMk[] = $kodeMk;
                        }
                    }
                    
                    $dosen['matakuliah'] = $uniqueJadwal;
                } else {
                    $dosen['matakuliah'] = [];
                }
            }
            unset($dosen); // Break reference
            
            // Create paginator with fetched data
            $dosenList = new \Illuminate\Pagination\LengthAwarePaginator(
                $currentPageDosen,
                $dosenCollection->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            return view('gkm.data-master.penugasan-dosen', compact('user', 'dosenList'));
            
        } catch (\Exception $e) {
            // Fallback to database on error
            \Log::error('API Dosen Error: ' . $e->getMessage());
            return $this->penugasanDosenFromDatabase($request, $user);
        }
    }
    
    private function penugasanDosenFromDatabase(Request $request, $user)
    {
        $query = Dosen::with('matakuliah')
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap');
        
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }
        
        if ($request->search) {
            $query->where('nama_lengkap', 'like', '%' . $request->search . '%');
        }
        
        $dosenList = $query->paginate(10)->appends($request->all());
        
        return view('gkm.data-master.penugasan-dosen', compact('user', 'dosenList'));
    }
}
