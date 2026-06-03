<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function sendMessage($phone, $message)
    {
        try {

            $phone = preg_replace('/[^0-9]/', '', $phone);

            if (substr($phone, 0, 1) == '0') {
                $phone = '62' . substr($phone, 1);
            }

            $response = Http::withHeaders([
                'APIKey' => env('WACHAT_TOKEN'),
                'Content-Type' => 'application/json',
            ])->post(env('WACHAT_URL'), [

                'destination' => $phone,
                'message'     => $message,
                'queue'       => env('WACHAT_INSTANCE_ID'),

            ]);

            Log::info('WA RESPONSE', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return $response->successful();

        } catch (\Exception $e) {

            Log::error('WA Failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}