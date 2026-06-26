# Script untuk copy struktur triwulan-create ke artefak-create
# dengan replacement otomatis

$source = "c:\Semester 6\PA3\resources\views\gjm\buat-laporan\triwulan-create.blade.php"
$dest = "c:\Semester 6\PA3\resources\views\gkm\laporan-artefak\create.blade.php"

Write-Host "Reading source file: triwulan-create.blade.php" -ForegroundColor Cyan

$content = Get-Content $source -Raw

Write-Host "Performing replacements for artefak..." -ForegroundColor Yellow

# Replace all triwulan references with artefak
$content = $content -replace 'triwulan', 'artefak'
$content = $content -replace 'Triwulan', 'Artefak'
$content = $content -replace 'TRIWULAN', 'ARTEFAK'

# Replace periode options
$oldPeriode = @'
                                        <option value="">Pilih Periode Artefak</option>
                                        <option value="1">Artefak I (Januari - Maret)</option>
                                        <option value="2">Artefak II (April - Juni)</option>
                                        <option value="3">Artefak III (Juli - September)</option>
                                        <option value="4">Artefak IV (Oktober - Desember)</option>
'@

$newPeriode = @'
                                        <option value="">-- Pilih Periode --</option>
                                        @foreach ($periodes as $p)
                                            <option value="{{ $p['value'] }}" {{ old('periode') == $p['value'] ? 'selected' : '' }}>
                                                {{ $p['label'] }}
                                            </option>
                                        @endforeach
'@

$content = $content -replace [regex]::Escape($oldPeriode), $newPeriode

# Replace field names
$content = $content -replace 'periode_artefak', 'periode'
$content = $content -replace 'name="periode"', 'name="periode"'
$content = $content -replace 'id="periode"', 'id="periode"'

# Replace routes
$content = $content -replace "route\('gjm\.buat-laporan\.artefak", "route('gkm.laporan-artefak"

# Replace descriptions
$content = $content -replace 'AI Agent akan menganalisis data kegiatan dan monitoring mutu dalam periode artefak yang dipilih', 'AI Agent akan menganalisis data RPS dan Materi dalam periode yang dipilih'
$content = $content -replace 'Data laporan bulanan otomatis digunakan sebagai konteks', 'Data monitoring RPS & Materi otomatis digunakan sebagai konteks'

# Replace AI header text
$content = $content -replace 'AI Assistant - Laporan Artefak', 'AI Assistant - Laporan Artefak'
$content = $content -replace 'Siap membantu Anda membuat laporan', 'Siap membantu Anda membuat laporan RPS & Materi'

# Replace placeholder
$content = $content -replace 'Deskripsikan website yang ingin Anda buat\.\.\.', 'Deskripsikan laporan artefak yang ingin Anda buat...'

# Replace hidden input name
$content = $content -replace 'name="tipe_laporan" value="artefak"', 'name="tipe_laporan" value="artefak"'

Write-Host "Writing to destination: artefak-create.blade.php" -ForegroundColor Green

Set-Content -Path $dest -Value $content -Encoding UTF8

Write-Host ""
Write-Host "✓ File successfully copied and modified!" -ForegroundColor Green
Write-Host ""
Write-Host "Summary of changes:" -ForegroundColor Cyan
Write-Host "- Replaced 'triwulan' with 'artefak'" -ForegroundColor White
Write-Host "- Updated periode options to use dynamic \$periodes" -ForegroundColor White
Write-Host "- Updated routes to GKM paths" -ForegroundColor White
Write-Host "- Updated descriptions for RPS and Materi context" -ForegroundColor White
Write-Host ""
Write-Host "Please manually verify:" -ForegroundColor Yellow
Write-Host "1. Route references" -ForegroundColor White
Write-Host "2. Field names match controller expectations" -ForegroundColor White
Write-Host "3. JavaScript functionality" -ForegroundColor White
