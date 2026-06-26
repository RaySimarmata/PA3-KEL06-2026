<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\APITokenManager;
use App\Services\ExternalAPIService;

class CheckAPIToken extends Command
{
    protected $signature = 'api:check-token {--refresh : Force refresh token}';
    protected $description = 'Check API token status and validity';

    public function handle()
    {
        $tokenManager = new APITokenManager();
        
        $this->info("=== API TOKEN STATUS ===");
        $this->newLine();

        // Get token info
        $info = $tokenManager->getTokenInfo();
        
        if (!$info) {
            $this->error("No token found in cache!");
            $this->newLine();
            
            if ($this->option('refresh')) {
                $this->info("Attempting to refresh token...");
                $token = $tokenManager->refreshToken();
                
                if ($token) {
                    $this->info("✓ Token refreshed successfully!");
                    $info = $tokenManager->getTokenInfo();
                } else {
                    $this->error("✗ Failed to refresh token!");
                    return 1;
                }
            } else {
                $this->comment("Run with --refresh to get a new token");
                return 1;
            }
        }

        // Display token info
        $this->table(
            ['Property', 'Value'],
            [
                ['Token (preview)', $info['token']],
                ['Issued At', $info['issued_at']],
                ['Expires At', $info['expires_at']],
                ['User ID', $info['user_id']],
                ['Is Valid', $info['is_valid'] ? '✓ Yes' : '✗ No'],
            ]
        );

        $this->newLine();

        // Test API connection
        $this->info("Testing API connection...");
        $apiService = new ExternalAPIService();
        
        if ($apiService->isAvailable()) {
            $this->info("✓ API connection successful!");
        } else {
            $this->error("✗ API connection failed!");
            
            if ($this->confirm('Try to refresh token?', true)) {
                $this->info("Refreshing token...");
                
                if ($apiService->refreshToken()) {
                    $this->info("✓ Token refreshed!");
                    
                    if ($apiService->isAvailable()) {
                        $this->info("✓ API connection successful after refresh!");
                    } else {
                        $this->error("✗ API still not available after refresh!");
                    }
                } else {
                    $this->error("✗ Failed to refresh token!");
                }
            }
        }

        return 0;
    }
}
