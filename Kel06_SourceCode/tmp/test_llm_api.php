<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$apiKey = env('LLM_API_KEY');
$baseUrl = env('LLM_BASE_URL', 'https://api.openai.com/v1');
$model = env('LLM_MODEL', 'gpt-4o-mini');

echo "Testing LLM API connectivity...\n";
echo "Provider: " . env('LLM_PROVIDER') . "\n";
echo "Model: {$model}\n";
echo "Base URL: {$baseUrl}\n";
echo "API Key length: " . strlen($apiKey) . "\n\n";

// Test with a simple request
try {
    echo "Sending test request to {$baseUrl}/chat/completions\n";
    echo "Timeout: 10 seconds\n";
    
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ])->timeout(10)->post($baseUrl . '/chat/completions', [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Say hello in one word.']
        ],
        'max_tokens' => 10,
    ]);
    
    echo "Response status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "Success! Response:\n";
        echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Error status: " . $response->status() . "\n";
        echo "Response body:\n";
        echo $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Error occurred during HTTP request\n";
}
