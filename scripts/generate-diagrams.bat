@echo off
REM ============================================================================
REM Auto Class Diagram Generator - Windows Batch Script
REM ============================================================================

echo.
echo ========================================
echo   Class Diagram Generator
echo ========================================
echo.

REM Check if PHP is installed
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PHP not found in PATH!
    echo.
    echo Please install PHP first:
    echo   - Download from: https://windows.php.net/download/
    echo   - Or use: winget install PHP.PHP
    echo.
    pause
    exit /b 1
)

echo [INFO] PHP found: 
php -v | findstr /C:"PHP"
echo.

REM Menu
echo Select diagram type:
echo   1. Models Only
echo   2. Services Only
echo   3. Controllers Only
echo   4. Complete Diagram (All)
echo   5. Exit
echo.

set /p choice="Enter choice (1-5): "

if "%choice%"=="1" (
    echo.
    echo [INFO] Generating Models diagram...
    php scripts/generate-class-diagram.php --models-only --format=plantuml
    if %ERRORLEVEL% EQU 0 (
        echo [SUCCESS] Models diagram generated!
        echo Output: docs\diagrams\class-diagram.plantuml
    )
) else if "%choice%"=="2" (
    echo.
    echo [INFO] Generating Services diagram...
    php scripts/generate-class-diagram.php --services-only --format=plantuml
    if %ERRORLEVEL% EQU 0 (
        echo [SUCCESS] Services diagram generated!
    )
) else if "%choice%"=="3" (
    echo.
    echo [INFO] Generating Controllers diagram...
    php scripts/generate-class-diagram.php --controllers-only --format=plantuml
    if %ERRORLEVEL% EQU 0 (
        echo [SUCCESS] Controllers diagram generated!
    )
) else if "%choice%"=="4" (
    echo.
    echo [INFO] Generating complete diagram...
    php scripts/generate-class-diagram.php --full --format=plantuml
    if %ERRORLEVEL% EQU 0 (
        echo [SUCCESS] Complete diagram generated!
    )
) else if "%choice%"=="5" (
    echo Bye!
    exit /b 0
) else (
    echo [ERROR] Invalid choice!
    pause
    exit /b 1
)

echo.
echo ========================================
echo   Generation Complete!
echo ========================================
echo.
echo Next steps:
echo   1. View online: https://www.plantuml.com/plantuml/uml/
echo   2. Use VSCode PlantUML extension
echo   3. Generate PNG: plantuml docs\diagrams\class-diagram.puml
echo.

pause
