@echo off
REM ==========================================
REM  OSTA Job Portal — Production Deployment
REM ==========================================

echo [1/8] Checking prerequisites...
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH
    exit /b 1
)

echo [2/8] Backing up current deployment...
if exist "backup" rmdir /s /q backup
mkdir backup
xcopy /E /I /Q config backup\config
xcopy /E /I /Q includes backup\includes
copy /Y .env.backup .env >nul 2>&1

echo [3/8] Installing Composer dependencies...
call composer install --no-dev --optimize-autoloader --no-interaction

echo [4/8] Running database migrations...
php src/Database/MigrationRunner.php run

echo [5/8] Setting file permissions...
REM On Windows, ensure uploads directory exists
if not exist "uploads\resumes" mkdir uploads\resumes
if not exist "uploads\cover_letters" mkdir uploads\cover_letters
if not exist "uploads\documents" mkdir uploads\documents
if not exist "logs" mkdir logs

echo [6/8] Clearing old logs...
if exist "logs\app.log" del /Q logs\app.log
if exist "logs\error.log" del /Q logs\error.log

echo [7/8] Verifying environment...
php -r "require 'vendor/autoload.php'; require 'config/database.php'; echo 'Database connection: OK' . PHP_EOL;"

echo [8/8] Deployment complete!
echo.
echo IMPORTANT: Verify the following before going live:
echo   1. .env file has correct production values
echo   2. SSL certificate is installed
echo   3. Apache mod_rewrite is enabled
echo   4. uploads/ directory is writable by web server
echo   5. logs/ directory is writable by web server
echo   6. SMTP credentials are working
echo   7. run: php src/Database/MigrationRunner.php status
