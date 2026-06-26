# 🚀 Setup & Deployment Guide - AI Assistant Sync System

## 📋 Pre-Deployment Checklist

- [ ] Database backed up
- [ ] Code changes reviewed
- [ ] All files updated:
  - [ ] Migration file created
  - [ ] Model updated
  - [ ] Controller updated
  - [ ] Routes updated
  - [ ] Views updated
  - [ ] JavaScript updated

---

## 🔧 Step-by-Step Setup

### Step 1: Update Database

**Option A: Fresh Migration (Recommended for Development)**
```bash
# Backup existing data first!
mysqldump -u root -p cobaaaa > backup_cobaaaa.sql

# Run fresh migration with seed
php artisan migrate:fresh --seed
```

**Option B: Incremental Migration (For Production)**
```bash
# Only run new migration (if migration runner has issues)
php artisan migrate:refresh --only 2026_06_10_000001_add_ai_preview_to_laporan_gkm_table
```

**Option C: Manual SQL (If migration fails)**
```sql
ALTER TABLE laporan_gkm ADD COLUMN (
    ai_preview_draft LONGTEXT NULL COMMENT 'Draft laporan hasil chat dengan AI',
    ai_sections JSON NULL COMMENT 'Struktur sections dari markdown',
    ai_preview_updated_at TIMESTAMP NULL COMMENT 'Waktu terakhir AI preview diupdate',
    ai_preview_used_for_generation TINYINT(1) DEFAULT 0 COMMENT 'Apakah laporan sudah di-generate dari preview terbaru'
);
```

---

### Step 2: Verify Database Changes

```bash
# Check columns were added
php artisan tinker

# Inside tinker:
>>> use App\Models\LaporanGKM;
>>> $columns = \DB::getSchemaBuilder()->getColumnListing('laporan_gkm');
>>> in_array('ai_preview_draft', $columns) ? 'OK' : 'MISSING';
>>> in_array('ai_sections', $columns) ? 'OK' : 'MISSING';
>>> exit()
```

---

### Step 3: Clear Laravel Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear
```

---

### Step 4: Run Tests

```bash
# Test the sync system
bash tests/test-ai-sync.sh

# Or use tinker directly
php artisan tinker
# Then copy-paste tests dari test-ai-sync.sh
```

---

### Step 5: Test Manual Flow

1. **Login as GKM user**
   - Go to Laporan Artefak → Generate Laporan Baru

2. **Create Draft**
   - Fill periode & judul
   - Click "Buat Laporan Draft"
   - Verify laporan created in database

3. **Test Chat & Sync**
   - Type message in AI assistant
   - Send message
   - **Look for:** Sync indicator (📤 → ✅)
   - **Check database:**
     ```sql
     SELECT id, ai_preview_draft, ai_preview_updated_at 
     FROM laporan_gkm 
     WHERE id = {laporan_id}
     LIMIT 1;
     ```
   - Verify `ai_preview_draft` is filled

4. **Test Revision**
   - Send another message for revision
   - **Check database:**
     ```sql
     SELECT ai_preview_draft, ai_preview_updated_at 
     FROM laporan_gkm 
     WHERE id = {laporan_id};
     ```
   - Verify timestamp changed to newer

5. **Test Generate Document**
   - Click "Generate Laporan Word"
   - **Check console logs:** 
     - Look for "Using newer version from database" message
   - Download file
   - Open file in Word
   - Verify content matches chat preview

6. **Test Latest Version Protection**
   - Modify `ai_preview_draft` in database directly to different content
   - Click Generate again
   - Verify file is generated from database version (not old UI data)

---

## 🐛 Troubleshooting

### Issue 1: "Column ai_preview_draft doesn't exist"

**Cause:** Migration not run

**Solution:**
```bash
# Check migration status
php artisan migrate:status

