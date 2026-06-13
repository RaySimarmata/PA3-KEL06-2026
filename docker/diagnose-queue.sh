#!/bin/bash

echo "=========================================="
echo "QUEUE DIAGNOSTICS"
echo "=========================================="
echo ""

echo "1. CHECKING QUEUE WORKER STATUS"
echo "------------------------------------------"
docker compose ps queue
echo ""

echo "2. QUEUE WORKER LOGS (last 50 lines)"
echo "------------------------------------------"
docker compose logs queue --tail=50
echo ""

echo "3. CHECKING JOBS TABLE"
echo "------------------------------------------"
docker compose exec -T mysql mysql -uroot -p${DB_ROOT_PASSWORD:-secret} -D ${DB_DATABASE:-pa3_db} -e "SELECT COUNT(*) as pending_jobs FROM jobs;"
echo ""
echo "Showing last 10 jobs:"
docker compose exec -T mysql mysql -uroot -p${DB_ROOT_PASSWORD:-secret} -D ${DB_DATABASE:-pa3_db} -e "SELECT id, queue, attempts, created_at FROM jobs ORDER BY id DESC LIMIT 10;"
echo ""

echo "4. CHECKING FAILED JOBS"
echo "------------------------------------------"
docker compose exec -T mysql mysql -uroot -p${DB_ROOT_PASSWORD:-secret} -D ${DB_DATABASE:-pa3_db} -e "SELECT COUNT(*) as failed_jobs FROM failed_jobs;"
echo ""
echo "Showing last 5 failed jobs:"
docker compose exec -T mysql mysql -uroot -p${DB_ROOT_PASSWORD:-secret} -D ${DB_DATABASE:-pa3_db} -e "SELECT id, queue, exception, failed_at FROM failed_jobs ORDER BY id DESC LIMIT 5;"
echo ""

echo "5. CHECKING DOSEN RECORDS"
echo "------------------------------------------"
docker compose exec -T mysql mysql -uroot -p${DB_ROOT_PASSWORD:-secret} -D ${DB_DATABASE:-pa3_db} -e "SELECT COUNT(*) as total_dosen FROM dosenn;"
echo ""
echo "Sample dosen records:"
docker compose exec -T mysql mysql -uroot -p${DB_ROOT_PASSWORD:-secret} -D ${DB_DATABASE:-pa3_db} -e "SELECT pegawai_id, nama FROM dosenn LIMIT 5;"
echo ""

echo "6. CHECKING ACTIVE PERIODE"
echo "------------------------------------------"
docker compose exec -T mysql mysql -uroot -p${DB_ROOT_PASSWORD:-secret} -D ${DB_DATABASE:-pa3_db} -e "SELECT id, tahun_ajaran, semester_label, is_active FROM periode_akademiks WHERE is_active = 1;"
echo ""

echo "7. TESTING MANUAL JOB DISPATCH"
echo "------------------------------------------"
echo "Dispatching a test job..."
docker compose exec laravel php artisan tinker --execute="
\$periode = \App\Models\PeriodeAkademik::where('is_active', true)->first();
\$dosen = \App\Models\Dosenn::first();
if (\$periode && \$dosen) {
    \App\Jobs\SyncJadwalDosenJob::dispatch(\$dosen->pegawai_id, \$periode->semester, \$periode->tahun_ajaran);
    echo 'Test job dispatched for dosen: ' . \$dosen->pegawai_id . PHP_EOL;
    echo 'Checking jobs table...' . PHP_EOL;
} else {
    echo 'ERROR: No active periode or dosen found' . PHP_EOL;
}
"
echo ""

echo "8. CHECKING JOBS TABLE AFTER TEST"
echo "------------------------------------------"
docker compose exec -T mysql mysql -uroot -p${DB_ROOT_PASSWORD:-secret} -D ${DB_DATABASE:-pa3_db} -e "SELECT COUNT(*) as pending_jobs FROM jobs;"
echo ""

echo "=========================================="
echo "DIAGNOSTICS COMPLETE"
echo "=========================================="
