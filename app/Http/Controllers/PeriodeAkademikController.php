<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PeriodeAkademik;
use App\Models\Dosenn;
use App\Jobs\SyncJadwalDosenJob;

class PeriodeAkademikController extends Controller
{
    public function index()
    {
        $data = PeriodeAkademik::latest()->get();

        return view('gkm.data-master.periodeA', compact('data'));
    }

    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | NORMALISASI DATA
        |--------------------------------------------------------------------------
        */

        if ($request->semester === 'Ganjil') {

            $request->merge(['semester' => 1]);

        } elseif ($request->semester === 'Genap') {

            $request->merge(['semester' => 2]);
        }

        /*
        |--------------------------------------------------------------------------
        | FORMAT TANGGAL
        |--------------------------------------------------------------------------
        */
        if ($request->start_date) {

            try {

                $request->merge([
                    'start_date' => \Carbon\Carbon::createFromFormat(
                        'd/m/Y',
                        $request->start_date
                    )->format('Y-m-d')
                ]);

            } catch (\Exception $e) {
            }
        }

        if ($request->end_date) {

            try {

                $request->merge([
                    'end_date' => \Carbon\Carbon::createFromFormat(
                        'd/m/Y',
                        $request->end_date
                    )->format('Y-m-d')
                ]);

            } catch (\Exception $e) {
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */
        $request->validate([
            'tahun_ajaran' => 'required|integer|min:2000|max:2100',
            'semester'     => 'required|in:1,2',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date|after:start_date',
        ], [
            'semester.in'      => 'Semester harus dipilih (Ganjil/Genap)',
            'start_date.required' => 'Tanggal mulai wajib diisi',
            'start_date.date'  => 'Format tanggal mulai tidak valid',
            'end_date.date'    => 'Format tanggal selesai tidak valid',
            'end_date.after'   => 'Tanggal selesai harus setelah tanggal mulai',
        ]);

        /*
        |--------------------------------------------------------------------------
        | SIMPAN
        |--------------------------------------------------------------------------
        */
        PeriodeAkademik::create([
            'tahun_ajaran' => $request->tahun_ajaran,
            'semester'     => $request->semester,
            'semester_label' => $request->semester == 1
                ? 'Ganjil'
                : 'Genap',
            'start_date'   => $request->start_date,
            'end_date'     => $request->end_date,
            'is_active'    => false
        ]);

        return back()->with(
            'success',
            'Periode berhasil ditambahkan'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | API PERIODE AKTIF
    |--------------------------------------------------------------------------
    */
    public function getActive()
    {
        return response()->json(
            PeriodeAkademik::getActive()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SET ACTIVE + AUTO SYNC JADWAL DOSEN
    |--------------------------------------------------------------------------
    */
    public function setActive($id)
    {
        /*
        |--------------------------------------------------------------------------
        | NONAKTIFKAN SEMUA
        |--------------------------------------------------------------------------
        */
        PeriodeAkademik::where('is_active', true)
            ->update([
                'is_active' => false
            ]);

        /*
        |--------------------------------------------------------------------------
        | AKTIFKAN PERIODE TERPILIH
        |--------------------------------------------------------------------------
        */
        $periode = PeriodeAkademik::findOrFail($id);

        $periode->update([
            'is_active' => true
        ]);

        /*
        |--------------------------------------------------------------------------
        | AUTO SYNC JADWAL DOSEN
        |--------------------------------------------------------------------------
        */
        $dosenList = Dosenn::select('pegawai_id')->get();

        foreach ($dosenList as $dosen) {

            SyncJadwalDosenJob::dispatch(
                $dosen->pegawai_id,
                $periode->semester,
                $periode->tahun_ajaran
            );
        }

        return back()->with(
            'success',
            'Periode berhasil diaktifkan dan sync jadwal dosen dimulai'
        );
    }
}