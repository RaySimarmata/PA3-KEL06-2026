# PowerShell Script to Create AI Assistant Files for Semester and VMTS
# Based on Triwulan AI Assistant with all validations

Write-Host "========================================"
Write-Host "Creating AI Assistant Files"
Write-Host "========================================"
Write-Host ""

$sourceFile = "c:\Semester 6\PA3\public\js\ai-prompt-assistant-triwulan.js"
$semesterFile = "c:\Semester 6\PA3\public\js\ai-prompt-assistant-semester.js"
$vmtsFile = "c:\Semester 6\PA3\public\js\ai-prompt-assistant-vmts.js"

# Check if source file exists
if (-not (Test-Path $sourceFile)) {
    Write-Host "ERROR: Source file not found"
    exit 1
}

Write-Host "Source file found"
Write-Host ""

# Read source file
Write-Host "Reading source file..."
$content = Get-Content $sourceFile -Raw -Encoding UTF8

# CREATE SEMESTER VERSION
Write-Host ""
Write-Host "Creating Semester version..."

$semesterContent = $content
$semesterContent = $semesterContent -replace 'AIPromptAssistantTriwulan', 'AIPromptAssistantSemester'
$semesterContent = $semesterContent -replace 'triwulan', 'semester'
$semesterContent = $semesterContent -replace 'Triwulan', 'Semester'

Write-Host "Saving Semester file..."
$semesterContent | Out-File $semesterFile -Encoding UTF8 -NoNewline
Write-Host "Semester file created!"

# CREATE VMTS VERSION
Write-Host ""
Write-Host "Creating VMTS version..."

$vmtsContent = $content
$vmtsContent = $vmtsContent -replace 'AIPromptAssistantTriwulan', 'AIPromptAssistantVMTS'
$vmtsContent = $vmtsContent -replace 'triwulan', 'vmts'
$vmtsContent = $vmtsContent -replace 'Triwulan', 'VMTS'

# Fix lowercase IDs
$vmtsContent = $vmtsContent -replace 'ai-assistant-VMTS', 'ai-assistant-vmts'
$vmtsContent = $vmtsContent -replace 'ai-conversation-VMTS', 'ai-conversation-vmts'
$vmtsContent = $vmtsContent -replace 'ai-input-VMTS', 'ai-input-vmts'
$vmtsContent = $vmtsContent -replace 'ai-send-btn-VMTS', 'ai-send-btn-vmts'
$vmtsContent = $vmtsContent -replace 'file-upload-area-VMTS', 'file-upload-area-vmts'
$vmtsContent = $vmtsContent -replace 'file-input-VMTS', 'file-input-vmts'
$vmtsContent = $vmtsContent -replace 'uploaded-files-VMTS', 'uploaded-files-vmts'
$vmtsContent = $vmtsContent -replace 'ai-floating-btn-VMTS', 'ai-floating-btn-vmts'

Write-Host "Saving VMTS file..."
$vmtsContent | Out-File $vmtsFile -Encoding UTF8 -NoNewline
Write-Host "VMTS file created!"

# SUMMARY
Write-Host ""
Write-Host "========================================"
Write-Host "SUMMARY"
Write-Host "========================================"
Write-Host ""
Write-Host "Source: $sourceFile"
Write-Host "Semester: $semesterFile"
Write-Host "VMTS: $vmtsFile"
Write-Host ""
Write-Host "All files created successfully!"
Write-Host ""
Write-Host "All validations from Triwulan applied:"
Write-Host "  - File upload validation with OCR"
Write-Host "  - Conversation history management"
Write-Host "  - UI Controls"
Write-Host "  - Drag and Drop"
Write-Host "  - Retry mechanism"
Write-Host "  - Error handling"
Write-Host "  - Smart section merge"
Write-Host "  - Input validation"
Write-Host ""
