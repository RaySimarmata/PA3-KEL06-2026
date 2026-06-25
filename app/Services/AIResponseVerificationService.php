<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * AI Response Verification Service
 * 
 * Service untuk memverifikasi AI response terhadap hallucination
 * dengan membandingkan response dengan context asli
 */
class AIResponseVerificationService
{
    protected $aiService;
    
    public function __construct(UnifiedAIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    /**
     * Verify AI response against context to detect hallucination
     * 
     * @param string $aiResponse AI-generated response to verify
     * @param string $context Original context/documents used
     * @return array Verification result with hallucination detection
     */
    public function verifyResponse(string $aiResponse, string $context): array
    {
        try {
            Log::info("=== AI Response Verification Started ===");
            
            // Truncate if too long to fit in verification prompt
            $contextPreview = strlen($context) > 8000 ? substr($context, 0, 8000) . '...[truncated]' : $context;
            $responsePreview = strlen($aiResponse) > 8000 ? substr($aiResponse, 0, 8000) . '...[truncated]' : $aiResponse;
            
            $verificationPrompt = "TUGAS: Verifikasi apakah AI response mengandung hallucination atau informasi yang tidak ada di context.

CONTEXT ASLI YANG DIBERIKAN:
=============================
{$contextPreview}

AI RESPONSE YANG PERLU DIVERIFIKASI:
====================================
{$responsePreview}

INSTRUKSI ANALISIS:
1. Periksa SETIAP pernyataan faktual dalam AI response
2. Cek apakah pernyataan tersebut ada di context atau bisa disimpulkan dari context
3. Identifikasi pernyataan yang mengarang atau tidak ada dasarnya di context
4. Berikan skor faithfulness (0.0-1.0):
   - 1.0 = Semua pernyataan akurat dan bisa dilacak ke context
   - 0.5 = Sebagian pernyataan tidak ada di context
   - 0.0 = Banyak pernyataan mengarang/hallucination

OUTPUT FORMAT JSON (HANYA JSON, TANPA TEXT LAIN):
{
  \"hallucination_detected\": true/false,
  \"hallucinated_claims\": [\"list klaim yang tidak ada di context\"],
  \"faithfulness_score\": 0.85,
  \"verification_notes\": \"Penjelasan singkat hasil verifikasi\",
  \"grounded_claims_count\": 10,
  \"total_claims_count\": 12
}";

            $result = $this->aiService->generateChat([
                [
                    'role' => 'system', 
                    'content' => 'Anda adalah AI verifier yang ahli mendeteksi hallucination. Tugas Anda adalah membandingkan AI response dengan context dan mendeteksi informasi yang tidak ada dasarnya. Berikan output HANYA JSON valid.'
                ],
                [
                    'role' => 'user', 
                    'content' => $verificationPrompt
                ]
            ], [
                'temperature' => 0.1,  // Very low for consistent verification
                'max_tokens' => 1024
            ]);
            
            if ($result['success']) {
                $text = trim($result['text']);
                
                // Extract JSON from response (handle cases where AI adds extra text)
                if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
                    $jsonText = $matches[0];
                    $verification = json_decode($jsonText, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        Log::info("Verification completed", [
                            'hallucination_detected' => $verification['hallucination_detected'] ?? false,
                            'faithfulness_score' => $verification['faithfulness_score'] ?? 'N/A'
                        ]);
                        
                        return $verification;
                    }
                }
                
                Log::warning("Failed to parse verification JSON", ['response' => substr($text, 0, 200)]);
            }
            
            // Fallback if verification fails
            return [
                'hallucination_detected' => false,
                'hallucinated_claims' => [],
                'faithfulness_score' => null,
                'verification_notes' => 'Verifikasi otomatis gagal',
                'grounded_claims_count' => null,
                'total_claims_count' => null,
                'error' => 'Verification service unavailable'
            ];
            
        } catch (\Exception $e) {
            Log::error("Verification error", [
                'error' => $e->getMessage()
            ]);
            
            return [
                'hallucination_detected' => false,
                'hallucinated_claims' => [],
                'faithfulness_score' => null,
                'verification_notes' => 'Error during verification',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Quick check if response likely contains hallucination
     * Based on heuristics (faster than full verification)
     * 
     * @param string $aiResponse AI response to check
     * @param string $context Original context
     * @return array Quick check result
     */
    public function quickCheck(string $aiResponse, string $context): array
    {
        $issues = [];
        $score = 1.0;
        
        // Check 1: Response is much longer than context (might be adding info)
        $contextLength = strlen($context);
        $responseLength = strlen($aiResponse);
        
        if ($responseLength > $contextLength * 1.5) {
            $issues[] = 'Response significantly longer than context (possible elaboration)';
            $score -= 0.1;
        }
        
        // Check 2: Contains phrases that indicate uncertainty or assumptions
        $uncertainPhrases = [
            'mungkin', 'kemungkinan', 'biasanya', 'umumnya', 
            'diasumsikan', 'diperkirakan', 'rata-rata', 'kira-kira'
        ];
        
        foreach ($uncertainPhrases as $phrase) {
            if (stripos($aiResponse, $phrase) !== false) {
                $issues[] = "Contains uncertain phrase: '{$phrase}'";
                $score -= 0.05;
            }
        }
        
        // Check 3: Contains specific numbers not in context
        preg_match_all('/\d+[.,]?\d*%?/', $aiResponse, $responseNumbers);
        preg_match_all('/\d+[.,]?\d*%?/', $context, $contextNumbers);
        
        $responseNumSet = array_unique($responseNumbers[0] ?? []);
        $contextNumSet = array_unique($contextNumbers[0] ?? []);
        
        $unmatchedNumbers = array_diff($responseNumSet, $contextNumSet);
        if (count($unmatchedNumbers) > 3) {
            $issues[] = 'Many numbers in response not found in context';
            $score -= 0.15;
        }
        
        $score = max(0.0, min(1.0, $score));
        
        return [
            'quick_check_score' => round($score, 2),
            'potential_issues' => $issues,
            'recommendation' => $score < 0.7 ? 'Run full verification' : 'Likely OK'
        ];
    }
    
    /**
     * Calculate faithfulness score manually (simple version)
     * By checking what percentage of response content exists in context
     * 
     * @param string $aiResponse AI response
     * @param string $context Original context
     * @return float Faithfulness score 0.0-1.0
     */
    public function calculateSimpleFaithfulness(string $aiResponse, string $context): float
    {
        // Tokenize response into sentences
        $sentences = preg_split('/[.!?]+/', $aiResponse, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_map('trim', $sentences);
        $sentences = array_filter($sentences, fn($s) => strlen($s) > 10);
        
        if (empty($sentences)) {
            return 1.0;
        }
        
        $groundedCount = 0;
        $contextLower = strtolower($context);
        
        foreach ($sentences as $sentence) {
            $sentenceLower = strtolower($sentence);
            
            // Extract key phrases (3+ word phrases)
            $words = explode(' ', $sentenceLower);
            $found = false;
            
            // Check if sentence or significant parts exist in context
            if (strlen($sentence) > 20) {
                // For longer sentences, check if at least 50% of words are in context
                $wordCount = count($words);
                $foundWords = 0;
                
                foreach ($words as $word) {
                    if (strlen($word) > 3 && stripos($contextLower, $word) !== false) {
                        $foundWords++;
                    }
                }
                
                if ($foundWords / $wordCount >= 0.5) {
                    $found = true;
                }
            }
            
            if ($found) {
                $groundedCount++;
            }
        }
        
        $faithfulness = $groundedCount / count($sentences);
        
        return round($faithfulness, 2);
    }
}