# If stuck, reset and run
php artisan migrate:reset
php artisan migrate
```

---

### Issue 2: Sync indicator not showing

**Check:**
1. Open browser DevTools → Console
2. Check for JavaScript errors
3. Verify response from `/gkm/laporan-artefak/ai-prompt` includes `sync_status`

**Fix:**
```bash
# Clear view cache
php artisan view:clear
# Refresh browser (Ctrl+Shift+R for hard refresh)
```

---

### Issue 3: Data not saving to database

**Check logs:**
```bash
tail -f storage/logs/laravel.log | grep -i "sync"
```

**Common causes:**
- User doesn't have permission
- Database connection issue
- Model not updated properly

**Verify model:**
```bash
php artisan tinker
>>> use App\Models\LaporanGKM;
>>> $m = new LaporanGKM;
>>> in_array('ai_preview_draft', $m->getFillable()) ? 'OK' : 'FAIL';
```

---

### Issue 4: Download generates old version

**Check:**
1. Refresh page before generate
2. Check API endpoint response:
   ```bash
   curl "http://localhost:8000/gkm/laporan-artefak/api/get?laporan_id=1" \
     -H "Accept: application/json"
   ```
3. Verify response includes latest `ai_preview_draft`

---

## 📊 Database Verification Queries

### Check if columns exist
```sql
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'laporan_gkm' 
AND COLUMN_NAME IN ('ai_preview_draft', 'ai_sections', 'ai_preview_updated_at');
```

### View laporan with AI data
```sql
SELECT id, periode, status, 
       LENGTH(ai_preview_draft) as preview_size,
       JSON_LENGTH(ai_sections) as sections_count,
       ai_preview_updated_at,
       ai_preview_used_for_generation
FROM laporan_gkm 
WHERE ai_preview_draft IS NOT NULL
ORDER BY ai_preview_updated_at DESC
LIMIT 10;
```

### Check latest syncs
```sql
SELECT id, periode, ai_preview_updated_at, 
       TIMESTAMPDIFF(MINUTE, ai_preview_updated_at, NOW()) as minutes_ago
FROM laporan_gkm 
WHERE ai_preview_updated_at IS NOT NULL
ORDER BY ai_preview_updated_at DESC
LIMIT 5;
```

---

## 🔍 Testing Checklist (Complete)

### Database Layer
- [ ] Columns exist in table
- [ ] Can update ai_preview_draft with text
- [ ] Can update ai_sections with JSON
- [ ] Timestamps auto-updated
- [ ] Booleans save correctly

### Model Layer
- [ ] Model has fillable attributes
- [ ] Model has proper casts
- [ ] No mass assignment errors
- [ ] Relationships work

### Controller Layer
- [ ] `aiPrompt()` saves to database
- [ ] `generateWordDocument()` reads from database
- [ ] `apiGet()` returns latest data
- [ ] Error handling works

### Frontend Layer
- [ ] Sync indicator shows
- [ ] Sync indicator hides after success
- [ ] Chat displays AI response
- [ ] Generate button works
- [ ] Download works

### Integration
- [ ] Chat → Database → Download works end-to-end
- [ ] Multiple revisions accumulate correctly
- [ ] Latest version always used on download
- [ ] User sees correct content in downloaded file

---

## 📈 Performance Monitoring

### Check query times
```bash
php artisan tinker
>>> use App\Models\LaporanGKM;
>>> DB::enableQueryLog();
>>> $laporan = LaporanGKM::find(1);
>>> print_r(DB::getQueryLog());
```

### Monitor sync operations
```bash
# Watch sync logs
tail -f storage/logs/laravel.log | grep "Synced AI response"

# Count successful syncs
grep "Synced AI response" storage/logs/laravel.log | wc -l
```

---

## 🚨 Rollback Procedure

If something goes wrong and need to rollback:

```bash
# 1. Revert code
git checkout HEAD~1 app/
git checkout HEAD~1 resources/
git checkout HEAD~1 routes/

# 2. Rollback migration
php artisan migrate:rollback

# 3. Clear cache
php artisan optimize:clear

# 4. Verify
php artisan migrate:status
php artisan route:list | grep laporan-artefak
```

---

## ✅ Deployment Validation

After deployment, run this validation script:

```bash
#!/bin/bash

echo "🔍 Post-Deployment Validation"
echo "=============================="

# Check 1: Database
php artisan tinker << 'EOF'
use App\Models\LaporanGKM;
try {
    $laporan = LaporanGKM::first();
    echo "✅ Can read from laporan_gkm\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
EOF

# Check 2: Routes
php artisan route:list | grep -q "laporan-artefak.*api-get"
[ $? -eq 0 ] && echo "✅ API route exists" || echo "❌ API route missing"

# Check 3: Views
grep -q "sync-status-indicator" resources/views/gkm/laporan-artefak/create.blade.php
[ $? -eq 0 ] && echo "✅ Sync indicator in view" || echo "❌ Sync indicator missing"

echo "=============================="
echo "✅ Validation Complete"
```

---

## 📞 Support & Documentation

- Main Documentation: `docs/AI_ASSISTANT_SYNC_SYSTEM.md`
- This Guide: `docs/SYNC_SETUP_DEPLOYMENT.md`
- Test Script: `tests/test-ai-sync.sh`
- Issue Tracking: Check logs in `storage/logs/laravel.log`

---

**Last Updated:** 2026-06-10  
**Status:** ✅ Ready for Deployment
