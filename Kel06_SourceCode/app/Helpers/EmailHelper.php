<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use App\Services\AIAgentService;

class EmailHelper
{
    /**
     * Check if AI agent is enabled
     */
    private static function isAIEnabled()
    {
        return config('services.llm.enabled', env('LLM_ENABLED', true)); // Default true untuk pure AI
    }
    /**
     * Test SMTP connection
     */
    public static function testSmtpConnection()
    {
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $timeout = config('mail.mailers.smtp.timeout', 30);

        try {
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            
            if (!$connection) {
                Log::error("SMTP Connection Failed", [
                    'host' => $host,
                    'port' => $port,
                    'error_code' => $errno,
                    'error_message' => $errstr
                ]);
                return false;
            }

            fclose($connection);
            Log::info("SMTP Connection Success", [
                'host' => $host,
                'port' => $port
            ]);
            return true;

        } catch (\Exception $e) {
            Log::error("SMTP Connection Exception", [
                'host' => $host,
                'port' => $port,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get email configuration info
     */
    public static function getEmailConfig()
    {
        return [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'timeout' => config('mail.mailers.smtp.timeout'),
        ];
    }

    /**
     * Validate email configuration
     */
    public static function validateConfig()
    {
        $errors = [];

        if (empty(config('mail.mailers.smtp.host'))) {
            $errors[] = 'MAIL_HOST tidak diset';
        }

        if (empty(config('mail.mailers.smtp.port'))) {
            $errors[] = 'MAIL_PORT tidak diset';
        }

        if (empty(config('mail.mailers.smtp.username'))) {
            $errors[] = 'MAIL_USERNAME tidak diset';
        }

        if (empty(config('mail.mailers.smtp.password'))) {
            $errors[] = 'MAIL_PASSWORD tidak diset';
        }

        if (empty(config('mail.from.address'))) {
            $errors[] = 'MAIL_FROM_ADDRESS tidak diset';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Send Reminder Perwalian with AI-generated message
     * Supports both manual (with parameters) and automatic (with Dosen object)
     */
    public static function sendReminderPerwalian($param1, $param2 = null, $param3 = null, $param4 = null, $param5 = 'TRPL', $param6 = 'TRPL')
    {
        // Check if first parameter is Dosen object (for scheduler)
        if (is_object($param1) && get_class($param1) === 'App\Models\Dosen') {
            $dosen = $param1;
            $dosenEmail = $dosen->kontak_email;
            $dosenName = $dosen->nama_lengkap;
            $subject = 'Reminder: Perwalian Mahasiswa';
            $prodiName = $dosen->prodi->nama_prodi ?? 'TRPL';
            $prodiKode = $dosen->prodi->kode_prodi ?? 'TRPL';
            $prodiId = $dosen->prodi_id;

            // Generate message using AI Agent - PURE AI
            if (self::isAIEnabled()) {
                try {
                    $aiAgent = new AIAgentService();
                    $dosenCollection = collect([$dosen]);
                    $messageContent = $aiAgent->generateReminderMessage($dosenCollection, $dosen->prodi, 'perwalian');
                    Log::info("AI Agent generated Perwalian reminder", ['dosen' => $dosen->nama_lengkap]);
                } catch (\Exception $e) {
                    Log::error("AI Agent failed, cannot send reminder", ['error' => $e->getMessage()]);
                    throw new \Exception("AI Agent tidak tersedia: " . $e->getMessage());
                }
            } else {
                throw new \Exception("AI Agent harus diaktifkan untuk mengirim reminder");
            }
        } else {
            // Manual call with parameters
            $dosenEmail = $param1;
            $dosenName = $param2;
            $subject = $param3;
            $messageContent = $param4;
            $prodiName = $param5;
            $prodiKode = $param6;
            $prodiId = null; // Untuk manual call, prodi_id tidak tersedia
        }

        try {
            $mail = new \App\Mail\ReminderPerwalianMail($subject, $messageContent, $dosenName, $prodiName, $prodiKode);
            \Illuminate\Support\Facades\Mail::to($dosenEmail)->send($mail);

            \App\Models\LogEmail::create([
                'prodi_id' => $prodiId,
                'penerima_email' => $dosenEmail,
                'subjek' => $subject,
                'isi_email' => $messageContent,
                'status_pengiriman' => 'success',
                'tanggal_pengiriman' => now()->toDateString(),
            ]);

            Log::info("Reminder Perwalian sent successfully", ['to' => $dosenEmail, 'ai_enabled' => self::isAIEnabled()]);
            return is_object($param1) ? true : ['success' => true, 'message' => 'Email berhasil dikirim'];

        } catch (\Exception $e) {
            \App\Models\LogEmail::create([
                'prodi_id' => $prodiId,
                'penerima_email' => $dosenEmail,
                'subjek' => $subject,
                'isi_email' => $messageContent,
                'status_pengiriman' => 'failed',
                'tanggal_pengiriman' => now()->toDateString(),
                'pesan_error' => $e->getMessage(),
            ]);

            Log::error("Failed to send Reminder Perwalian", ['to' => $dosenEmail, 'error' => $e->getMessage()]);
            return is_object($param1) ? false : ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send Reminder Upload Materi with AI-generated message
     * Supports both manual (with parameters) and automatic (with Dosen object)
     */
    public static function sendReminderUploadMateri($param1, $param2 = null, $param3 = null, $param4 = null, $param5 = 'TRPL', $param6 = 'TRPL')
    {
        if (is_object($param1) && get_class($param1) === 'App\Models\Dosen') {
            $dosen = $param1;
            $dosenEmail = $dosen->kontak_email;
            $dosenName = $dosen->nama_lengkap;
            $subject = 'Reminder: Upload Materi Perkuliahan';
            $prodiName = $dosen->prodi->nama_prodi ?? 'TRPL';
            $prodiKode = $dosen->prodi->kode_prodi ?? 'TRPL';
            $prodiId = $dosen->prodi_id;

            // Generate message using AI Agent - PURE AI
            if (self::isAIEnabled()) {
                try {
                    $aiAgent = new AIAgentService();
                    $dosenCollection = collect([$dosen]);
                    $messageContent = $aiAgent->generateReminderMessage($dosenCollection, $dosen->prodi, 'materi');
                    Log::info("AI Agent generated Materi reminder", ['dosen' => $dosen->nama_lengkap]);
                } catch (\Exception $e) {
                    Log::error("AI Agent failed, cannot send reminder", ['error' => $e->getMessage()]);
                    throw new \Exception("AI Agent tidak tersedia: " . $e->getMessage());
                }
            } else {
                throw new \Exception("AI Agent harus diaktifkan untuk mengirim reminder");
            }
        } else {
            $dosenEmail = $param1;
            $dosenName = $param2;
            $subject = $param3;
            $messageContent = $param4;
            $prodiName = $param5;
            $prodiKode = $param6;
            $prodiId = null;
        }

        try {
            $mail = new \App\Mail\ReminderUploadMateriMail($subject, $messageContent, $dosenName, $prodiName, $prodiKode);
            \Illuminate\Support\Facades\Mail::to($dosenEmail)->send($mail);

            \App\Models\LogEmail::create([
                'prodi_id' => $prodiId,
                'penerima_email' => $dosenEmail,
                'subjek' => $subject,
                'isi_email' => $messageContent,
                'status_pengiriman' => 'success',
                'tanggal_pengiriman' => now()->toDateString(),
            ]);

            Log::info("Reminder Upload Materi sent successfully", ['to' => $dosenEmail, 'ai_enabled' => self::isAIEnabled()]);
            return is_object($param1) ? true : ['success' => true, 'message' => 'Email berhasil dikirim'];

        } catch (\Exception $e) {
            \App\Models\LogEmail::create([
                'prodi_id' => $prodiId,
                'penerima_email' => $dosenEmail,
                'subjek' => $subject,
                'isi_email' => $messageContent,
                'status_pengiriman' => 'failed',
                'tanggal_pengiriman' => now()->toDateString(),
                'pesan_error' => $e->getMessage(),
            ]);

            Log::error("Failed to send Reminder Upload Materi", ['to' => $dosenEmail, 'error' => $e->getMessage()]);
            return is_object($param1) ? false : ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send Reminder Review Soal with AI-generated message
     * Supports both manual (with parameters) and automatic (with Dosen object)
     */
    public static function sendReminderReviewSoal($param1, $param2 = null, $param3 = null, $param4 = null, $param5 = 'TRPL', $param6 = 'TRPL')
    {
        if (is_object($param1) && get_class($param1) === 'App\Models\Dosen') {
            $dosen = $param1;
            $dosenEmail = $dosen->kontak_email;
            $dosenName = $dosen->nama_lengkap;
            $subject = 'Reminder: Review Soal Ujian';
            $prodiName = $dosen->prodi->nama_prodi ?? 'TRPL';
            $prodiKode = $dosen->prodi->kode_prodi ?? 'TRPL';
            $prodiId = $dosen->prodi_id;

            // Generate message using AI Agent - PURE AI
            if (self::isAIEnabled()) {
                try {
                    $aiAgent = new AIAgentService();
                    $dosenCollection = collect([$dosen]);
                    $messageContent = $aiAgent->generateReminderMessage($dosenCollection, $dosen->prodi, 'soal');
                    Log::info("AI Agent generated Review Soal reminder", ['dosen' => $dosen->nama_lengkap]);
                } catch (\Exception $e) {
                    Log::error("AI Agent failed, cannot send reminder", ['error' => $e->getMessage()]);
                    throw new \Exception("AI Agent tidak tersedia: " . $e->getMessage());
                }
            } else {
                throw new \Exception("AI Agent harus diaktifkan untuk mengirim reminder");
            }
        } else {
            $dosenEmail = $param1;
            $dosenName = $param2;
            $subject = $param3;
            $messageContent = $param4;
            $prodiName = $param5;
            $prodiKode = $param6;
            $prodiId = null;
        }

        try {
            $mail = new \App\Mail\ReminderReviewSoalMail($subject, $messageContent, $dosenName, $prodiName, $prodiKode);
            \Illuminate\Support\Facades\Mail::to($dosenEmail)->send($mail);

            \App\Models\LogEmail::create([
                'prodi_id' => $prodiId,
                'penerima_email' => $dosenEmail,
                'subjek' => $subject,
                'isi_email' => $messageContent,
                'status_pengiriman' => 'success',
                'tanggal_pengiriman' => now()->toDateString(),
            ]);

            Log::info("Reminder Review Soal sent successfully", ['to' => $dosenEmail, 'ai_enabled' => self::isAIEnabled()]);
            return is_object($param1) ? true : ['success' => true, 'message' => 'Email berhasil dikirim'];

        } catch (\Exception $e) {
            \App\Models\LogEmail::create([
                'prodi_id' => $prodiId,
                'penerima_email' => $dosenEmail,
                'subjek' => $subject,
                'isi_email' => $messageContent,
                'status_pengiriman' => 'failed',
                'tanggal_pengiriman' => now()->toDateString(),
                'pesan_error' => $e->getMessage(),
            ]);

            Log::error("Failed to send Reminder Review Soal", ['to' => $dosenEmail, 'error' => $e->getMessage()]);
            return is_object($param1) ? false : ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send Reminder RPS with AI Agent - PURE AI
     */
    public static function sendReminderRPS($dosen)
    {
        $subject = 'Reminder: Upload RPS';
        $prodiName = $dosen->prodi->nama_prodi ?? 'TRPL';
        $prodiKode = $dosen->prodi->kode_prodi ?? 'TRPL';
        $prodiId = $dosen->prodi_id;

        // Generate message using AI Agent - PURE AI
        if (self::isAIEnabled()) {
            try {
                $aiAgent = new AIAgentService();
                $dosenCollection = collect([$dosen]);
                $message = $aiAgent->generateReminderMessage($dosenCollection, $dosen->prodi, 'rps');
                Log::info("AI Agent generated RPS reminder", ['dosen' => $dosen->nama_lengkap]);
            } catch (\Exception $e) {
                Log::error("AI Agent failed, cannot send reminder", ['error' => $e->getMessage()]);
                throw new \Exception("AI Agent tidak tersedia: " . $e->getMessage());
            }
        } else {
            throw new \Exception("AI Agent harus diaktifkan untuk mengirim reminder");
        }

        try {
            $mail = new \App\Mail\ReminderRPSMail($subject, $message, $dosen->nama_lengkap, $prodiName, $prodiKode);
            \Illuminate\Support\Facades\Mail::to($dosen->kontak_email)->send($mail);

            \App\Models\LogEmail::create([
                'prodi_id' => $prodiId,
                'penerima_email' => $dosen->kontak_email,
                'subjek' => $subject,
                'isi_email' => $message,
                'status_pengiriman' => 'success',
                'tanggal_pengiriman' => now()->toDateString(),
            ]);

            Log::info("Reminder RPS sent successfully via AI Agent", ['to' => $dosen->kontak_email]);
            return true;

        } catch (\Exception $e) {
            \App\Models\LogEmail::create([
                'prodi_id' => $prodiId,
                'penerima_email' => $dosen->kontak_email,
                'subjek' => $subject,
                'isi_email' => $message ?? 'AI Agent error',
                'status_pengiriman' => 'failed',
                'tanggal_pengiriman' => now()->toDateString(),
                'pesan_error' => $e->getMessage(),
            ]);

            Log::error("Failed to send Reminder RPS", ['to' => $dosen->kontak_email, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
