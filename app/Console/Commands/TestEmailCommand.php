<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReminderRPSMail;
use App\Mail\ReminderPerwalianMail;
use App\Mail\ReminderUploadMateriMail;
use App\Mail\ReminderReviewSoalMail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email} {--type=raw : Type of email (raw, rps, perwalian, materi, soal)} {--subject=Test Email} {--message=Ini adalah test email dari sistem GKM TRPL}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration dengan mengirim test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $type = $this->option('type');
        $subject = $this->option('subject');
        $message = $this->option('message');

        $this->info("Mengirim test email ke: {$email}");
        $this->info("Type: {$type}");
        $this->info("Subject: {$subject}");
        $this->info("Message: {$message}");
        $this->newLine();

        try {
            $this->info("Mencoba koneksi ke SMTP server...");
            
            // Test koneksi
            $transport = Mail::getSwiftMailer()->getTransport();
            
            $this->info("Mengirim email...");
            
            // Kirim email berdasarkan type
            switch ($type) {
                case 'rps':
                    $mail = new ReminderRPSMail($subject, $message, 'Test User', 'TRPL', 'TRPL');
                    Mail::to($email)->send($mail);
                    break;
                
                case 'perwalian':
                    $mail = new ReminderPerwalianMail($subject, $message, 'Test User', 'TRPL', 'TRPL');
                    Mail::to($email)->send($mail);
                    break;
                
                case 'materi':
                    $mail = new ReminderUploadMateriMail($subject, $message, 'Test User', 'TRPL', 'TRPL');
                    Mail::to($email)->send($mail);
                    break;
                
                case 'soal':
                    $mail = new ReminderReviewSoalMail($subject, $message, 'Test User', 'TRPL', 'TRPL');
                    Mail::to($email)->send($mail);
                    break;
                
                default:
                    Mail::raw($message, function($msg) use ($email, $subject) {
                        $msg->to($email)
                            ->subject($subject);
                    });
                    break;
            }

            // Cek failures
            if (count(Mail::failures()) > 0) {
                $this->error("Email gagal dikirim!");
                $this->error("Failed emails: " . implode(', ', Mail::failures()));
                return 1;
            }

            $this->newLine();
            $this->info("✓ Email berhasil dikirim!");
            $this->info("Silakan cek inbox email: {$email}");
            
            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ Error: " . $e->getMessage());
            $this->newLine();
            
            $this->warn("Troubleshooting:");
            $this->line("1. Cek koneksi internet: ping smtp.gmail.com");
            $this->line("2. Cek konfigurasi di .env:");
            $this->line("   - MAIL_HOST=" . config('mail.mailers.smtp.host'));
            $this->line("   - MAIL_PORT=" . config('mail.mailers.smtp.port'));
            $this->line("   - MAIL_USERNAME=" . config('mail.mailers.smtp.username'));
            $this->line("   - MAIL_ENCRYPTION=" . config('mail.mailers.smtp.encryption'));
            $this->line("3. Pastikan menggunakan App Password (bukan password biasa)");
            $this->line("4. Cek firewall/antivirus");
            
            return 1;
        }
    }
}
