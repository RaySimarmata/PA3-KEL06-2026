# Evaluasi Ambiguity dan RAGAS untuk AI Assistant

## Overview

Sistem evaluasi AI Assistant telah ditingkatkan dengan dua metrik evaluasi baru:

1. **Ambiguity Evaluation** - Mengukur kejelasan dan ketidakambiguan response AI
2. **RAGAS (Retrieval Augmented Generation Assessment)** - Mengukur kualitas sistem RAG

## 1. Ambiguity Evaluation

### Apa itu Ambiguity?

Ambiguity mengukur seberapa jelas dan tidak ambigu sebuah response AI. Response yang ambigu dapat memiliki multiple interpretations dan membingungkan user.

### Ambiguity Score (1-5)

- **1 - Very Clear**: Response sangat jelas, tidak ada ambiguitas
- **2 - Mostly Clear**: Response umumnya jelas dengan ambiguitas minor
- **3 - Moderate**: Response memiliki beberapa bagian yang ambigu
- **4 - Somewhat Ambiguous**: Response cukup ambigu, multiple interpretations possible
- **5 - Very Ambiguous**: Response sangat ambigu, meaning tidak jelas

### Cara Menggunakan

#### Manual Evaluation

```php
use App\Services\AIEvaluationService;

$evaluationService = app(AIEvaluationService::class);

// Evaluate dengan ambiguity score
$evaluationService->evaluateAIResponse(
    testId: $testId,
    relevance: 4,
    completeness: 4,
    clarity: 4,
    accuracy: 4,
    hasHallucination: false,
    ambiguityScore: 2, // Score 1-5
    ambiguousParts: ['phrase yang ambigu', 'kalimat tidak jelas'],
    notes: 'Response cukup jelas dengan minor ambiguity',
    evaluatedBy: 'Admin'
);
```

#### Automatic Evaluation dengan AI

```php
use App\Services\RAGASEvaluationService;

$ragasService = app(RAGASEvaluationService::class);

$result = $ragasService->evaluateAmbiguity($responseText);

// Result:
// [
//     'ambiguity_score' => 2,
//     'ambiguous_parts' => ['phrase1', 'phrase2'],
//     'explanation' => 'Brief explanation'
// ]
```

### Database Schema

```sql
-- ai_evaluation_tests table
ambiguity_score INT NULL,              -- Score 1-5
ambiguous_parts JSON NULL,             -- Array of ambiguous phrases
ambiguity_notes TEXT NULL,             -- Notes about ambiguity

-- ai_evaluation_results table
avg_ambiguity DECIMAL(5,2) NULL,       -- Average ambiguity score
high_ambiguity_count INT DEFAULT 0,    -- Count of high ambiguity cases (score >= 4)
```

## 2. RAGAS Evaluation

### Apa itu RAGAS?

RAGAS (Retrieval Augmented Generation Assessment) adalah framework untuk mengevaluasi kualitas sistem RAG (Retrieval Augmented Generation). RAGAS mengukur 5 metrik utama:

### RAGAS Metrics (0-1 scale)

#### 1. Faithfulness (0-1)
**Definisi**: Mengukur konsistensi faktual answer dengan context yang diberikan.

- **1.0**: Semua statements dalam answer fully supported by context
- **0.8**: Most statements supported dengan minor unsupported details
- **0.6**: Sekitar setengah statements supported
- **0.4**: Few statements supported
- **0.0**: Answer contradicts atau tidak supported by context

**Cara Meningkatkan**:
- Tambahkan instruction "stick to the context" dalam prompt
- Implementasi citation mechanism
- Gunakan fact-checking

#### 2. Answer Relevancy (0-1)
**Definisi**: Mengukur seberapa relevan answer terhadap question.

- **1.0**: Answer directly dan completely addresses question
- **0.8**: Answer addresses question dengan minor irrelevant details
- **0.6**: Answer partially addresses question
- **0.4**: Answer barely addresses question
- **0.0**: Answer completely irrelevant

