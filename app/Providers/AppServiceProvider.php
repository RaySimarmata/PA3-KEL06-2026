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
        // Paksa semua URL pakai APP_URL (fix untuk Docker/VPS/reverse proxy)
        // Ini memastikan pagination, redirect, dan semua generated URL pakai host yang benar
        $appUrl = config('app.url');
        if ($appUrl) {
            \URL::forceRootUrl($appUrl);

            if (str_starts_with($appUrl, 'https://')) {
                \URL::forceScheme('https');
            } else {
                \URL::forceScheme('http');
            }
        }

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
