<!-- Cache Management Widget - Tambahkan ke dashboard view -->

<!-- Button di header/toolbar -->
<div class="cache-management">
    <form action="{{ route('gjm.clear-cache') }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-warning btn-sm" title="Refresh dashboard data dari database">
            <i class="fas fa-sync-alt"></i> Refresh Cache
        </button>
    </form>
</div>

<!-- Display cache status (optional) -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Example: Info box tentang cache -->
<div class="alert alert-info" role="alert">
    <h6 class="alert-heading">💾 Cache Status</h6>
    <small>
        Dashboard data di-cache selama <strong>60 menit</strong>. 
        Jika data tidak update, klik <strong>Refresh Cache</strong> untuk reload dari database.
    </small>
</div>

<!-- CSS (optional) -->
<style>
.cache-management {
    margin-bottom: 1rem;
}

.cache-management .btn {
    transition: all 0.3s ease;
}

.cache-management .btn:hover {
    transform: scale(1.05);
}
</style>

<!-- JavaScript (optional) - untuk visual feedback -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clearCacheForm = document.querySelector('.cache-management form');
    if (clearCacheForm) {
        clearCacheForm.addEventListener('submit', function(e) {
            const btn = clearCacheForm.querySelector('button');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Clearing...';
        });
    }
});
</script>

<!-- 
INSTALLATION:
1. Copy snippet ini ke resources/views/gjm/dashboard/index.blade.php
2. Letakkan di bagian header/toolbar
3. Adjust styling sesuai dengan theme yang digunakan

USAGE:
- Users bisa klik "Refresh Cache" untuk force reload data dari database
- Cocok dilakukan setelah upload data baru
- Setelah klik, page akan redirect dan cache akan di-clear

ALTERNATIVE (di .env untuk development):
- CACHE_STORE=array (tidak cache, always fresh)
- atau php artisan cache:clear
-->
