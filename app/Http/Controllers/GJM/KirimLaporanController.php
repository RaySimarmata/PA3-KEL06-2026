<?php

namespace App\Http\Controllers\GJM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AIAgentService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KirimLaporanController extends Controller
{
    protected $aiAgent;

    public function __construct(AIAgentService $aiAgent)
    {
        $this->aiAgent = $aiAgent;
    }

    public function index()
    {
        // Ambil laporan GJM yang sudah approved
        $laporanList = \App\Models\LaporanGJM::where('status_laporan', 'approved')
            ->orderBy('periode_akhir', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('gjm.kirim-laporan.index', compact('laporanList'));
    }

    public function generateMessage(Request $request)
    {
        try {
            $request->validate([
                'recipients' => 'required|string',
                'subject' => 'required|string',
            ]);

            $recipients = $request->recipients;
            $subject = $request->subject;
            
            // Get logged in user info
            $user = auth()->user();
            
            // Fixed recipients for "Kepada Yth." (tidak dari input email)
            $fixedRecipients = [
                'Rektor',
                'Wakil Rektor Bidang Akademik',
                'Dekan Fakultas Vokasi',
                'SPM'
            ];
            
            $recipientList = implode(', ', $fixedRecipients);

            // Check if AI Agent is available
            if (!$this->aiAgent) {
                throw new \Exception('AI Agent service tidak tersedia');
            }

            $prompt = "Buatkan pesan email SINGKAT dan FORMAL untuk pengiriman laporan dengan detail berikut:\n\n";
            $prompt .= "Kepada: {$recipientList}\n";
            $prompt .= "Subjek: {$subject}\n";
            $prompt .= "Pengirim: Tim GJM Fakultas Vokasi\n\n";
            $prompt .= "ATURAN PENTING:\n";
            $prompt .= "1. Menggunakan bahasa Indonesia yang SANGAT FORMAL dan sopan\n";
            $prompt .= "2. Dimulai LANGSUNG dengan 'Kepada Yth. {$recipientList}' TANPA intro atau penjelasan apapun\n";
            $prompt .= "3. WAJIB gunakan 'Bapak/Ibu' BUKAN 'Anda' untuk menyapa\n";
            $prompt .= "4. Menyebutkan tujuan pengiriman berdasarkan subjek: '{$subject}'\n";
            $prompt .= "5. SANGAT SINGKAT, maksimal 80 kata saja\n";
            $prompt .= "6. Diakhiri dengan 'Terima kasih atas perhatian Bapak/Ibu.' dan ditutup dengan 'Tim GJM Fakultas Vokasi'\n";
            $prompt .= "7. Jangan gunakan placeholder seperti [tanggal]\n";
            $prompt .= "8. Gunakan kata ganti 'kami' untuk pengirim dan 'Bapak/Ibu' untuk penerima\n";
            $prompt .= "9. JANGAN tambahkan kalimat seperti 'kami berharap Bapak/Ibu dapat meninjau' atau 'memberikan umpan balik'\n";
            $prompt .= "10. Cukup sampaikan bahwa laporan telah dikirim/dilampirkan, TIDAK PERLU meminta tindak lanjut\n";
            $prompt .= "11. JANGAN tambahkan intro seperti 'Berikut adalah pesan email' atau penjelasan lainnya\n\n";
            $prompt .= "Langsung tulis pesan emailnya saja dengan format:\n";
            $prompt .= "Kepada Yth. {$recipientList}\n\n";
            $prompt .= "Dengan hormat,\n\n";
            $prompt .= "Kami dari Tim GJM Fakultas Vokasi dengan ini menyampaikan {$subject} yang telah kami lengkapi.\n\n";
            $prompt .= "Terima kasih atas perhatian Bapak/Ibu.\n\n";
            $prompt .= "Tim GJM Fakultas Vokasi";

            $generatedMessage = $this->aiAgent->generateText($prompt, 400);

            if (empty($generatedMessage)) {
                throw new \Exception('AI Agent tidak menghasilkan pesan. Periksa koneksi ke LLM.');
            }

            return response()->json([
                'success' => true,
                'message' => $generatedMessage
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->errors())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error generating message: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate pesan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function send(Request $request)
    {
        try {
            $request->validate([
                'recipients' => 'required|string',
                'subject' => 'required|string',
                'message' => 'required|string',
                'cc' => 'nullable|string',
                'attachments.*' => 'nullable|file|max:10240',
                'laporan_ids' => 'nullable|array',
                'laporan_ids.*' => 'exists:laporan_gjm,id'
            ]);

            $recipients = array_map('trim', explode(',', $request->recipients));
            $cc = $request->cc ? array_map('trim', explode(',', $request->cc)) : [];
            $subject = $request->subject;
            $message = $request->message;

            // Validate email addresses
            foreach ($recipients as $recipient) {
                if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Email tidak valid: {$recipient}");
                }
            }

            foreach ($cc as $ccEmail) {
                if (!filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Email CC tidak valid: {$ccEmail}");
                }
            }

            // Get laporan files if selected
            $laporanFiles = [];
            if ($request->has('laporan_ids') && !empty($request->laporan_ids)) {
                $laporanList = \App\Models\LaporanGJM::whereIn('id', $request->laporan_ids)->get();
                
                foreach ($laporanList as $laporan) {
                    // Check if file exists
                    $filePath = null;
                    $fileName = null;
                    
                    if ($laporan->file_laporan && \Storage::exists($laporan->file_laporan)) {
                        $filePath = $laporan->file_laporan;
                        $extension = pathinfo($laporan->file_laporan, PATHINFO_EXTENSION);
                        $fileName = 'Laporan_GJM_' . str_replace(' ', '_', $laporan->getPeriodeLabel()) . '.' . $extension;
                    } elseif ($laporan->dokumen_path && \Storage::exists($laporan->dokumen_path)) {
                        $filePath = $laporan->dokumen_path;
                        $extension = pathinfo($laporan->dokumen_path, PATHINFO_EXTENSION);
                        $fileName = 'Laporan_GJM_' . str_replace(' ', '_', $laporan->getPeriodeLabel()) . '.' . $extension;
                    }
                    
                    if ($filePath) {
                        $fullPath = storage_path('app/' . $filePath);
                        if (file_exists($fullPath)) {
                            $laporanFiles[] = [
                                'path' => $fullPath,
                                'name' => $fileName,
                                'mime' => \Storage::mimeType($filePath)
                            ];
                        } else {
                            Log::warning("File laporan tidak ditemukan: {$fullPath}");
                        }
                    }
                }
                
                if (empty($laporanFiles) && !empty($request->laporan_ids)) {
                    Log::warning('Tidak ada file laporan yang valid ditemukan untuk IDs: ' . json_encode($request->laporan_ids));
                }
            }

            $sentCount = 0;
            foreach ($recipients as $recipient) {
                try {
                    Mail::raw($message, function ($mail) use ($recipient, $cc, $subject, $request, $laporanFiles) {
                        $mail->to($recipient)
                             ->subject($subject);
                        
                        if (!empty($cc)) {
                            $mail->cc($cc);
                        }

                        // Attach external files
                        if ($request->hasFile('attachments')) {
                            foreach ($request->file('attachments') as $file) {
                                $mail->attach($file->getRealPath(), [
                                    'as' => $file->getClientOriginalName(),
                                    'mime' => $file->getMimeType(),
                                ]);
                            }
                        }
                        
                        // Attach laporan files
                        foreach ($laporanFiles as $laporanFile) {
                            $mail->attach($laporanFile['path'], [
                                'as' => $laporanFile['name'],
                                'mime' => $laporanFile['mime'],
                            ]);
                        }
                    });
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to send email to {$recipient}: " . $e->getMessage());
                }
            }

            if ($sentCount === 0) {
                throw new \Exception('Tidak ada email yang berhasil dikirim. Periksa konfigurasi email di .env');
            }

            $attachmentInfo = '';
            if ($request->hasFile('attachments')) {
                $attachmentInfo .= count($request->file('attachments')) . ' file eksternal';
            }
            if (!empty($laporanFiles)) {
                if ($attachmentInfo) $attachmentInfo .= ' dan ';
                $attachmentInfo .= count($laporanFiles) . ' laporan';
            }

            $message = "Laporan berhasil dikirim ke {$sentCount} dari " . count($recipients) . ' penerima';
            if ($attachmentInfo) {
                $message .= " dengan {$attachmentInfo}";
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $e->errors())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error sending laporan: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim laporan: ' . $e->getMessage()
            ], 500);
        }
    }
}