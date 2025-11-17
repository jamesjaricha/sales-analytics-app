# 🚀 Deployment Guide - Sales Analytics App (cPanel)

This guide will help you deploy your Laravel 11 Sales Analytics application to a cPanel hosting environment.

---

## 📋 Pre-Deployment Checklist

Before deploying, ensure you have:

-   [x] **cPanel hosting account** with SSH access (recommended)
-   [x] **PHP 8.3+** available on your server
-   [x] **MySQL/MariaDB database** access
-   [x] **Node.js & npm** (for building assets)
-   [x] **Composer** (PHP dependency manager)
-   [x] **Git** (optional, but recommended)

---

## 🛠️ Step 1: Prepare Your Application

### 1.1 Build Production Assets

```bash
# Build optimized assets for production
npm run build

# Verify build was successful
ls -la public/build/
```

### 1.2 Update Environment Configuration

Create a production `.env` file:

```bash
cp .env .env.production
```

Edit `.env.production` with your production settings:

```env
APP_NAME="Sales Analytics"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_secure_password

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=database
CACHE_PREFIX=

MAIL_MAILER=log
```

### 1.3 Optimize Application

```bash
# Clear all caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

### 1.4 Create Deployment Package

```bash
# Remove development dependencies
composer install --no-dev --optimize-autoloader

# Create a zip file of your application
zip -r sales-analytics-app.zip . \
  -x "node_modules/*" \
  -x ".git/*" \
  -x "storage/logs/*" \
  -x "storage/framework/cache/*" \
  -x "storage/framework/sessions/*" \
  -x "storage/framework/views/*" \
  -x ".env" \
  -x "*.log"
```

---

## 📦 Step 2: Upload to cPanel

### Option A: Via File Manager

1. **Login to cPanel**
2. **Open File Manager**
3. **Navigate to your domain's root** (usually `public_html` or a subdomain folder)
4. **Upload** `sales-analytics-app.zip`
5. **Extract** the zip file
6. **Move Laravel files** to a folder OUTSIDE `public_html` (e.g., `laravel-app`)
7. **Move public folder contents** to `public_html`

### Option B: Via SSH (Recommended)

```bash
# Connect to your server
ssh username@your-server.com

# Navigate to your home directory
cd ~

# Create application directory
mkdir laravel-app
cd laravel-app

# Upload your zip file (use SFTP or scp)
# Then extract:
unzip sales-analytics-app.zip

