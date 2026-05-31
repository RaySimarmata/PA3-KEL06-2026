<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class APITokenManager
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $cacheKey = 'api_token';
    protected $cacheExpiry = 3000; // 50 minutes (token valid for 1 hour)

    public function __construct()
    {
        $this->baseUrl = env('LIBRARY_API_URL', 'https://cis.del.ac.id/api');
        $this->username = env('LIBRARY_API_USERNAME');
        $this->password = env('LIBRARY_API_PASSWORD');
    }

    /**
     * Get valid token (from cache or refresh if needed)
     * 
     * @return string|null
     */
    public function getToken()
    {
        // Try to get token from cache
        $token = Cache::get($this->cacheKey);
        
        if ($token && $this->isTokenValid($token)) {
            return $token;
        }
        
        // Token expired or not found, get new token
        return $this->refreshToken();
    }

    /**
     * Refresh token from API
     * 
     * @return string|null
     */
    public function refreshToken()
    {
        try {
            // If username/password not configured, use static token from .env
            if (!$this->username || !$this->password) {
                $staticToken = env('API_TOKEN');
                
                if ($staticToken) {
                    Log::info('Using static API token from .env');
                    Cache::put($this->cacheKey, $staticToken, $this->cacheExpiry);
                    return $staticToken;
                }
                
                Log::warning('No API credentials configured');
                return null;
            }

            Log::info('Refreshing API token...');
            
            // Call login endpoint to get new token
            $response = Http::timeout(10)
                ->post($this->baseUrl . '/auth/login', [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['token'] ?? $data['access_token'] ?? null;
                
                if ($token) {
                    // Cache the new token
                    Cache::put($this->cacheKey, $token, $this->cacheExpiry);
                    
                    Log::info('API token refreshed successfully');
                    return $token;
                }
            }

            Log::error('Failed to refresh API token', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception while refreshing API token', [
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Check if token is still valid
     * 
     * @param string $token
     * @return bool
     */
    protected function isTokenValid($token)
    {
        if (!$token) {
            return false;
        }

        // Decode JWT to check expiry
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return false;
            }

            $payload = json_decode(base64_decode($parts[1]), true);
            
            if (!isset($payload['exp'])) {
                // No expiry, assume valid
                return true;
            }

            // Check if token expires in next 5 minutes
            $expiresAt = $payload['exp'];
            $now = time();
            
            return ($expiresAt - $now) > 300; // 5 minutes buffer

        } catch (\Exception $e) {
            Log::warning('Failed to validate token', [
                'message' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Force clear token cache
     * 
     * @return void
     */
    public function clearToken()
    {
        Cache::forget($this->cacheKey);
        Log::info('API token cache cleared');
    }

    /**
     * Get token info
     * 
     * @return array|null
     */
    public function getTokenInfo()
    {
        $token = Cache::get($this->cacheKey);
        
        if (!$token) {
            return null;
        }

        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode($parts[1]), true);
            
            return [
                'token' => substr($token, 0, 20) . '...',
                'expires_at' => isset($payload['exp']) ? date('Y-m-d H:i:s', $payload['exp']) : 'No expiry',
                'issued_at' => isset($payload['iat']) ? date('Y-m-d H:i:s', $payload['iat']) : 'Unknown',
                'user_id' => $payload['uid'] ?? 'Unknown',
                'is_valid' => $this->isTokenValid($token),
                'cached_until' => Cache::get($this->cacheKey . '_expires') 
                    ? date('Y-m-d H:i:s', Cache::get($this->cacheKey . '_expires')) 
                    : 'Unknown'
            ];

        } catch (\Exception $e) {
            return null;
        }
    }
}