**Cara Meningkatkan**:
- Improve query understanding
- Better prompt engineering
- Query expansion/reformulation

#### 3. Context Precision (0-1)
**Definisi**: Mengukur precision dari retrieved context (apakah semua context relevan?).

- **1.0**: All retrieved contexts are relevant
- **0.5**: Half of contexts are relevant
- **0.0**: No contexts are relevant

**Cara Meningkatkan**:
- Improve embedding quality
- Implement re-ranking
- Increase similarity threshold

#### 4. Context Recall (0-1)
**Definisi**: Mengukur recall dari retrieved context (apakah semua necessary info retrieved?).

- **1.0**: All necessary information retrieved
- **0.5**: Half of necessary information retrieved
- **0.0**: No necessary information retrieved

**Cara Meningkatkan**:
- Increase top_k parameter
- Lower similarity threshold
- Improve chunking strategy

#### 5. Context Relevancy (0-1)
**Definisi**: Mengukur relevance dari retrieved contexts terhadap query.

- **1.0**: All contexts highly relevant to query
- **0.5**: Half of contexts relevant
- **0.0**: No contexts relevant

**Cara Meningkatkan**:
- Improve embedding model
- Query expansion techniques
- Better chunking

### Cara Menggunakan RAGAS

#### Evaluate dengan RAGAS

```php
use App\Services\AIEvaluationService;

$evaluationService = app(AIEvaluationService::class);

// Create test dan evaluate dengan RAGAS
$test = $evaluationService->createAndEvaluateWithRAGAS([
    'test_name' => 'Test Laporan Semester',
    'feature' => 'semester',
    'query' => 'Apa poin positif dari kuesioner?',
    'actual_response' => 'Berdasarkan kuesioner, poin positif adalah...',
    'contexts' => [
        'Context chunk 1 dari RAG',
        'Context chunk 2 dari RAG',
        'Context chunk 3 dari RAG',
    ],
    'expected_response' => 'Ground truth (optional)',
]);

// Access RAGAS scores
echo "Faithfulness: " . $test->ragas_faithfulness . "\n";
echo "Answer Relevancy: " . $test->ragas_answer_relevancy . "\n";
echo "Context Precision: " . $test->ragas_context_precision . "\n";
echo "Context Recall: " . $test->ragas_context_recall . "\n";
echo "Context Relevancy: " . $test->ragas_context_relevancy . "\n";
echo "Overall Score: " . $test->ragas_overall_score . "\n";
echo "Quality Level: " . $test->ragas_quality_level . "\n";
```

#### Manual RAGAS Evaluation

```php
use App\Services\RAGASEvaluationService;

$ragasService = app(RAGASEvaluationService::class);

$metrics = $ragasService->evaluateRAGResponse(
    question: 'Apa poin positif dari kuesioner?',
    answer: 'Berdasarkan kuesioner, poin positif adalah...',
    contexts: [
        'Context chunk 1',
        'Context chunk 2',
    ],
    groundTruth: 'Expected answer (optional)'
);

// Result:
// [
//     'faithfulness' => 0.85,
//     'answer_relevancy' => 0.90,
//     'context_precision' => 0.75,
//     'context_recall' => 0.80,
//     'context_relevancy' => 0.88,
//     'overall_score' => 0.836
// ]
```

### Database Schema

