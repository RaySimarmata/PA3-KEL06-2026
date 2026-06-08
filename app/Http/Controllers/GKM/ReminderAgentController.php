<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\JadwalReminder;
use App\Models\LogEmail;
use App\Models\Dosen;
use App\Helpers\EmailHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReminderAgentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Filter jadwal reminder berdasarkan prodi user yang login
        $jadwalList = JadwalReminder::where('prodi_id', $user->prodi_id)
            ->orderBy('tanggal_mulai', 'desc')
            ->orderBy('jam_pengiriman', 'desc')
            ->paginate(10);
            
        return view('gkm.reminder-agent.index', compact('user', 'jadwalList'));
    }

    public function jadwal()
    {
        $user = Auth::user();
        
        // Filter jadwal reminder berdasarkan prodi user yang login
        $jadwalList = JadwalReminder::where('prodi_id', $user->prodi_id)
            ->orderBy('tanggal_mulai', 'desc')
            ->orderBy('jam_pengiriman', 'desc')
            ->paginate(10);
        
        return view('gkm.reminder-agent.jadwal', compact('user', 'jadwalList'));
    }

    public function logEmail()
    {
        $user = Auth::user();

        // Filter log email berdasarkan prodi user yang login
        $logEmailList = LogEmail::where('prodi_id', $user->prodi_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Hitung statistik untuk prodi user yang login
        $totalSuccess = LogEmail::where('prodi_id', $user->prodi_id)
            ->where('status_pengiriman', 'success')
            ->count();
        $totalFailed = LogEmail::where('prodi_id', $user->prodi_id)
            ->where('status_pengiriman', 'failed')
            ->count();
        $totalPending = LogEmail::where('prodi_id', $user->prodi_id)
            ->where('status_pengiriman', 'pending')
            ->count();

        return view('gkm.reminder-agent.log-email', compact('user', 'logEmailList', 'totalSuccess', 'totalFailed', 'totalPending'));
    }

    public function storeJadwal(Request $request)
    {
        $validated = $request->validate([
            'nama_jadwal' => 'required|string|max:255',
            'tipe_reminder' => 'required|in:Upload Materi,Review Soal,RPS',
            'jam_pengiriman' => 'required|date_format:H:i',
            'tanggal_kirim' => 'required|date|after_or_equal:today',
        ], [
            'tanggal_kirim.after_or_equal' => 'Tanggal kirim tidak boleh sebelum hari ini.',
        ]);

        // Validasi tambahan: jika tanggal hari ini, jam tidak boleh sebelum jam sekarang
        $tanggalKirim = Carbon::parse($validated['tanggal_kirim']);
        $jamPengiriman = $validated['jam_pengiriman'];
        $now = Carbon::now();
        
        if ($tanggalKirim->isToday()) {
            $waktuKirim = Carbon::createFromFormat('H:i', $jamPengiriman);
            $jamSekarang = Carbon::createFromFormat('H:i', $now->format('H:i'));
            
            if ($waktuKirim->lessThanOrEqualTo($jamSekarang)) {
                return back()->withErrors(['jam_pengiriman' => 'Untuk hari ini, jam pengiriman harus setelah jam sekarang (' . $now->format('H:i') . ').'])->withInput();
            }
        }

        $user = Auth::user();

        $jadwal = JadwalReminder::create([
            'prodi_id' => $user->prodi_id,
            'nama_jadwal' => $validated['nama_jadwal'],
            'tipe_reminder' => $validated['tipe_reminder'],
            'jam_pengiriman' => $validated['jam_pengiriman'],
            'waktu_pengiriman' => $validated['jam_pengiriman'] . ':00',
            'tanggal_mulai' => $validated['tanggal_kirim'],
            'tanggal_selesai' => null,
            'status' => 'aktif',
            'is_active' => true,
            'dibuat_oleh' => Auth::id(),
        ]);

        // Dispatch job untuk pengiriman terjadwal otomatis
        \App\Jobs\SendScheduledReminderJob::dispatch($jadwal);

        return redirect()->route('gkm.reminder-agent.index')
            ->with('success', 'Jadwal reminder berhasil ditambahkan. Email akan terkirim otomatis sesuai jadwal yang ditentukan.');
    }

    public function editJadwal($id)
    {
        $user = Auth::user();
        
        // Pastikan jadwal milik prodi user yang login
        $jadwal = JadwalReminder::where('id', $id)
            ->where('prodi_id', $user->prodi_id)
            ->firstOrFail();

        return view('gkm.reminder-agent.edit', compact('user', 'jadwal'));
    }

    public function updateJadwal(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_jadwal' => 'required|string|max:255',
            'tipe_reminder' => 'required|in:Upload Materi,Review Soal,RPS',
            'jam_pengiriman' => 'required|date_format:H:i',
            'tanggal_kirim' => 'required|date|after_or_equal:today',
            'is_active' => 'boolean',
        ], [
            'tanggal_kirim.after_or_equal' => 'Tanggal kirim tidak boleh sebelum hari ini.',
        ]);

        // Validasi tambahan: jika tanggal hari ini, jam tidak boleh sebelum jam sekarang
        $tanggalKirim = Carbon::parse($validated['tanggal_kirim']);
        $jamPengiriman = $validated['jam_pengiriman'];
        $now = Carbon::now();
        
        if ($tanggalKirim->isToday()) {
            $waktuKirim = Carbon::createFromFormat('H:i', $jamPengiriman);
            $jamSekarang = Carbon::createFromFormat('H:i', $now->format('H:i'));
            
            if ($waktuKirim->lessThanOrEqualTo($jamSekarang)) {
                return back()->withErrors(['jam_pengiriman' => 'Untuk hari ini, jam pengiriman harus setelah jam sekarang (' . $now->format('H:i') . ').'])->withInput();
            }
        }

        $user = Auth::user();
        
        // Pastikan jadwal milik prodi user yang login
        $jadwal = JadwalReminder::where('id', $id)
            ->where('prodi_id', $user->prodi_id)
            ->firstOrFail();

        $jadwal->update([
            'nama_jadwal' => $validated['nama_jadwal'],
            'tipe_reminder' => $validated['tipe_reminder'],
            'jam_pengiriman' => $validated['jam_pengiriman'],
            'tanggal_mulai' => $validated['tanggal_kirim'],
            'tanggal_selesai' => null,
            'is_active' => $request->has('is_active'),
        ]);

        // Dispatch ulang job jika jadwal diaktifkan dan belum terkirim
        if ($request->has('is_active') && !$jadwal->last_sent_at) {
            \App\Jobs\SendScheduledReminderJob::dispatch($jadwal);
        }

        return redirect()->route('gkm.reminder-agent.index')
            ->with('success', 'Jadwal reminder berhasil diperbarui. Email akan terkirim otomatis sesuai jadwal yang ditentukan.');
    }

    public function destroyJadwal($id)
    {
        try {
            $user = Auth::user();
            
            // Pastikan jadwal milik prodi user yang login
            $jadwal = JadwalReminder::where('id', $id)
                ->where('prodi_id', $user->prodi_id)
                ->firstOrFail();
                
            $jadwal->delete();

            return redirect()->route('gkm.reminder-agent.index')
                ->with('success', 'Jadwal reminder berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus jadwal reminder: ' . $e->getMessage());
        }
    }
}
