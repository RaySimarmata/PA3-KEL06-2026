# 🎯 Panduan Meningkatkan Faithfulness & Mengurangi Hallucination

## 📊 Status Saat Ini
- **Faithfulness:** 69.76% (❌ MERAH - Target: >80%)
- **Hallucination:** 30.24% (❌ MERAH - Target: <15%)
- **RAGAS Score:** 76.88%

## 🔴 Masalah Utama

### 1. System Prompt Terlalu Generic
**Lokasi:** `UnifiedAIService.php` line 501
```php
'content' => 'Anda adalah AI assistant yang membantu membuat laporan akademik...'
```

**Masalah:**
- Tidak ada instruksi eksplisit untuk HANYA menggunakan context yang diberikan
- Tidak ada larangan untuk mengarang fakta
- Tidak ada requirement untuk cite sumber

### 2. Tidak Ada Anti-Hallucination Measures
**Masalah:**
- AI bebas menambahkan informasi yang tidak ada di context
- Tidak ada verification step
- Tidak ada grounding ke dokumen sumber

### 3. Temperature Terlalu Tinggi
**Lokasi:** Multiple services
```php
'temperature' => 0.7  // Terlalu tinggi untuk factual content
```

**Efek:**
- AI lebih "kreatif" = lebih banyak hallucination
- Untuk factual reports, temperature seharusnya 0.2-0.4

## ✅ Solusi yang Harus Diimplementasikan

### SOLUSI 1: Enhanced System Prompt dengan Anti-Hallucination

**File:** `UnifiedAIService.php`
**Method:** `callOpenAICompatibleAPI()` dan `callOpenAICompatibleChatAPI()`

**Ganti system prompt dari:**
```php
'content' => 'Anda adalah AI assistant yang membantu membuat laporan akademik dalam bahasa Indonesia. Berikan jawaban yang terstruktur, profesional, dan relevan.'
```

**Menjadi:**
```php
'content' => 'Anda adalah AI Assistant untuk sistem pelaporan akademik Institut Teknologi Del.

ATURAN KETAT ANTI-HALLUCINATION:
1. HANYA gunakan informasi dari CONTEXT yang diberikan
2. JANGAN PERNAH mengarang, mengira-ngira, atau menambahkan informasi yang tidak ada di context
3. Jika informasi tidak tersedia di context, katakan "Data tidak tersedia" atau "Informasi tidak ditemukan dalam dokumen"
4. Setiap pernyataan faktual HARUS bisa dilacak ke context
5. Gunakan kutipan langsung dari context jika memungkinkan
6. Jika diminta membuat analisis, HANYA analisis berdasarkan data yang ADA di context

GAYA PENULISAN:
- Bahasa Indonesia formal dan profesional
- Struktur terorganisir dengan heading dan numbering
- Gunakan data spesifik dari context (angka, nama, tanggal)
- Hindari pernyataan umum atau generalisasi tanpa data pendukung

LARANGAN:
❌ Menambahkan fakta yang tidak ada di context
❌ Menggunakan pengetahuan umum untuk "melengkapi" data
❌ Membuat asumsi atau prediksi tanpa basis data
❌ Menyebutkan informasi yang "mungkin" atau "biasanya"'
```

### SOLUSI 2: Lower Temperature untuk Factual Content

**File:** Semua Service yang call AI (LaporanTriwulanService, LaporanKuesioneService, dll)

**Ganti dari:**
```php
'temperature' => 0.7
```

**Menjadi:**
```php
'temperature' => 0.3  // Lower = lebih faktual, kurang hallucination
```

**Catatan:**
- 0.0-0.3: Sangat faktual, deterministik (ideal untuk laporan)
- 0.4-0.6: Balanced
- 0.7-1.0: Kreatif (untuk content generation, bukan factual reports)

### SOLUSI 3: Enhanced Prompt Structure dengan Context Grounding

**File:** Semua Service AI (Triwulan, Kuesioner, Semester, VMTS, Bulanan)

**Format prompt yang HARUS digunakan:**
```php
$prompt = "CONTEXT DOKUMEN:
=================
{$contextData}
=================

INSTRUKSI:
Berdasarkan HANYA context dokumen di atas, {$userInstruction}

PENTING: 
- Jika data tidak ada di context, katakan 'Data tidak tersedia'
- Jangan menambahkan informasi dari pengetahuan umummu
- Cite sumber dengan format [Sumber: nama_dokumen]";
```

### SOLUSI 4: Add Verification Prompt Layer

**File:** Buat service baru `AIResponseVerificationService.php`

```php
<?php

namespace App\Services;

class AIResponseVerificationService
{
    protected $aiService;
    
    public function __construct(UnifiedAIService $aiService)
    {
        $this->aiService = $aiService;
    }
    
    /**
     * Verify AI response against context to check hallucination
     */
    public function verifyResponse(string $aiResponse, string $context): array
    {
        $verificationPrompt = "TUGAS: Verifikasi apakah AI response mengandung hallucination

CONTEXT ASLI:
{$context}

AI RESPONSE YANG PERLU DIVERIFIKASI:
{$aiResponse}

ANALISIS:
Periksa setiap pernyataan faktual dalam AI response. Apakah semua pernyataan bisa ditemukan di context?

OUTPUT FORMAT JSON:
{
  \"hallucination_detected\": true/false,
  \"hallucinated_claims\": [\"list of claims not found in context\"],
  \"faithfulness_score\": 0.0-1.0,
  \"verification_notes\": \"explanation\"
}

Berikan HANYA JSON, tanpa text tambahan.";

        $result = $this->aiService->generateChat([
            ['role' => 'system', 'content' => 'Anda adalah AI verifier yang mendeteksi hallucination.'],
            ['role' => 'user', 'content' => $verificationPrompt]
        ], ['temperature' => 0.1, 'max_tokens' => 1024]);
        
        if ($result['success']) {
            $verification = json_decode($result['text'], true);
            return $verification ?? ['hallucination_detected' => false];
        }
        
        return ['hallucination_detected' => false];
    }
}
```

