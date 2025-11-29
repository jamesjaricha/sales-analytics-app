# URGENT: Deployment Error Fix Guide

## Current Issues:

1. **403 Forbidden** on root URL (https://sales.ulwazienergy.co.za/)
2. **500 Internal Server Error** on /public/ URL

## Root Cause:

Your domain's document root is pointing to the application root instead of the `public` folder.

---

## IMMEDIATE FIX - Choose One Method:

### METHOD 1: Change Document Root (RECOMMENDED) ✅

**This is the correct Laravel deployment method.**

#### Steps in cPanel:

1. **Login to cPanel**

2. **Go to "Domains" section**

3. **Click on your domain** (sales.ulwazienergy.co.za)

4. **Change Document Root:**

    - Current (wrong): `/home/username/sales-analytics-app/`
    - Change to (correct): `/home/username/sales-analytics-app/public`

5. **Save changes**

6. **Wait 2-3 minutes** for changes to propagate

7. **Visit:** https://sales.ulwazienergy.co.za/ (should work now)

---

### METHOD 2: Move Files (Alternative if you can't change document root)

**Only use this if you cannot access domain settings in cPanel.**

#### Via SSH:

```bash
# Navigate to your home directory
cd ~/

# Backup current setup
cp -r public_html public_html_backup

# Move Laravel public folder contents to public_html
cp -r sales-analytics-app/public/* public_html/

# Move application root one level up (if not already there)
# Your structure should be:
# /home/username/
#   ├── public_html/           (Laravel public folder contents)
#   │   ├── index.php
#   │   ├── .htaccess
#   │   └── build/
#   └── sales-analytics-app/   (Laravel application root)
#       ├── app/
#       ├── bootstrap/
#       ├── config/
#       └── ...
```

#### Update index.php path:

Edit `/home/username/public_html/index.php` and change paths:

**Find these lines:**

```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

**Change to:**

```php
require __DIR__.'/../sales-analytics-app/vendor/autoload.php';
$app = require_once __DIR__.'/../sales-analytics-app/bootstrap/app.php';
```

---

## ADDITIONAL FIXES for 500 Error:

### 1. Check .env File

**Via cPanel File Manager or SSH:**

```bash
# Navigate to application root
cd ~/sales-analytics-app/

# Check if .env exists
ls -la .env

# If missing, create from template
cp .env.example.production .env

# Edit .env
nano .env
```

**Critical .env settings:**

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:XXXXX  # Must be generated
APP_URL=https://sales.ulwazienergy.co.za

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

### 2. Generate APP_KEY (if missing)

```bash
cd ~/sales-analytics-app/
php artisan key:generate --force
```

**OR via browser (one-time use):**

Create file: `generate-key.php` in your document root:

```php
<?php
require __DIR__.'/../sales-analytics-app/vendor/autoload.php';
$app = require_once __DIR__.'/../sales-analytics-app/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->call('key:generate', ['--force' => true]);
echo "Application key generated! Check your .env file.";
unlink(__FILE__); // Delete this file after running
?>
```

Visit: `https://sales.ulwazienergy.co.za/generate-key.php` (runs once and deletes itself)

### 3. Set File Permissions

```bash
cd ~/sales-analytics-app/

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Set storage permissions
chmod -R 775 storage bootstrap/cache

# If you get permission denied, try with group ownership
chgrp -R www-data storage bootstrap/cache
# OR
chgrp -R nobody storage bootstrap/cache
```

### 4. Check Error Logs

**Via cPanel:**

1. Go to **"Errors"** section in cPanel
2. View the latest errors
3. Share the error message if you need help

**OR via SSH:**

```bash
# Check Laravel logs
tail -50 ~/sales-analytics-app/storage/logs/laravel.log

# Check Apache error log
tail -50 ~/logs/error_log
# OR
tail -50 ~/public_html/error_log
```

---

## VERIFICATION CHECKLIST

After applying fixes, verify:

-   [ ] Can access: https://sales.ulwazienergy.co.za/ (no 403)
-   [ ] No 500 error
-   [ ] Login page shows correctly
-   [ ] CSS/JS assets load
-   [ ] Can login (test with any credentials to see if DB works)

---

## If Still Getting Errors:

### For 403 Forbidden:

**Check .htaccess in public folder:**

Ensure `/public/.htaccess` contains:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

**Check index.php exists:**

```bash
ls -la ~/sales-analytics-app/public/index.php
# Should show: -rw-r--r-- (644 permissions)
```

### For 500 Internal Server Error:

**Common causes:**

1. **Missing .env file**

    ```bash
    cd ~/sales-analytics-app/
    cp .env.example.production .env
    nano .env  # Edit with your settings
    ```

2. **Wrong APP_KEY**

    ```bash
    php artisan key:generate --force
    ```

3. **Storage permissions**

    ```bash
    chmod -R 775 storage bootstrap/cache
    ```

4. **Database connection**

    - Verify database exists
    - Verify credentials in .env
    - Test connection:

    ```bash
    php artisan tinker
    DB::connection()->getPdo();
    # Should connect without error
    ```

5. **Composer dependencies missing**

    ```bash
    cd ~/sales-analytics-app/
    composer install --optimize-autoloader --no-dev
    ```

6. **PHP version**
    - Check PHP version: `php -v`
    - Should be PHP 8.2 or higher
    - In cPanel: **MultiPHP Manager** → Select PHP 8.2+

---

## QUICK DEBUG SCRIPT

Create `debug.php` in your document root:

```php
<?php
echo "<h1>Debug Information</h1>";

// Check PHP version
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if Laravel files are accessible
$appPath = __DIR__ . '/../sales-analytics-app';
echo "<p>Application path: " . $appPath . "</p>";
echo "<p>Application exists: " . (file_exists($appPath) ? 'YES' : 'NO') . "</p>";

// Check .env file
$envPath = $appPath . '/.env';
echo "<p>.env exists: " . (file_exists($envPath) ? 'YES' : 'NO') . "</p>";

// Check storage permissions
$storagePath = $appPath . '/storage';
echo "<p>Storage writable: " . (is_writable($storagePath) ? 'YES' : 'NO') . "</p>";

// Check vendor folder
$vendorPath = $appPath . '/vendor';
echo "<p>Vendor folder exists: " . (file_exists($vendorPath) ? 'YES' : 'NO') . "</p>";

// Try to load Laravel
try {
    require $appPath . '/vendor/autoload.php';
    echo "<p style='color:green;'>✓ Autoload successful</p>";

    $app = require_once $appPath . '/bootstrap/app.php';
    echo "<p style='color:green;'>✓ Laravel app loaded</p>";

    echo "<p>APP_ENV: " . env('APP_ENV') . "</p>";
    echo "<p>APP_DEBUG: " . (env('APP_DEBUG') ? 'true' : 'false') . "</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Delete this file after viewing
echo "<hr>";
echo "<p><strong>Delete this file after debugging!</strong></p>";
?>
```

Visit: `https://sales.ulwazienergy.co.za/debug.php`

This will show you exactly what's wrong.

---

## MOST LIKELY SOLUTION:

Based on your error, **99% chance** the issue is:

**Your domain document root is pointing to the wrong directory.**

### Quick Fix:

1. cPanel → Domains → sales.ulwazienergy.co.za
2. Change Document Root to: `/home/username/sales-analytics-app/public`
3. Save
4. Wait 2 minutes
5. Refresh browser

**That should fix both the 403 and 500 errors!**

---

## Need Help?

**Send me:**

1. Output from debug.php (if you create it)
2. Last 20 lines from storage/logs/laravel.log
3. Last 20 lines from cPanel error logs
4. Your current directory structure

**To get directory structure:**

```bash
cd ~/
ls -la
cd sales-analytics-app
ls -la
```

Share the output and I'll provide specific fixes.
