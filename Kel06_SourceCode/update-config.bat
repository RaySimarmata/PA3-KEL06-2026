@echo off
echo ================================================
echo   Updating Laravel Configuration for Ngrok
echo ================================================
echo.

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo.
echo ================================================
echo   Configuration Updated Successfully!
echo ================================================
echo.
echo Your app is now accessible at:
echo https://3ecc-114-122-40-45.ngrok-free.app
echo.
pause