### SOLUSI 5: Implement RAG Context Quality Check

**File:** `RAGRetrievalService.php`

**Tambahkan method:**
```php
/**
 * Score retrieved chunks by relevance to ensure high quality context
 */
public function scoreAndFilterChunks(array $chunks, string $query, float $minScore = 0.7): array
{
    $scored = [];
    
    foreach ($chunks as $chunk) {
        $relevanceScore = $this->calculateRelevance($chunk['content'], $query);
        
        if ($relevanceScore >= $minScore) {
            $chunk['relevance_score'] = $relevanceScore;
            $scored[] = $chunk;
        }
    }
    
    // Sort by relevance
    usort($scored, function($a, $b) {
        return $b['relevance_score'] <=> $a['relevance_score'];
    });
    
    return $scored;
}

private function calculateRelevance(string $content, string $query): float
{
    // Simple relevance calculation (can be enhanced with AI)
    $queryWords = explode(' ', strtolower($query));
    $contentLower = strtolower($content);
    
    $matches = 0;
    foreach ($queryWords as $word) {
        if (strlen($word) > 3 && strpos($contentLower, $word) !== false) {
            $matches++;
        }
    }
    
    return min(1.0, $matches / count($queryWords));
}
```

## 📋 Implementation Checklist

### High Priority (Implement Immediately)
- [ ] **Update UnifiedAIService system prompt** with anti-hallucination rules
- [ ] **Lower temperature to 0.3** in all AI generation calls
- [ ] **Add context grounding** to all prompts (wrap context clearly)
- [ ] **Test dengan 5-10 prompts** dan ukur improvement

### Medium Priority (Week 1)
- [ ] Create `AIResponseVerificationService`
- [ ] Integrate verification dalam `LaporanKuesioneService`
- [ ] Add relevance scoring in `RAGRetrievalService`
- [ ] Update all service prompts dengan format baru

### Low Priority (Week 2)
- [ ] Implement citation mechanism [Sumber: dokumen]
- [ ] Add confidence scores in AI responses
- [ ] Create monitoring dashboard untuk hallucination rate
- [ ] A/B testing different prompt strategies

## 🎯 Target Metrics Setelah Implementation

| Metric | Saat Ini | Target | Cara Mencapai |
|--------|----------|--------|---------------|
| Faithfulness | 69.76% | **>85%** | Lower temp + anti-hallucination prompt |
| Hallucination | 30.24% | **<12%** | Context grounding + verification |
| Context Precision | 79.89% | **>85%** | Better relevance scoring |
| Context Recall | 86.48% | **Maintain** | Already good |
| RAGAS Score | 76.88% | **>82%** | Combined improvements |

## 📝 Testing Procedure

### 1. Baseline Test (Before Changes)
```bash
php artisan ragas:report --save-baseline
```

### 2. Implement Changes
- Update UnifiedAIService
- Update temperature
- Update prompts

### 3. Generate Test Laporan
```bash
# Test dengan prompt yang sama seperti baseline
php artisan test:ai-generation --category=triwulan
php artisan test:ai-generation --category=kuesioner
```

### 4. Run RAGAS Evaluation
```bash
php artisan ragas:evaluate --compare-with-baseline
```

### 5. Analyze Results
```bash
php artisan ragas:report --show-comparison
```

## 🚨 Common Pitfalls to Avoid

1. **DON'T** make temperature TOO low (0.0-0.1)
   - Sweet spot: 0.2-0.4 for factual content
   
2. **DON'T** make context TOO verbose
   - Keep context relevant and concise
   - Use only top-K most relevant chunks (K=5-10)
   
3. **DON'T** forget to test incrementally
   - Test each change separately
   - Measure impact before next change
   
4. **DON'T** over-constrain the AI
   - Allow some natural language variation
   - Focus on factual accuracy, not rigid templates

## 📚 References

- RAGAS Paper: https://arxiv.org/abs/2309.15217
- Anti-Hallucination Techniques: https://arxiv.org/abs/2311.08401
- Prompt Engineering Guide: https://www.promptingguide.ai/

## 🔧 Quick Wins (Implement in 30 minutes)

1. **Edit `UnifiedAIService.php` line 501-505**
   - Replace system prompt dengan anti-hallucination version
   
2. **Edit `UnifiedAIService.php` line 518 dan 645**
   - Change `'temperature' => 0.7` to `'temperature' => 0.3`
   
3. **Test immediately:**
   ```bash
   # Generate 1 laporan dan check hasilnya
   # Visit GJM > Buat Laporan > Triwulan
   # Bandingkan dengan laporan sebelumnya
   ```

4. **Run RAGAS evaluation:**
   ```bash
   php artisan ragas:sync-cache
   # Check halaman Evaluasi RAGAS untuk melihat improvement
   ```

---

**Expected Impact:**
- Faithfulness: **69.76% → 82-88%** (+12-18%)
- Hallucination: **30.24% → 12-18%** (-12-18%)  
- RAGAS Score: **76.88% → 83-87%** (+6-10%)

**Time to implement:** 1-2 hours for quick wins, 1 week for full implementation
