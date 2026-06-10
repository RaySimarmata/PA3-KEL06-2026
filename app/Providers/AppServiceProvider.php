<?php

namespace App\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Gunakan Bootstrap 5 untuk pagination
        Paginator::useBootstrap();

        // Jika variabel env MAIL_TEST_RECIPIENTS diisi,
        // semua email akan diarahkan ke alamat-alamat pengujian ini.
        $testRecipients = env('MAIL_TEST_RECIPIENTS');
        if (!empty($testRecipients)) {
            $emails = array_filter(array_map('trim', explode(',', $testRecipients)));
            if (!empty($emails)) {
                Mail::alwaysTo($emails);
            }
        }
    }
}
