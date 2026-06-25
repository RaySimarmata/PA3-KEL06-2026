# PA3 Quick Fixes — Action Items Priority List

## 🔴 CRITICAL (Fix This Week)

### 1. AI Fallback Circuit Breaker
**Status**: ❌ Not Implemented  
**Time**: 4-6 hours  
**Files**:
- `app/Services/UnifiedAIService.php` → Add `CircuitBreaker` class
- `app/Services/ClaudeAIService.php` → Use circuit breaker in `callAI()`

**What to do**:
```bash
# Create circuit breaker service
touch app/Services/CircuitBreakerService.php

# Add to ClaudeAIService
private CircuitBreakerService $breaker;

public function ask($system, $user)
{
    return $this->breaker->call(fn() => $this->callAI($system, $user));
}
```

---

### 2. MongoDB Schema Versioning
**Status**: ❌ Not Implemented  
**Time**: 6-8 hours  
**Files**:
- `spark/spark_kuesioner.py` → Add `_schema_version` field
- New migration for MongoDB schema validation

**What to do**:
```python
# In spark_kuesioner.py
SCHEMA_VERSION = 2
df_hasil = df.withColumn('_schema_version', F.lit(SCHEMA_VERSION))
          .withColumn('_updated_at', F.current_timestamp())
```

---

### 3. Spark Job Queue Processing
**Status**: ❌ Blocking HTTP Call (Bad!)  
**Time**: 8-12 hours  
**Files**:
- Create `app/Jobs/ProcessKuesioneWithSpark.php`
- Modify `app/Http/Controllers/GJM/DashboardController.php`

**What to do**:
```bash
# Create job
php artisan make:job ProcessKuesioneWithSpark

# In DashboardController.jalankanAnalisisSpark()
ProcessKuesioneWithSpark::dispatch();
return back()->with('success', 'Job queued. Check status shortly.');
```

---

### 4. File Upload Validation
**Status**: ⚠️ Weak (content-type only)  
**Time**: 4-6 hours  
**Files**:
- `app/Http/Controllers/GKM/MonitoringKuesioneController.php`

**What to do**:
```php
// Add to store() method
$validated = $request->validate([
    'file_excel' => 'required|file|mimes:xlsx,xls,csv|max:10240'
]);

// Add content validation
if (!$this->isSafeFile($validated['file_excel'])) {
    return back()->with('error', 'Invalid file content');
}
```

---

### 5. Cache TTL Enforcement
**Status**: ❌ Missing  
**Time**: 6-8 hours  
**Files**:
- `app/Http/Controllers/GJM/DashboardController.php`
- `app/Http/Controllers/GKM/DashboardController.php`

**What to do**:
```php
// Use cache tags
Cache::tags(['dashboard_gjm'])->remember($key, 60, fn() => [...]);

// When data updates
Cache::tags(['dashboard_gjm'])->flush();
```

---

## 🟡 HIGH-PRIORITY (Fix Within 2 Weeks)

### 6. Error Handling & Logging
**Status**: ⚠️ Generic error messages  
**Time**: 8-10 hours  

**Add error codes**:
```php
const ERROR_SPARK_TIMEOUT = 'ERR_SPARK_001';
const ERROR_AI_429 = 'ERR_AI_429';

\Log::error('Job failed', [
    'error_code' => self::ERROR_SPARK_TIMEOUT,
    'error_id' => Str::uuid(),
    'duration_ms' => $duration
]);
```

---

### 7. RAG Threshold Configuration
**Status**: ❌ Hardcoded  
**Time**: 4-6 hours  

**Add to config/rag.php**:
```php
'similarity_thresholds' => [
    'kuesioner' => 0.7,
    'laporan' => 0.6,
    'reminder' => 0.5,
]
```

---

### 8. OCR Fault Tolerance
**Status**: ⚠️ No fallback  
**Time**: 12-16 hours  

**Add local OCR fallback**:
```php
// ImageContentValidationService
try {
    return $this->claudeVision($path); // Primary
} catch (\Exception $e) {
    return $this->localModel($path);  // Fallback
}
```

---

## 🟠 MEDIUM-PRIORITY (Fix Within 1 Month)

### 9. Secure API Keys
**Status**: 🔴 Hardcoded in docker-compose.yml  
**Time**: 2-3 hours  

```yaml
# Use Docker secrets
environment:
  LLM_API_KEY: ${LLM_API_KEY}

# Or in .env
LLM_API_KEY=your_key_here
```

---

### 10. Database Query Optimization
**Status**: ⚠️ Loading all data  
**Time**: 6-8 hours  

```php
// Use aggregation instead of loop
$stats = [
    'terlambat' => PerkuliahanMonitoringDetail::sum('jumlah_terlambat'),
];

// Add indexes
$table->index('prodi_kode');
$table->index(['prodi_kode', 'semester', 'tahun_ajaran']);
```

---

### 11. Automated Testing
**Status**: ❌ No tests  
**Time**: 20-30 hours  

```bash
php artisan make:test GKMDashboardTest
php artisan make:test KuesioneAnalysisTest
```

---

## MONITORING CHECKLIST

After fixes, monitor these:

```checklist
- [ ] AI response times < 5s (no timeout)
- [ ] Spark jobs complete without error
- [ ] Cache hit rate > 70%
- [ ] Error log entries < 10/day
- [ ] File upload validation working
- [ ] OCR fallback triggered < 2%
- [ ] Database queries < 100ms
- [ ] Queue jobs processed < 30s
```

---

## FILES TO REVIEW/UPDATE

### Phase 1 (This Week)
1. `app/Services/CircuitBreakerService.php` (NEW)
2. `app/Services/ClaudeAIService.php` (MODIFY)
3. `app/Jobs/ProcessKuesioneWithSpark.php` (NEW)
4. `spark/spark_kuesioner.py` (MODIFY)
5. `app/Http/Controllers/GKM/MonitoringKuesioneController.php` (MODIFY)
6. `app/Http/Controllers/GJM/DashboardController.php` (MODIFY)
7. `app/Http/Controllers/GKM/DashboardController.php` (MODIFY)

### Phase 2 (2 Weeks)
8. `app/Services/` - All services need error handling review
9. `config/rag.php` (NEW)
10. `app/Services/OCRService.php` (MODIFY)
11. `app/Services/ImageContentValidationService.php` (MODIFY)
12. `docker-compose.yml` (MODIFY)

### Phase 3 (1 Month)
13. `tests/` - Create full test suite
14. `database/migrations/` - Add indexes
15. API documentation (Swagger/OpenAPI)

---

## VERIFICATION STEPS

After each fix:

```bash
# 1. Check syntax
php -l app/Services/CircuitBreakerService.php

# 2. Run tests
php artisan test

# 3. Check logs
tail -f storage/logs/laravel.log

# 4. Monitor performance
php artisan tinker
> Illuminate\Support\Facades\Cache::get('...')

# 5. Database query log
DB::enableQueryLog(); // Check in queries after request
```

---

## SUCCESS CRITERIA

| Metric | Target | Status |
|--------|--------|--------|
| Code Score | 8+/10 | 🔴 Currently 7/10 |
| Error Handling | 8+/10 | 🔴 Currently 5/10 |
| Test Coverage | 70%+ | 🔴 Currently 0% |
| Cache Hits | >70% | ⚠️ Unknown |
| Spark Success Rate | 99%+ | ⚠️ Unknown |
| Response Time | <2s | ⚠️ Unknown |

---

**Generated**: 2026-06-15  
**Reviewer**: Kiro Agent  
**Next Review**: After fixes complete
