# Queue Troubleshooting Guide

Jalankan command berikut satu per satu untuk mendiagnosa masalah queue:

## 1. Cek Status Queue Worker
```bash
docker compose ps queue
```
**Expected**: Container `pa3_queue` harus status `Up`

---

## 2. Cek Log Queue Worker
```bash
docker compose logs queue --tail=50
```
**Look for**: Error messages atau "Processing" messages

---

## 3. Cek Jumlah Jobs di Database
```bash
docker compose exec mysql mysql -uroot -prootpassword -D pa3_db -e "SELECT COUNT(*) as pending_jobs FROM jobs;"
```
**Expected**: Harus ada jobs (> 0) setelah aktivasi periode

---

## 4. Lihat Isi Jobs Table
```bash
docker compose exec mysql mysql -uroot -prootpassword -D pa3_db -e "SELECT id, queue, attempts, created_at FROM jobs ORDER BY id DESC LIMIT 10;"
```
**Expected**: Harus ada job dengan nama `SyncJadwalDosenJob`

---

## 5. Cek Failed Jobs
```bash
docker compose exec mysql mysql -uroot -prootpassword -D pa3_db -e "SELECT id, queue, LEFT(exception, 100) as error, failed_at FROM failed_jobs ORDER BY id DESC LIMIT 5;"
```
**Look for**: Error messages jika ada jobs yang gagal

---

## 6. Cek Jumlah Dosen
```bash
docker compose exec mysql mysql -uroot -prootpassword -D pa3_db -e "SELECT COUNT(*) as total_dosen FROM dosenn;"
```
**Expected**: Harus ada dosen (> 0) agar jobs didispatch

---

## 7. Cek Periode Aktif
```bash
docker compose exec mysql mysql -uroot -prootpassword -D pa3_db -e "SELECT id, tahun_ajaran, semester_label, is_active FROM periode_akademiks ORDER BY id DESC LIMIT 5;"
```
**Expected**: Harus ada 1 periode dengan `is_active = 1`

---

## 8. Test Manual Job Dispatch
```bash
docker compose exec laravel php artisan tinker --execute="
\$periode = \App\Models\PeriodeAkademik::where('is_active', true)->first();
\$dosen = \App\Models\Dosenn::first();
if (\$periode && \$dosen) {
    \App\Jobs\SyncJadwalDosenJob::dispatch(\$dosen->pegawai_id, \$periode->semester, \$periode->tahun_ajaran);
    echo 'Job dispatched! Check jobs table.' . PHP_EOL;
} else {
    echo 'ERROR: No periode/dosen' . PHP_EOL;
}
"
```
Setelah itu cek lagi jobs table (command #3)

---

## 9. Restart Queue Worker (Jika Perlu)
```bash
docker compose restart queue
```

---

## 10. Monitor Queue Worker Real-time
```bash
docker compose logs queue -f
```
Press `Ctrl+C` to stop monitoring

---

## Common Issues & Solutions

### Issue 1: No jobs in database
**Cause**: Tidak ada dosen di database
**Solution**: Check command #6, pastikan ada records di table `dosenn`

### Issue 2: Jobs exist but not processing
**Cause**: Queue worker not running atau error
**Solution**: 
- Check command #2 for errors
- Restart queue worker (command #9)

### Issue 3: Jobs keep failing
**Cause**: Error in SyncJadwalDosenJob
**Solution**: 
- Check command #5 for error details
- Check External API credentials in .env
- Check LIBRARY_API_URL, LIBRARY_API_USERNAME, LIBRARY_API_PASSWORD

### Issue 4: Queue worker crashes
**Cause**: Memory issues atau connection problems
**Solution**: Check `docker compose logs queue` for OOM (Out of Memory) or database connection errors

---

## Quick One-Line Diagnostic
```bash
echo "Queue Status:" && docker compose ps queue && echo -e "\nPending Jobs:" && docker compose exec mysql mysql -uroot -prootpassword -D pa3_db -e "SELECT COUNT(*) FROM jobs;" && echo -e "\nTotal Dosen:" && docker compose exec mysql mysql -uroot -prootpassword -D pa3_db -e "SELECT COUNT(*) FROM dosenn;"
```
