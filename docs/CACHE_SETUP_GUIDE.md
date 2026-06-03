# Setup & Activation Guide - Caching System

## ✅ What's Implemented

Sistem cache sudah siap di-implementasikan di Dashboard GJM untuk mempercepat loading page.

### Files Modified/Created:
1. ✅ `app/Http/Controllers/GJM/DashboardController.php` - Main caching logic
2. ✅ `routes/web.php` - Added clear-cache route
3. ✅ `app/Console/Commands/ClearDashboardCache.php` - Artisan command
4. ✅ `docs/CACHING_SYSTEM.md` - Complete documentation
5. ✅ `docs/CACHE_WIDGET_EXAMPLE.blade.php` - UI widget example

---

## 🚀 Aktivasi (4 Langkah)

### Step 1: Verifikasi Cache Driver
```bash
# Check .env file
cat .env | grep CACHE_STORE
```

Expected output: `CACHE_STORE=database` (current) atau `CACHE_STORE=redis` (recommended)

### Step 2: Ensure Cache Table Exists
```bash
# Jika belum ada table 'cache':
php artisan cache:table
php artisan migrate
```

Atau manual check:
```bash
php artisan tinker
> DB::table('cache')->count()
```

### Step 3: Test Cache (Optional)
```bash
php artisan tinker
> Cache::put('test_key', 'test_value', now()->addMinutes(5))
> Cache::get('test_key')  # Should return 'test_value'
> Cache::forget('test_key')
```

### Step 4: Test Dashboard
1. Open browser: `http://localhost/gjm/dashboard`
2. Check Network tab in DevTools
3. First load: ~5-10 detik (queries cache)
4. Refresh page: <500ms (cache hit!)
5. Click "Refresh Cache" button: Clear cache & reload

---

## 📊 Expected Performance Gains

### Before Cache
```
First Load:     5-10 seconds
Database Queries: ~20+
Subsequent Load: 5-10 seconds (same queries)
```

### After Cache
```
First Load:      5-10 seconds (query + cache)
Database Queries: ~2 (filter lists only)
Cache Hit Load:   <500ms ⚡
```

### Benefits
- 95%+ faster on cache hit
- 90% less database queries
- Significantly reduced server load

---

## 🎛️ Configuration

### Cache Duration (Default: 60 menit)
**File**: `app/Http/Controllers/GJM/DashboardController.php` Line 33

```php
$cacheDuration = 60; // Change this value
```

Options:
- `5` = 5 menit (lebih sering update)
- `60` = 1 jam (balanced)
- `240` = 4 jam (jarang update)
- `1440` = 24 jam (long cache)

### Filter List Duration (Default: 24 jam)
**File**: `app/Http/Controllers/GJM/DashboardController.php` Lines 288, 293

```php
Cache::remember('filter_list_tahun', now()->addHours(24), function () {
    // Change '24' to desired hours
});
```

---

## 🛠️ Usage

### CLI Commands
```bash
# Clear dashboard cache (filter + dashboard data)
php artisan cache:clear-dashboard

# Clear filter lists only
php artisan cache:clear-dashboard --filter-only

# Clear ALL application cache
php artisan cache:clear-dashboard --all

# Or use Laravel default
php artisan cache:clear
```

### Web UI (Optional)
Add button ke dashboard view (lihat: `docs/CACHE_WIDGET_EXAMPLE.blade.php`)
```
POST /gjm/clear-cache → Clear cache & redirect back
```

---

## 🔍 Monitoring

### Check Cache Content
```bash
php artisan tinker

# Check if cache exists
> Cache::has('dashboard_gjm_xxx')

# Get cache value
> $data = Cache::get('dashboard_gjm_xxx')
> dd($data)

# Check filter caches
> Cache::has('filter_list_tahun')
> Cache::get('filter_list_tahun')
```

### Database Cache Table
```sql
-- Check cache entries
SELECT COUNT(*) FROM cache;

-- View cache keys
SELECT `key`, DATE_FORMAT(FROM_UNIXTIME(expiration), '%Y-%m-%d %H:%i:%S') as expires_at 
FROM cache 
LIMIT 10;

-- Delete specific cache
DELETE FROM cache WHERE `key` LIKE 'dashboard_gjm_%';
```

---

## ⚠️ Important Notes

### Database Driver Limitations
Current setup menggunakan `database` driver:
- ✅ Works fine
- ❌ Can't use wildcard patterns (dashboard_gjm_*)
- ⚠️ Slower than Redis for high-volume

### Production Recommendation
**Upgrade ke Redis** untuk performa maksimal:

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

Then all cache clearing akan instant dan support tagging.

### Cache Invalidation
Cache akan auto-expire berdasarkan duration:
- Dashboard: 60 menit → expired & fresh query
- Filter list: 24 jam → expired & fresh query

Manual clear: `php artisan cache:clear-dashboard`

---

## 🐛 Troubleshooting

### Cache tidak jalan?
```bash
# Check cache driver
php artisan config:show cache

# Clear and retry
php artisan cache:clear
php artisan cache:forget dashboard_gjm_*

# Check logs
tail -f storage/logs/laravel.log
```

### Page masih lambat setelah cache?
1. Check if cache table exists: `php artisan migrate`
2. Verify storage folder writable: `chmod 755 storage`
3. Check database connection
4. Monitor queries: `php artisan tinker` → `DB::enableQueryLog()`

### Forget/expire cache keys?
```bash
# Forget specific key
php artisan tinker
> Cache::forget('dashboard_gjm_xxx')
> Cache::forget('filter_list_tahun')

# Verify
> Cache::has('dashboard_gjm_xxx')  # false
```

---

## 📈 Next Steps (Optional)

### Phase 2: Advanced Caching
1. Add cache hit/miss metrics
2. Implement cache warming (pre-populate at off-peak)
3. Add cache invalidation events
4. Create cache analytics dashboard

### Phase 3: Production Optimization
1. Migrate to Redis
2. Implement cache layer (CDN for assets)
3. Add query optimization (eager loading)
4. Database indexing review

---

## 📝 References

- Complete docs: [docs/CACHING_SYSTEM.md](CACHING_SYSTEM.md)
- Widget example: [docs/CACHE_WIDGET_EXAMPLE.blade.php](CACHE_WIDGET_EXAMPLE.blade.php)
- Laravel cache docs: https://laravel.com/docs/cache

---

**Status**: ✅ Ready to use
**Last Updated**: June 2, 2026
**Cache Driver**: Database (can upgrade to Redis)