```sql
-- ai_evaluation_tests table
ragas_faithfulness DECIMAL(5,4) NULL,
ragas_answer_relevancy DECIMAL(5,4) NULL,
ragas_context_precision DECIMAL(5,4) NULL,
ragas_context_recall DECIMAL(5,4) NULL,
ragas_context_relevancy DECIMAL(5,4) NULL,
ragas_overall_score DECIMAL(5,4) NULL,
rag_context_used JSON NULL,
rag_chunks_count INT NULL,
rag_avg_similarity DECIMAL(5,4) NULL,

-- ai_evaluation_results table
avg_ragas_faithfulness DECIMAL(5,4) NULL,
avg_ragas_answer_relevancy DECIMAL(5,4) NULL,
avg_ragas_context_precision DECIMAL(5,4) NULL,
avg_ragas_context_recall DECIMAL(5,4) NULL,
avg_ragas_context_relevancy DECIMAL(5,4) NULL,
avg_ragas_overall DECIMAL(5,4) NULL,
total_ragas_tests INT DEFAULT 0,
```

## 3. Viewing Results

### Halaman Evaluasi AI Assistant

Akses: `/gjm/model-evaluation`

Halaman ini menampilkan:

1. **Ambiguity Metrics Section**
   - Total tests
   - Average ambiguity score
   - High ambiguity cases count
   - Distribution by score (1-5)
   - Examples of ambiguous responses

2. **RAGAS Metrics Section**
   - Total tests
   - Overall RAGAS score
   - Breakdown of 5 RAGAS metrics
   - Quality distribution
   - Recommendations for improvement

### API Endpoint

```php
GET /gjm/model-evaluation/get-data?period=7days&feature=all

Response:
{
    "success": true,
    "data": {
        "ambiguity_metrics": {
            "has_data": true,
            "total_tests": 10,
            "avg_ambiguity": 2.3,
            "high_ambiguity_count": 2,
            "ambiguity_rate": 20.0,
            "distribution": {...},
            "quality_level": {...}
        },
        "ragas_metrics": {
            "has_data": true,
            "total_tests": 10,
            "overall_score": 0.836,
            "overall_percentage": 83.6,
            "metrics": {...},
            "quality_distribution": {...},
            "recommendations": [...]
        }
    }
}
```

## 4. Testing

### Run Migration

```bash
php artisan migrate
```

### Test RAGAS Evaluation

```bash
php artisan test:ragas-evaluation
```

Output akan menampilkan:
- Ambiguity evaluation result
- RAGAS metrics breakdown
- Created test case dengan scores

## 5. Best Practices

### Untuk Ambiguity

1. **Gunakan Structured Output**: Format response dengan clear structure
2. **Avoid Vague Terms**: Hindari kata-kata seperti "mungkin", "bisa jadi", "kemungkinan"
3. **Be Specific**: Berikan angka, fakta, dan details yang konkret
4. **Use Examples**: Tambahkan contoh untuk clarify meaning

### Untuk RAGAS

1. **Optimize Chunking**: Chunk size yang tepat (tidak terlalu besar/kecil)
2. **Quality Embeddings**: Gunakan embedding model yang baik
3. **Appropriate top_k**: Balance antara recall dan precision
4. **Re-ranking**: Implement re-ranking untuk improve precision
5. **Prompt Engineering**: Clear instructions untuk stick to context
6. **Monitor Metrics**: Track semua 5 metrics untuk identify bottlenecks

## 6. Troubleshooting

### Ambiguity Score Tinggi

**Problem**: Avg ambiguity score >= 3.5

**Solutions**:
- Review prompt templates
- Add more specific instructions
- Use structured output format
- Add examples in prompt

### Low RAGAS Scores

**Problem**: Overall RAGAS score < 0.6

**Solutions**:

- **Low Faithfulness**: Add "stick to context" instruction, implement citations
- **Low Answer Relevancy**: Improve query understanding, better prompts
- **Low Context Precision**: Improve embeddings, implement re-ranking
- **Low Context Recall**: Increase top_k, lower similarity threshold
- **Low Context Relevancy**: Better embedding model, query expansion

## 7. References

- RAGAS Framework: https://docs.ragas.io/
- Ambiguity in NLP: Research papers on clarity metrics
- RAG Best Practices: https://www.pinecone.io/learn/retrieval-augmented-generation/
