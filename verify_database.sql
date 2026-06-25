-- =============================================
-- DATABASE VERIFICATION SCRIPT
-- Purpose: Verify table status and usage
-- Date: 2026-06-13
-- =============================================

USE pa3_database; -- Replace with your database name

-- =============================================
-- 1. LIST ALL TABLES IN DATABASE
-- =============================================
SELECT 
    TABLE_NAME as 'Table',
    TABLE_TYPE as 'Type',
    ENGINE as 'Engine',
    TABLE_ROWS as 'Estimated Rows',
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as 'Size (MB)',
    CREATE_TIME as 'Created',
    UPDATE_TIME as 'Last Updated'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- =============================================
-- 2. CHECK FOREIGN KEY RELATIONSHIPS
-- =============================================
SELECT 
    TABLE_NAME as 'Child Table',
    COLUMN_NAME as 'Foreign Key Column',
    REFERENCED_TABLE_NAME as 'Parent Table',
    REFERENCED_COLUMN_NAME as 'Parent Column',
    CONSTRAINT_NAME as 'Constraint Name'
FROM information_schema.KEY_COLUMN_USAGE
WHERE 
    TABLE_SCHEMA = DATABASE() 
    AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY TABLE_NAME, COLUMN_NAME;

-- =============================================
-- 3. CHECK TABLE SIZES
-- =============================================
SELECT 
    TABLE_NAME as 'Table',
    ROUND((DATA_LENGTH) / 1024 / 1024, 2) as 'Data Size (MB)',
    ROUND((INDEX_LENGTH) / 1024 / 1024, 2) as 'Index Size (MB)',
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as 'Total Size (MB)',
    TABLE_ROWS as 'Rows'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;


-- =============================================
-- 4. CHECK ACTIVE TABLES (Should exist)
-- =============================================
SELECT 
    CASE 
        WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS'
        ELSE '❌ MISSING'
    END as Status,
    'users' as Expected_Table
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'prodi'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prodi'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'periode_akademik'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'periode_akademik'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'dosenn'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dosenn'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'jadwal_dosen'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jadwal_dosen'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'rps_monitoring_snapshots'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rps_monitoring_snapshots'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'perkuliahan_monitoring_snapshots'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'perkuliahan_monitoring_snapshots'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'kuesioner_uploads'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kuesioner_uploads'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'laporan_gjm'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'laporan_gjm'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'laporan_gkm'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'laporan_gkm'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'laporan_bulanan'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'laporan_bulanan'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'template_laporan'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'template_laporan'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'document_chunks'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'document_chunks'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'embeddings_cache'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'embeddings_cache'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'jadwal_reminder'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jadwal_reminder'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'log_email'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_email'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'kirim_laporan_history'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kirim_laporan_history'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'cache'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cache'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NOT NULL THEN '✅ EXISTS' ELSE '❌ MISSING' END,
    'jobs'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jobs';

-- =============================================
-- 5. CHECK DEPRECATED TABLES (Should NOT exist)
-- =============================================
SELECT 
    CASE 
        WHEN TABLE_NAME IS NULL THEN '✅ DROPPED'
        ELSE '⚠️ STILL EXISTS'
    END as Status,
    'dosen' as Deprecated_Table,
    CASE 
        WHEN TABLE_NAME IS NULL THEN 'OK'
        ELSE 'NEEDS CLEANUP'
    END as Action_Needed
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dosen'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'perwaliaan',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'perwaliaan'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'monitoring',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'monitoring'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'kuisioner',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kuisioner'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'pertanyaan_kuisioner',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pertanyaan_kuisioner'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'jawaban_kuisioner',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jawaban_kuisioner'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'matkul_dosen',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'matkul_dosen'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'dosen_matakuliah',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dosen_matakuliah'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'jabatan_akademik',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jabatan_akademik'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'ai_response_cache',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ai_response_cache'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'ai_evaluation_tests',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ai_evaluation_tests'

UNION ALL SELECT 
    CASE WHEN TABLE_NAME IS NULL THEN '✅ DROPPED' ELSE '⚠️ STILL EXISTS' END,
    'ai_evaluation_results',
    CASE WHEN TABLE_NAME IS NULL THEN 'OK' ELSE 'NEEDS CLEANUP' END
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ai_evaluation_results';

-- =============================================
-- 6. CHECK ROW COUNTS OF ACTIVE TABLES
-- =============================================
SELECT 'users' as Table_Name, COUNT(*) as Row_Count FROM users
UNION ALL SELECT 'prodi', COUNT(*) FROM prodi
UNION ALL SELECT 'periode_akademik', COUNT(*) FROM periode_akademik
UNION ALL SELECT 'dosenn', COUNT(*) FROM dosenn
UNION ALL SELECT 'jadwal_dosen', COUNT(*) FROM jadwal_dosen
UNION ALL SELECT 'rps_monitoring_snapshots', COUNT(*) FROM rps_monitoring_snapshots
UNION ALL SELECT 'perkuliahan_monitoring_snapshots', COUNT(*) FROM perkuliahan_monitoring_snapshots
UNION ALL SELECT 'kuesioner_uploads', COUNT(*) FROM kuesioner_uploads
UNION ALL SELECT 'laporan_gjm', COUNT(*) FROM laporan_gjm
UNION ALL SELECT 'laporan_gkm', COUNT(*) FROM laporan_gkm
UNION ALL SELECT 'laporan_bulanan', COUNT(*) FROM laporan_bulanan
UNION ALL SELECT 'template_laporan', COUNT(*) FROM template_laporan
UNION ALL SELECT 'document_chunks', COUNT(*) FROM document_chunks
UNION ALL SELECT 'jadwal_reminder', COUNT(*) FROM jadwal_reminder
UNION ALL SELECT 'log_email', COUNT(*) FROM log_email
UNION ALL SELECT 'kirim_laporan_history', COUNT(*) FROM kirim_laporan_history
ORDER BY Row_Count DESC;

-- =============================================
-- 7. CHECK INDEXES ON IMPORTANT TABLES
-- =============================================
SELECT 
    TABLE_NAME as 'Table',
    INDEX_NAME as 'Index Name',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as 'Columns',
    INDEX_TYPE as 'Type',
    NON_UNIQUE as 'Non Unique'
FROM information_schema.STATISTICS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN (
        'users', 'laporan_gjm', 'laporan_gkm', 'laporan_bulanan',
        'rps_monitoring_snapshots', 'perkuliahan_monitoring_snapshots',
        'kuesioner_uploads', 'document_chunks'
    )
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;

-- =============================================
-- 8. SUMMARY REPORT
-- =============================================
SELECT 
    'Total Tables' as Metric,
    COUNT(*) as Value
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()

UNION ALL SELECT 
    'Total Size (MB)',
    ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()

UNION ALL SELECT 
    'Total Rows',
    SUM(TABLE_ROWS)
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()

UNION ALL SELECT
    'Foreign Keys',
    COUNT(DISTINCT CONSTRAINT_NAME)
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL;

-- =============================================
-- END OF VERIFICATION SCRIPT
-- =============================================
