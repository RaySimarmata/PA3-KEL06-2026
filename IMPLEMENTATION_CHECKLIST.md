# PA3 Implementation Checklist — Fix Tracker

**Project**: PA3-KEL06-2026  
**Start Date**: 2026-06-15  
**Target Completion**: 2026-07-15  
**Total Effort**: 110-140 hours

---

## 🔴 PHASE 1: CRITICAL FIXES (Week 1) — 28-40 hours

### Task 1: Circuit Breaker for AI Fallback
- [ ] **1.1** Create `app/Services/CircuitBreakerService.php` (1-2h)
  - [ ] Implement state machine (closed → open → half-open)
  - [ ] Add failure threshold (5 failures)
  - [ ] Add success threshold (2 successes to close)
  - [ ] Add timeout (60 seconds)
  - [ ] Add metrics logging

- [ ] **1.2** Update `app/Services/ClaudeAIService.php` (2-3h)
  - [ ] Inject CircuitBreaker dependency
  - [ ] Wrap `callAI()` with circuit breaker
  - [ ] Test with mock AI failures
  
- [ ] **1.3** Update `app/Services/UnifiedAIService.php` (1-2h)
  - [ ] Use circuit breaker per provider
  - [ ] Add exponential backoff (60s, 300s, 900s)
  - [ ] Log fallback events

- [ ] **1.4** Testing & Validation (1h)
  - [ ] Unit test: circuit breaker opens after 5 failures
  - [ ] Unit test: circuit breaker resets after timeout
  - [ ] Integration test: AI call with circuit breaker

**Files Modified**: 3  
**Tests Added**: 3+  
**Estimated Hours**: 4-6  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

### Task 2: MongoDB Schema Versioning
- [ ] **2.1** Update `spark/spark_kuesioner.py` (2-3h)
  - [ ] Define SCHEMA_VERSION = 2
  - [ ] Add _schema_version to all inserts
  - [ ] Add _updated_at timestamp
  - [ ] Add _created_by metadata
  - [ ] Add data validation before insert

- [ ] **2.2** Create migration validation (1-2h)
  - [ ] Create script to validate old data
  - [ ] Create script to migrate old → new schema
  - [ ] Test on staging data

- [ ] **2.3** Update AIAgentService to handle versioning (1h)
  - [ ] Handle both v1 and v2 data
  - [ ] Log version mismatch warnings

- [ ] **2.4** Testing & Validation (1h)
  - [ ] Run Spark job, verify _schema_version field
  - [ ] Verify timestamp format
  - [ ] Check MongoDB documents for version

**Files Modified**: 2  
**New Migrations**: 1  
**Estimated Hours**: 6-8  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

### Task 3: Spark Job Queue Processing
- [ ] **3.1** Create Laravel Job class (2-3h)
  - [ ] File: `app/Jobs/ProcessKuesioneWithSpark.php`
  - [ ] Implement `handle()` with Process class
  - [ ] Add timeout (300s)
  - [ ] Add max retries (3)
  - [ ] Add exponential backoff
  - [ ] Implement cleanup on failure

- [ ] **3.2** Update DashboardController (1-2h)
  - [ ] File: `app/Http/Controllers/GJM/DashboardController.php`
  - [ ] Change `jalankanAnalisisSpark()` to dispatch job
  - [ ] Return immediate response
  - [ ] Add job status endpoint

- [ ] **3.3** Create job status tracking (2h)
  - [ ] Create `JobStatusController`
  - [ ] Add endpoint: `GET /api/jobs/{jobId}/status`
  - [ ] Return: status, progress, error message

- [ ] **3.4** Add notifications (1h)
  - [ ] Email notification on job complete
  - [ ] Toast notification on job error
  - [ ] Dashboard refresh on job complete

- [ ] **3.5** Testing & Validation (1-2h)
  - [ ] Test job queue processing
  - [ ] Test retry on timeout
  - [ ] Test cleanup on failure
  - [ ] Test status endpoint

**Files Created**: 2  
**Files Modified**: 2  
**Estimated Hours**: 8-12  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

### Task 4: File Upload Validation
- [ ] **4.1** Harden upload validation (2-3h)
  - [ ] File: `app/Http/Controllers/GKM/MonitoringKuesioneController.php`
  - [ ] Add MIME type whitelist
  - [ ] Add file size limits (10MB)
  - [ ] Add extension whitelist (.xlsx, .xls, .csv)
  - [ ] Check magic bytes (file signature)

- [ ] **4.2** Add content scanning (1-2h)
  - [ ] Create `app/Services/FileScannerService.php`
  - [ ] Scan for dangerous patterns
  - [ ] Check for embedded scripts
  - [ ] Validate Excel structure

- [ ] **4.3** Create validation helper (1h)
  - [ ] Function: `isSafeFile($file)`
  - [ ] Function: `isValidExcel($file)`
  - [ ] Function: `validateMimeType($file)`