# Set up directory structure
cd ~
# Move public contents to public_html
cp -r laravel-app/public/* public_html/
```

---

## 🔧 Step 3: Configure cPanel

### 3.1 Create Database

1. **Login to cPanel**
2. **Go to MySQL Databases**
3. **Create New Database**: `your_db_name`
4. **Create Database User**: `your_db_user`
5. **Set Strong Password**
6. **Add User to Database** with ALL PRIVILEGES
7. **Note down**:
    - Database name
    - Database user
    - Database password
    - Database host (usually `localhost`)

### 3.2 Update .env File

1. **Navigate to** `~/laravel-app` in File Manager
2. **Rename** `.env.production` to `.env`
3. **Edit** `.env` with your database credentials
4. **Generate APP_KEY** if not already set:

```bash
php artisan key:generate
```

### 3.3 Update index.php

Edit `public_html/index.php` to point to Laravel:

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../laravel-app/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../laravel-app/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../laravel-app/bootstrap/app.php')
    ->handleRequest(Request::capture());
```

### 3.4 Create .htaccess in public_html

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 🗄️ Step 4: Database Setup

### Via SSH:

```bash
cd ~/laravel-app

# Run migrations
php artisan migrate --force

# Seed admin user
php artisan db:seed --class=AdminUserSeeder --force
```

### Via cPanel Terminal:

1. **Open Terminal** in cPanel
2. **Navigate to Laravel**:
    ```bash
    cd ~/laravel-app
    php artisan migrate --force
    php artisan db:seed --class=AdminUserSeeder --force
    ```

---

## 🔒 Step 5: Set Permissions

### Via SSH:

```bash
cd ~/laravel-app

# Set correct ownership (replace 'username' with your cPanel username)
chown -R username:username storage bootstrap/cache

# Set directory permissions
find storage -type d -exec chmod 755 {} \;
find bootstrap/cache -type d -exec chmod 755 {} \;

# Set file permissions
find storage -type f -exec chmod 644 {} \;
find bootstrap/cache -type f -exec chmod 644 {} \;
```

### Via File Manager:

1. **Navigate to** `laravel-app/storage`
2. **Right-click** → Properties
3. **Set permissions to 755** for folders
4. **Check** "Recurse into subdirectories"
5. **Repeat for** `laravel-app/bootstrap/cache`

---

## 🔐 Step 6: Security Hardening

### 6.1 Protect Sensitive Files

Create `.htaccess` in `~/laravel-app`:

```apache
# Deny access to Laravel app directory
Order Deny,Allow
Deny from all
```

### 6.2 Update Environment Settings

In your `.env`:

```env
APP_DEBUG=false
APP_ENV=production
LOG_LEVEL=error
```

### 6.3 Set Up SSL Certificate

1. **Go to cPanel → SSL/TLS Status**
2. **Run AutoSSL** for your domain
3. **Or install Let's Encrypt** certificate
4. **Update APP_URL** in `.env` to use `https://`

---

## ✅ Step 7: Post-Deployment Testing

### 7.1 Test Application Access

1. Visit: `https://yourdomain.com`
2. Should redirect to: `https://yourdomain.com/login`
3. **Login with admin credentials**:
    - Email: `admin@example.com`
    - Password: `password123` (change immediately!)

### 7.2 Test Core Functionality

-   [ ] ✅ Dashboard loads with charts
-   [ ] ✅ Products CRUD operations work
-   [ ] ✅ Sales reporting functions correctly
-   [ ] ✅ User management (admin only) accessible
-   [ ] ✅ Monthly reports generate
-   [ ] ✅ No CSS/JS errors in browser console

### 7.3 Check Logs

```bash
cd ~/laravel-app
tail -f storage/logs/laravel.log
```

---

## 🐛 Troubleshooting

### Issue: 500 Internal Server Error

**Solutions:**

1. Check file permissions (storage and bootstrap/cache)
2. Verify `.env` file exists and has correct values
3. Check `storage/logs/laravel.log` for errors
4. Ensure PHP version is 8.3+

### Issue: CSS/JS Not Loading

**Solutions:**

1. Run `npm run build` before deployment
2. Verify `public_html/build/` folder has asset files
3. Check `APP_URL` in `.env` matches your domain
4. Clear browser cache

### Issue: Database Connection Failed

**Solutions:**

1. Verify database credentials in `.env`
2. Ensure database user has privileges
3. Check if MySQL is running
4. Use `localhost` as DB_HOST (not 127.0.0.1)

### Issue: Charts Not Displaying

**Solutions:**

1. Check browser console for JavaScript errors
2. Verify Chart.js is bundled in assets
3. Clear application cache: `php artisan cache:clear`
4. Rebuild assets: `npm run build`

### Issue: Admin Can't Create Users

**Solutions:**

1. Check role middleware is working
2. Verify CSRF token in forms
3. Check browser console for errors
4. Review `storage/logs/laravel.log`

---

## 🔄 Step 8: Updating the Application

When you need to update your application:

```bash
# On your local machine:
1. Make changes and test locally
2. npm run build
3. git commit and push (if using Git)
4. composer install --no-dev --optimize-autoloader
5. Create new deployment package

# On the server:
1. Backup database: php artisan backup
2. Put app in maintenance: php artisan down
3. Upload and extract new files
4. Run migrations: php artisan migrate --force
5. Clear caches: php artisan optimize
6. Bring app back up: php artisan up
```

---

## 📞 Support & Resources

### Laravel Documentation

-   https://laravel.com/docs/11.x/deployment
-   https://laravel.com/docs/11.x/configuration

### Common Commands

```bash
# Clear all caches
php artisan optimize:clear

# Cache everything for production
php artisan optimize

# View logs
tail -f storage/logs/laravel.log

# Check application status
php artisan about
```

---

## ⚠️ Important Security Notes

1. **Change default admin password immediately**
2. **Never commit `.env` file to Git**
3. **Always use HTTPS in production**
4. **Keep APP_DEBUG=false in production**
5. **Regularly update dependencies**: `composer update`
6. **Backup database regularly**
7. **Monitor logs for suspicious activity**

---

## 📝 Notes

-   **Database Backups**: Set up automated backups in cPanel
-   **Monitoring**: Consider using Laravel Telescope or similar in development
-   **Queue Workers**: If using queues, set up cron jobs
-   **Task Scheduling**: Add Laravel scheduler to cron

---

**Deployment Date**: ******\_\_\_******

**Deployed By**: ******\_\_\_******

**Domain**: ******\_\_\_******

**Server Details**: ******\_\_\_******
