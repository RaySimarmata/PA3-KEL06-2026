<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExternalAPIService;
use Illuminate\Support\Facades\Cache;

class TestLibraryAPIToken extends Command
{
    protected $signature = 'test:library-token';
    protected $description = 'Test Library API token auto-refresh';

    public function handle()
    {
        $this->info('Testing Library API Token Auto-Refresh...');
        $this->newLine();

        // Check configuration
        $this->info('1. Checking configuration...');
        $url = config('services.library.url');
        $username = config('services.library.username');
        $password = config('services.library.password');

        if (!$url || !$username || !$password) {
            $this->error('❌ Configuration incomplete!');
            $this->warn('Please set LIBRARY_API_URL, LIBRARY_API_USERNAME, and LIBRARY_API_PASSWORD in .env');
            return 1;
        }

        $this->info("   URL: {$url}");
        $this->info("   Username: {$username}");
        $this->info("   Password: " . str_repeat('*', strlen($password)));
        $this->newLine();

        // Test token generation
        $this->info('2. Testing token generation...');
        $apiService = new ExternalAPIService();
        
        $tokenInfo = $apiService->getTokenInfo();
        
        if ($tokenInfo['valid']) {
            $this->info('   ✅ Token generated successfully');
            $this->info("   Token: {$tokenInfo['token']}");
            
            if (isset($tokenInfo['expires_at'])) {
                $this->info("   Expires at: {$tokenInfo['expires_at']}");
            }
            if (isset($tokenInfo['issued_at'])) {
                $this->info("   Issued at: {$tokenInfo['issued_at']}");
            }
        } else {
            $this->error('   ❌ Failed to generate token');
            $this->warn("   Message: {$tokenInfo['message']}");
            return 1;
        }
        $this->newLine();

        // Test API call
        $this->info('3. Testing API call (get dosen)...');
        $dosenList = $apiService->getDosen();
        
        if (!empty($dosenList)) {
            $this->info('   ✅ API call successful');
            $this->info('   Total dosen: ' . count($dosenList));
            
            if (count($dosenList) > 0) {
                $this->info('   Sample dosen: ' . ($dosenList[0]['nama'] ?? 'N/A'));
            }
        } else {
            $this->error('   ❌ API call failed or returned empty data');
            return 1;
        }
        $this->newLine();

        // Test cache
        $this->info('4. Checking token cache...');
        $cachedToken = Cache::get('library_api_token');
        
        if ($cachedToken) {
            $this->info('   ✅ Token is cached');
            $this->info('   Cached token: ' . substr($cachedToken, 0, 20) . '...');
        } else {
            $this->warn('   ⚠️  Token not found in cache');
        }
        $this->newLine();

        // Test token refresh
        $this->info('5. Testing token refresh...');
        $refreshed = $apiService->refreshToken();
        
        if ($refreshed) {
            $this->info('   ✅ Token refresh successful');
        } else {
            $this->error('   ❌ Token refresh failed');
            return 1;
        }
        $this->newLine();

        $this->info('✅ All tests passed!');
        $this->info('Library API token auto-refresh is working correctly.');
        
        return 0;
    }
}