- [ ] **4.4** Testing & Validation (1h)
  - [ ] Test valid Excel files (pass)
  - [ ] Test invalid MIME types (reject)
  - [ ] Test with embedded scripts (reject)
  - [ ] Test with large files (reject)

**Files Created**: 1  
**Files Modified**: 1  
**Estimated Hours**: 4-6  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

### Task 5: Cache TTL & Invalidation
- [ ] **5.1** Implement cache tags (2-3h)
  - [ ] File: `app/Http/Controllers/GJM/DashboardController.php`
  - [ ] Replace `Cache::put()` with `Cache::tags()->remember()`
  - [ ] Use tags: 'dashboard_gjm', 'analytics_gkm'
  - [ ] Set consistent TTL (30 minutes)

- [ ] **5.2** Add model event listeners (1-2h)
  - [ ] Listen to `HasilAnalisisMongo::updated`
  - [ ] Flush related cache tags
  - [ ] Log cache invalidation

- [ ] **5.3** Create cache warming (1h)
  - [ ] Pre-populate cache on app start
  - [ ] Warm cache after data import
  - [ ] Periodic cache refresh (scheduler)

- [ ] **5.4** Testing & Validation (1-2h)
  - [ ] Test cache hit rate
  - [ ] Test cache invalidation on update
  - [ ] Test cache warming

**Files Modified**: 3+  
**Estimated Hours**: 6-8  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

## 🟡 PHASE 2: HIGH-PRIORITY FIXES (Weeks 2-3) — 24-32 hours

### Task 6: Error Handling & Logging
- [ ] **6.1** Define error codes (1h)
  - [ ] File: `app/Enums/ErrorCode.php`
  - [ ] Define constants for all error types
  - [ ] Document each error code

- [ ] **6.2** Create error handler middleware (2-3h)
  - [ ] File: `app/Http/Middleware/ErrorHandler.php`
  - [ ] Catch all exceptions
  - [ ] Log with error code + error ID
  - [ ] Return user-friendly messages

- [ ] **6.3** Update service error handling (3-4h)
  - [ ] All services should catch + throw with ErrorCode
  - [ ] All controllers should use error middleware
  - [ ] Remove generic error messages

- [ ] **6.4** Structured logging (1-2h)
  - [ ] Create LogFormatter for consistency
  - [ ] Log: error_code, error_id, duration, context
  - [ ] Setup log rotation

- [ ] **6.5** Testing & Validation (1-2h)
  - [ ] Test error codes unique
  - [ ] Test error logging format
  - [ ] Test user messages sanitized

**Files Created**: 2  
**Files Modified**: 10+  
**Estimated Hours**: 8-10  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

### Task 7: RAG Configuration
- [ ] **7.1** Create configuration (1h)
  - [ ] File: `config/rag.php`
  - [ ] Define similarity thresholds per collection
  - [ ] Define chunk sizes per type
  - [ ] Define max results per query

- [ ] **7.2** Update VectorDatabaseService (1-2h)
  - [ ] Use config values instead of hardcoded
  - [ ] Make threshold configurable per query
  - [ ] Log threshold used for debugging

- [ ] **7.3** Create tuning dashboard (2-3h)
  - [ ] View to display threshold values
  - [ ] Form to adjust thresholds (admin only)
  - [ ] Metrics: precision, recall, F1 score

- [ ] **7.4** Testing & Validation (1h)
  - [ ] Test different thresholds
  - [ ] Measure precision/recall
  - [ ] Document optimal values per collection

**Files Created**: 1  
**Files Modified**: 2  
**Estimated Hours**: 4-6  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

### Task 8: OCR Fault Tolerance
- [ ] **8.1** Add local OCR fallback (4-6h)
  - [ ] Setup TensorFlow/local model
  - [ ] Create `app/Services/LocalOCRService.php`
  - [ ] Implement fallback in OCRService
  - [ ] Test with sample images

- [ ] **8.2** Batch OCR processing (2-3h)
  - [ ] Create `app/Jobs/BatchOCRJob`
  - [ ] Process images in parallel
  - [ ] Update via database events

- [ ] **8.3** Text deduplication (1-2h)
  - [ ] Remove duplicate extracted text
  - [ ] Normalize whitespace
  - [ ] Deduplicate before indexing

- [ ] **8.4** Testing & Validation (2-3h)
  - [ ] Test Claude Vision failure → fallback
  - [ ] Test batch processing performance
  - [ ] Test deduplication logic

**Files Created**: 2  
**Files Modified**: 3  
**Estimated Hours**: 12-16  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

## 🟠 PHASE 3: MEDIUM-PRIORITY FIXES (Week 4) — 28-41 hours

### Task 9: Security Hardening
- [ ] **9.1** Move API keys to Docker secrets (1-2h)
  - [ ] File: `docker-compose.yml`
  - [ ] Create `.env.example`
  - [ ] Move keys to Docker secrets
  - [ ] Update `.gitignore`

