@echo off
echo ========================================
echo JALANKAN ANALISIS KUESIONER (DIRECT)
echo ========================================
echo.

echo Checking Python...
C:\Python312\python.exe --version
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Python not found!
    echo Please install Python or update path in this script.
    pause
    exit /b 1
)

echo.
echo Checking PySpark...
C:\Python312\python.exe -c "import pyspark; print('PySpark version:', pyspark.__version__)"
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo WARNING: PySpark not installed!
    echo Installing PySpark...
    C:\Python312\python.exe -m pip install pyspark
)

echo.
echo Checking PyMongo...
C:\Python312\python.exe -c "import pymongo"
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo WARNING: PyMongo not installed!
    echo Installing PyMongo...
    C:\Python312\python.exe -m pip install pymongo
)

echo.
echo ========================================
echo Starting Analysis...
echo ========================================
echo.

cd /d "%~dp0"
C:\Python312\python.exe spark\spark_kuesioner.py

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: Analysis failed!
    echo Check the error message above.
    pause
    exit /b 1
)

echo.
echo ========================================
echo SUCCESS! Clearing cache...
echo ========================================
php artisan gjm:debug-dashboard --clear-cache

echo.
echo ========================================
echo DONE! Refresh your dashboard.
echo ========================================
pause
