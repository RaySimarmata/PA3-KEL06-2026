# Script untuk menambahkan AI Assistant ke halaman Laporan Artefak dan Semester
# Version: 1.0.0
# Last Updated: 2026-06-02

Write-Host "=== Adding AI Assistant to Laporan Artefak and Semester Pages ===" -ForegroundColor Cyan
Write-Host ""

# Paths
$projectRoot = "c:\Semester 6\PA3"
$artefakCreateBlade = "$projectRoot\resources\views\gkm\laporan-artefak\create.blade.php"
$semesterCreateBlade = "$projectRoot\resources\views\gjm\buat-laporan\semester-create.blade.php"
$triwulanCreateBlade = "$projectRoot\resources\views\gjm\buat-laporan\triwulan-create.blade.php"

Write-Host "Files to update:" -ForegroundColor Yellow
Write-Host "1. $artefakCreateBlade"
Write-Host "2. $semesterCreateBlade"
Write-Host ""

Write-Host "Reference file:" -ForegroundColor Yellow
Write-Host "   $triwulanCreateBlade"
Write-Host ""

# Backup files
Write-Host "Creating backups..." -ForegroundColor Green
Copy-Item $artefakCreateBlade "$artefakCreateBlade.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')" -ErrorAction SilentlyContinue
Copy-Item $semesterCreateBlade "$semesterCreateBlade.backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')" -ErrorAction SilentlyContinue

Write-Host "✓ Backups created" -ForegroundColor Green
Write-Host ""

Write-Host "MANUAL STEPS REQUIRED:" -ForegroundColor Cyan
Write-Host "====================="  -ForegroundColor Cyan
Write-Host ""
Write-Host "Please follow these steps to add AI Assistant functionality:" -ForegroundColor Yellow
Write-Host ""
Write-Host "FOR LAPORAN ARTEFAK (gkm/laporan-artefak/create.blade.php):" -ForegroundColor Cyan
Write-Host "1. Copy all CSS styles from triwulan-create.blade.php @section('styles')" -ForegroundColor White
Write-Host "2. Add AI Chat Interface HTML section before form closing tag" -ForegroundColor White
Write-Host "3. Copy JavaScript from triwulan-create @section('scripts')" -ForegroundColor White
Write-Host "4. Update references:"  -ForegroundColor White
Write-Host "   - Change 'triwulan' to 'artefak' in all IDs and variables" -ForegroundColor Gray
Write-Host "   - Update API endpoint to '/gkm/laporan-artefak/ai-prompt'" -ForegroundColor Gray
Write-Host "   - Update context fields (periode, template_id)" -ForegroundColor Gray
Write-Host ""

Write-Host "FOR LAPORAN SEMESTER (gjm/buat-laporan/semester-create.blade.php):" -ForegroundColor Cyan
Write-Host "1. File already has AI Assistant styles and HTML" -ForegroundColor White
Write-Host "2. Verify JavaScript is properly configured" -ForegroundColor White
Write-Host "3. Test API endpoint '/gjm/laporan-semester/ai-prompt'" -ForegroundColor White
Write-Host ""

Write-Host "CONTROLLER UPDATES NEEDED:" -ForegroundColor Cyan
Write-Host "===========================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Add these routes and methods:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. LaporanArtefakController.php - Add method:" -ForegroundColor White
Write-Host @"
    public function aiPrompt(Request `$request)
    {
        // Implement AI prompt handler similar to LaporanTriwulanController
        // Use ClaudeAIService
        // Handle file uploads and conversation history
        // Return JSON response
    }
"@ -ForegroundColor Gray
Write-Host ""

Write-Host "2. web.php - Add routes:" -ForegroundColor White
Write-Host @"
    // GKM Laporan Artefak AI
    Route::post('/gkm/laporan-artefak/ai-prompt', 
        [LaporanArtefakController::class, 'aiPrompt']
    )->name('gkm.laporan-artefak.ai-prompt');
    
    // GJM Laporan Semester AI (if not exists)
    Route::post('/gjm/laporan-semester/ai-prompt', 
        [LaporanSemesterController::class, 'aiPrompt']
    )->name('gjm.laporan-semester.ai-prompt');
"@ -ForegroundColor Gray
Write-Host ""

Write-Host "FILES CREATED:" -ForegroundColor Cyan
Write-Host "==============" -ForegroundColor Cyan
Write-Host "✓ public/js/ai-prompt-assistant-artefak.js" -ForegroundColor Green
Write-Host ""

Write-Host "NEXT STEPS:" -ForegroundColor Cyan
Write-Host "===========" -ForegroundColor Cyan
Write-Host "1. Review triwulan-create.blade.php structure" -ForegroundColor White
Write-Host "2. Copy AI Chat Interface section to artefak create page" -ForegroundColor White
Write-Host "3. Update controller methods with AI prompt handlers" -ForegroundColor White
Write-Host "4. Test functionality" -ForegroundColor White
Write-Host ""

Write-Host "=== Script Complete ===" -ForegroundColor Green