- [ ] **9.2** Rate limiting (2-3h)
  - [ ] Add rate limiter middleware
  - [ ] Limit AI endpoints: 10 req/minute
  - [ ] Limit upload endpoint: 5 req/minute
  - [ ] Return 429 on limit exceeded

- [ ] **9.3** API authentication (1-2h)
  - [ ] Add API token validation
  - [ ] Implement token rotation
  - [ ] Log auth failures

- [ ] **9.4** Testing & Validation (1h)
  - [ ] Test Docker secrets loading
  - [ ] Test rate limiting
  - [ ] Test API token validation

**Files Modified**: 3  
**Estimated Hours**: 2-3  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

### Task 10: Database Optimization
- [ ] **10.1** Create database indexes (1-2h)
  - [ ] Add indexes to frequently filtered columns
  - [ ] Add composite indexes
  - [ ] Document index strategy

- [ ] **10.2** Query optimization (2-3h)
  - [ ] Review slow queries in controllers
  - [ ] Use aggregation instead of loops
  - [ ] Use select() to limit columns
  - [ ] Add eager loading (with())

- [ ] **10.3** Explain & analyze (1h)
  - [ ] Run EXPLAIN on top queries
  - [ ] Document query plans
  - [ ] Identify remaining bottlenecks

- [ ] **10.4** Testing & Validation (1-2h)
  - [ ] Benchmark before/after
  - [ ] Measure query time improvement
  - [ ] Monitor on production

**Files Modified**: 10+  
**New Migrations**: 1  
**Estimated Hours**: 6-8  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

### Task 11: Automated Testing
- [ ] **11.1** Setup test infrastructure (2-3h)
  - [ ] Configure PHPUnit
  - [ ] Setup test database
  - [ ] Create test factories/seeders
  - [ ] Create mock AI responses

- [ ] **11.2** Write feature tests (10-15h)
  - [ ] Dashboard tests (filtering, caching)
  - [ ] Auth tests (login, logout, roles)
  - [ ] Upload tests (validation, processing)
  - [ ] API tests (responses, errors)
  - [ ] Job tests (queue, retry, cleanup)

- [ ] **11.3** Write unit tests (5-8h)
  - [ ] Service tests (AIAgentService, RAGService)
  - [ ] Utility tests (validators, helpers)
  - [ ] Model tests (relationships, scopes)

- [ ] **11.4** Setup CI/CD (3-5h)
  - [ ] GitHub Actions for test on push
  - [ ] Code coverage reporting
  - [ ] Automated deployment on pass

- [ ] **11.5** Testing & Documentation (2-3h)
  - [ ] Achieve 70%+ code coverage
  - [ ] Document test patterns
  - [ ] Create testing guide

**Files Created**: 20+  
**Estimated Hours**: 20-30  
**Assigned To**: _________  
**Status**: ⬜ Not Started

---

## 📋 SUMMARY TRACKER

### Week 1: Critical Fixes
| Task | Hours | Status | Assignee | Due Date |
|------|-------|--------|----------|----------|
| 1. Circuit Breaker | 4-6 | ⬜ | | 2026-06-19 |
| 2. Schema Version | 6-8 | ⬜ | | 2026-06-20 |
| 3. Spark Queue | 8-12 | ⬜ | | 2026-06-21 |
| 4. File Validation | 4-6 | ⬜ | | 2026-06-21 |
| 5. Cache Invalidation | 6-8 | ⬜ | | 2026-06-22 |
| **TOTAL** | **28-40** | | | |

### Week 2-3: High-Priority Fixes
| Task | Hours | Status | Assignee | Due Date |
|------|-------|--------|----------|----------|
| 6. Error Handling | 8-10 | ⬜ | | 2026-06-29 |
| 7. RAG Config | 4-6 | ⬜ | | 2026-06-27 |
| 8. OCR Fallback | 12-16 | ⬜ | | 2026-07-03 |
| **TOTAL** | **24-32** | | | |

### Week 4: Medium-Priority Fixes
| Task | Hours | Status | Assignee | Due Date |
|------|-------|--------|----------|----------|
| 9. Security | 2-3 | ⬜ | | 2026-07-08 |
| 10. DB Optimization | 6-8 | ⬜ | | 2026-07-09 |
| 11. Testing | 20-30 | ⬜ | | 2026-07-15 |
| **TOTAL** | **28-41** | | | |

### Grand Total
- **Total Hours**: 110-140
- **Total Tasks**: 11
- **Timeline**: 4 weeks
- **Team Size**: 3-4 developers recommended

---

## ✅ COMPLETION CRITERIA

Each task is complete when:
1. All sub-items checked
2. Code passes local tests
3. Code reviewed by 2nd developer
4. Merged to develop branch
5. Verified in staging environment

---

## 📊 PROGRESS TRACKING

**Week 1**: [ ] 0% → Target: 100%
**Week 2-3**: [ ] 0% → Target: 100%
**Week 4**: [ ] 0% → Target: 100%

---

**Generated**: 2026-06-15  
**Owner**: _________  
**Last Updated**: _________  
**Next Review**: Weekly (Mondays)
