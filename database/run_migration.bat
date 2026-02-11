@echo off
REM ============================================
REM Nusantara Rental Car - Database Migration
REM Run this file to update your database
REM ============================================

echo.
echo ========================================
echo  Database Migration Tool
echo ========================================
echo.

REM Check if MySQL exists
if not exist "C:\xampp\mysql\bin\mysql.exe" (
    echo [ERROR] MySQL not found at C:\xampp\mysql\bin\mysql.exe
    echo Please check your XAMPP installation
    pause
    exit /b 1
)

echo [1/3] Checking MySQL connection...
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT 1;" >nul 2>&1

if errorlevel 1 (
    echo [ERROR] Cannot connect to MySQL
    echo.
    echo Please make sure:
    echo   1. XAMPP Control Panel is open
    echo   2. MySQL is started (green indicator)
    echo   3. Database 'nusantara_rental_car' exists
    echo.
    pause
    exit /b 1
)

echo [OK] MySQL connection successful!
echo.

echo [2/3] Applying database migration...
C:\xampp\mysql\bin\mysql.exe -u root nusantara_rental_car < "%~dp0migration_password_reset.sql"

if errorlevel 1 (
    echo [ERROR] Migration failed
    pause
    exit /b 1
)

echo [OK] Migration applied successfully!
echo.

echo [3/3] Verifying changes...
echo.
echo New columns in 'users' table:
C:\xampp\mysql\bin\mysql.exe -u root -e "USE nusantara_rental_car; DESCRIBE users;" | findstr "email_verified verification_token reset_token"

echo.
echo ========================================
echo  Migration Completed Successfully!
echo ========================================
echo.
echo New features enabled:
echo   - Password Reset functionality
echo   - Email Verification (ready for future)
echo.
echo Your database has been updated!
echo.
pause
