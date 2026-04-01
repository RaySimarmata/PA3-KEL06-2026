<?php

namespace App\Http\Controllers\GKM;

use App\Http\Controllers\Controller;
use App\Models\RPS;
use App\Models\Materi;
use App\Models\User;
use App\Models\Dosen;
use App\Models\LogEmail;
use App\Mail\ReminderRPSMail;
use App\Services\AIAgentService;
use App\Helpers\EmailHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MonitoringRPSController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Filter dosen berdasarkan prodi GKM
        $query = \App\Models\Dosen::with('matakuliah')
            ->where('status', 'aktif');

        // Jika GKM punya prodi_id, filter hanya dosen dari prodi tersebut
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }

        $dosenList = $query->paginate(10);

        return view('gkm.monitoring-rps.index', compact('user', 'dosenList'));
    }

    public function ceklistRPS()
    {
        $user = Auth::user();

        // Filter dosen berdasarkan prodi GKM
        $query = Dosen::with('matakuliah')
            ->where('status', 'aktif');

        // Jika GKM punya prodi_id, filter hanya dosen dari prodi tersebut
        if ($user->prodi_id) {
            $query->where('prodi_id', $user->prodi_id);
        }

        $dosenList = $query->get();

        return view('gkm.monitoring-rps.ceklist', compact('user', 'dosenList'));
    }

    public function generateReminderMessage(Request $request)
    {
        try {
            $request->validate([
                'dosen_ids' => 'required|array',
                'dosen_ids.*' => 'exists:dosen,id',
            ]);

            $dosenIds = $request->dosen_ids;
            $dosenList = Dosen::with('matakuliah')->whereIn('id', $dosenIds)->get();

            if ($dosenList->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada dosen yang dipilih'
                ], 400);
            }

            // Ambil prodi dari user yang login
            $user = Auth::user();
            $userProdi = $user->prodi;

            Log::info('Generating reminder message', [
                'dosen_count' => $dosenList->count(),
                'prodi' => $userProdi ? $userProdi->kode_prodi : 'null'
            ]);

            // Generate pesan reminder menggunakan AI Agent dengan info prodi
            $aiAgent = new AIAgentService();
            $templatePesan = $aiAgent->generateReminderMessage($dosenList, $userProdi, 'rps');

            Log::info('Reminder message generated successfully', [
                'message_length' => strlen($templatePesan)
            ]);

            return response()->json([
                'success' => true,
                'message' => $templatePesan,
                'dosen_count' => $dosenList->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Generate Reminder Message Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'AI Agent error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendReminder(Request $request)
    {
        $request->validate([
            'dosen_ids' => 'required|array',
            'dosen_ids.*' => 'exists:dosen,id',
            'message' => 'required|string',
            'subject' => 'required|string|max:255',
        ]);

        // Validasi konfigurasi email
        $configValidation = EmailHelper::validateConfig();
        if (!$configValidation['valid']) {
            return redirect()->back()
                ->with('error', 'Konfigurasi email tidak lengkap: ' . implode(', ', $configValidation['errors']))
                ->withInput();
        }

        // Test koneksi SMTP
        if (!EmailHelper::testSmtpConnection()) {
            Log::error('SMTP Connection Test Failed', EmailHelper::getEmailConfig());
            return redirect()->back()
                ->with('error', 'Tidak dapat terhubung ke server email. Periksa koneksi internet dan konfigurasi SMTP.')
                ->withInput();
        }

        try {
            $dosenList = Dosen::whereIn('id', $request->dosen_ids)->get();
            $successCount = 0;
            $failedCount = 0;
            $failedEmails = [];

            // Ambil info prodi dari user yang login
            $user = Auth::user();
            $prodiName = $user->prodi ? $user->prodi->nama_prodi : 'TRPL';
            $prodiKode = $user->prodi ? $user->prodi->kode_prodi : 'TRPL';

            foreach ($dosenList as $dosen) {
                $emailSent = false;
                $lastError = '';
                $maxRetries = 3;

                // Retry mechanism untuk setiap email
                for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                    try {
                        Log::info("Attempting to send email", [
                            'to' => $dosen->kontak_email,
                            'attempt' => $attempt,
                            'max_retries' => $maxRetries
                        ]);

                        // Kirim email dengan info prodi
                        Mail::to($dosen->kontak_email)->send(
                            new ReminderRPSMail(
                                $request->subject,
                                $request->message,
                                $dosen->nama_lengkap,
                                $prodiName,
                                $prodiKode
                            )
                        );

                        $emailSent = true;
                        
                        Log::info("Email sent successfully", [
                            'to' => $dosen->kontak_email,
                            'attempt' => $attempt
                        ]);

                        // Simpan log email jika berhasil
                        LogEmail::create([
                            'reminder_id' => null,
                            'prodi_id' => $user->prodi_id,
                            'penerima_email' => $dosen->kontak_email,
                            'subjek' => $request->subject,
                            'isi_email' => $request->message,
                            'status_pengiriman' => 'success',
                            'tanggal_pengiriman' => now()->toDateString(),
                            'percobaan_kirim' => $attempt,
                        ]);

                        $successCount++;
                        break; // Keluar dari loop retry jika berhasil

                    } catch (\Exception $e) {
                        $lastError = $e->getMessage();
                        
                        Log::error("Email sending failed", [
                            'to' => $dosen->kontak_email,
                            'attempt' => $attempt,
                            'error' => $lastError
                        ]);
                        
                        // Jika bukan percobaan terakhir, tunggu sebentar sebelum retry
                        if ($attempt < $maxRetries) {
                            sleep(2); // Tunggu 2 detik sebelum retry
                        }
                    }
                }

                // Jika semua percobaan gagal
                if (!$emailSent) {
                    LogEmail::create([
                        'reminder_id' => null,
                        'prodi_id' => $user->prodi_id,
                        'penerima_email' => $dosen->kontak_email,
                        'subjek' => $request->subject,
                        'isi_email' => $request->message,
                        'status_pengiriman' => 'failed',
                        'pesan_error' => $lastError,
                        'tanggal_pengiriman' => now()->toDateString(),
                        'percobaan_kirim' => $maxRetries,
                    ]);

                    $failedCount++;
                    $failedEmails[] = $dosen->kontak_email;
                }
            }

            // Response berdasarkan hasil pengiriman
            if ($failedCount > 0 && $successCount > 0) {
                return redirect()->route('gkm.monitoring-rps.index')
                    ->with('warning', "Reminder berhasil dikirim ke {$successCount} dosen, gagal ke {$failedCount} dosen. Email yang gagal: " . implode(', ', $failedEmails));
            } elseif ($failedCount > 0 && $successCount == 0) {
                $errorMsg = "Gagal mengirim reminder ke semua dosen. ";
                $errorMsg .= "Periksa: 1) Koneksi internet, 2) Konfigurasi MAIL_* di .env, 3) App Password Gmail. ";
                $errorMsg .= "Email yang gagal: " . implode(', ', $failedEmails);
                
                return redirect()->back()
                    ->with('error', $errorMsg)
                    ->withInput();
            }

            return redirect()->route('gkm.monitoring-rps.index')
                ->with('success', "Reminder berhasil dikirim ke {$successCount} dosen");
        } catch (\Exception $e) {
            Log::error('Send Reminder Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Gagal mengirim reminder: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function historyReminder($dosenId = null)
    {
        $user = Auth::user();

        // Filter dosen berdasarkan prodi GKM
        $dosenQuery = Dosen::where('status', 'aktif');
        
        if ($user->prodi_id) {
            $dosenQuery->where('prodi_id', $user->prodi_id);
        }
        
        $dosenList = $dosenQuery->get();
        
        // Ambil log email
        $logEmailQuery = LogEmail::query();
        
        // Filter berdasarkan dosen yang dipilih
        if ($dosenId) {
            $dosen = Dosen::find($dosenId);
            if ($dosen) {
                $logEmailQuery->where('penerima_email', $dosen->kontak_email);
            }
        } elseif ($user->prodi_id) {
            // Filter log email hanya untuk dosen di prodi GKM
            $dosenEmails = $dosenList->pluck('kontak_email')->toArray();
            $logEmailQuery->whereIn('penerima_email', $dosenEmails);
        }
        
        $logEmailList = $logEmailQuery->orderBy('created_at', 'desc')->paginate(10);

        return view('gkm.monitoring-rps.history', compact('user', 'logEmailList', 'dosenList', 'dosenId'));
    }
}
