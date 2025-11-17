@echo off
REM Sales Analytics App - Deployment Preparation Script (Windows)
REM This script prepares your application for deployment to cPanel

echo ==========================================
echo Sales Analytics - Deployment Preparation
echo ==========================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo Error: artisan file not found. Please run this script from your Laravel project root.
    pause
    exit /b 1
)

echo [OK] Laravel project detected
echo.

REM Step 1: Clear all caches
echo Clearing caches...
call php artisan config:clear
call php artisan route:clear
call php artisan view:clear
call php artisan cache:clear
echo [OK] Caches cleared
echo.

REM Step 2: Build production assets
echo Building production assets...
where npm >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    call npm run build
    if %ERRORLEVEL% EQU 0 (
        echo [OK] Assets built successfully
    ) else (
        echo [ERROR] Asset build failed. Please check for errors.
        pause
        exit /b 1
    )
) else (
    echo [WARNING] npm not found. Please build assets manually with: npm run build
)
echo.

REM Step 3: Install production dependencies
echo Installing production dependencies...
where composer >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    call composer install --no-dev --optimize-autoloader
    if %ERRORLEVEL% EQU 0 (
        echo [OK] Dependencies installed
    ) else (
        echo [ERROR] Composer install failed
        pause
        exit /b 1
    )
) else (
    echo [ERROR] Composer not found. Please install Composer first.
    pause
    exit /b 1
)
echo.

REM Step 4: Optimize for production
echo Optimizing for production...
call php artisan config:cache
call php artisan route:cache
call php artisan view:cache
call php artisan event:cache
call php artisan optimize
echo [OK] Application optimized
echo.

REM Step 5: Check for .env.production
if exist ".env.production" (
    echo [OK] .env.production file found
) else (
    echo [WARNING] Creating .env.production from .env
    copy .env .env.production
    echo [WARNING] Please update .env.production with production settings!
)
echo.

REM Step 6: Create deployment package info
echo ==========================================
echo Package Creation
echo ==========================================
echo.
echo Please manually create a ZIP file with the following:
echo.
echo INCLUDE:
echo   - All files and folders EXCEPT:
echo.
echo EXCLUDE:
echo   - node_modules/
echo   - .git/
echo   - storage/logs/*
echo   - storage/framework/cache/*
echo   - storage/framework/sessions/*
echo   - storage/framework/views/*
echo   - .env
echo   - *.log
echo   - tests/
echo   - .vscode/
echo   - deploy-prep.bat
echo   - deploy.sh
echo.
echo Suggested ZIP name: sales-analytics-app_%date:~-4%%date:~3,2%%date:~0,2%.zip
echo.

REM Step 7: Display next steps
echo ==========================================
echo Preparation Complete!
echo ==========================================
echo.
echo Next Steps:
echo.
echo 1. Create ZIP file as described above
echo 2. Upload to your cPanel
echo 3. Extract to ~/laravel-app/
echo 4. Move public/* to public_html/
echo 5. Create database in cPanel
echo 6. Rename .env.production to .env
echo 7. Update .env with database credentials
echo 8. Run: php artisan migrate --force
echo 9. Run: php artisan db:seed --class=AdminUserSeeder --force
echo.
echo See DEPLOYMENT.md for detailed instructions
echo.
echo IMPORTANT:
echo   - Update APP_URL in .env to your domain
echo   - Set APP_DEBUG=false
echo   - Set APP_ENV=production
echo   - Change default admin password after first login
echo.
echo ==========================================

pause
