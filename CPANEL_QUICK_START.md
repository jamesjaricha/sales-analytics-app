# cPanel Quick Start Guide - Sales Analytics Application

## Prerequisites Completed

✅ cPanel account with SSH access
✅ MySQL database created
✅ Domain/subdomain configured
✅ SSL certificate installed

## Quick Deployment Steps

### 1. Upload Files via cPanel File Manager or FTP

**Directory Structure:**

```
/home/username/
├── public_html/              (or your domain's document root)
│   └── (only public folder contents go here)
└── sales-analytics/          (application root - ABOVE public_html)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/                (contents moved to public_html)
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    └── .env
```

### 2. Set Document Root (Important!)

In cPanel:

1. Navigate to **Domains** section
2. Click on your domain
3. Set **Document Root** to point to the `public` directory of your application
    - Example: `/home/username/sales-analytics/public`

### 3. Configure .env File via cPanel File Manager

1. Navigate to `/home/username/sales-analytics/`
2. Copy `.env.example.production` to `.env`
3. Edit `.env` and update:

```env
APP_NAME="Sales Analytics"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=cpaneluser_dbname
DB_USERNAME=cpaneluser_dbuser
DB_PASSWORD=your_secure_password

SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_DOMAIN=.yourdomain.com
```

### 4. Run Deployment Script via SSH

```bash
# SSH into your server
ssh username@your-server-ip

# Navigate to application directory
cd ~/sales-analytics

# Make deployment script executable
chmod +x deploy-production.sh

# Run deployment
./deploy-production.sh
```

The script will:

-   Set file permissions
-   Install dependencies
-   Generate application key
-   Run database migrations
-   Create admin user
-   Cache configurations
-   Optimize for production

### 5. Alternative: Manual Deployment (if no SSH)

If you don't have SSH access, run these commands using cPanel Terminal or run them via PHP:

#### Generate Application Key

Create a file `generate-key.php` in your application root:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->call('key:generate', ['--force' => true]);
echo "Key generated successfully!";
```

Visit: `https://yourdomain.com/generate-key.php` (delete after use!)

#### Run Migrations

Create `migrate.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->call('migrate', ['--force' => true]);
echo "Migrations completed!";
```

Visit: `https://yourdomain.com/migrate.php` (delete after use!)

#### Create Admin User

Create `seed-admin.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->call('db:seed', ['--class' => 'AdminUserSeeder', '--force' => true]);
echo "Admin user created! Login: admin@example.com / Admin@123";
```

Visit: `https://yourdomain.com/seed-admin.php` (delete after use!)

#### Cache Configuration

Create `optimize.php`:

```php
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->call('config:cache');
$kernel->call('route:cache');
$kernel->call('view:cache');
echo "Application optimized!";
```

Visit: `https://yourdomain.com/optimize.php` (delete after use!)

### 6. Set File Permissions via cPanel

If you can't run the deployment script:

**Directories should be 755:**

-   storage/
-   bootstrap/cache/
-   All other directories

**Files should be 644:**

-   .env
-   All PHP files
-   Configuration files

**Special permissions (775):**

-   storage/framework/
-   storage/logs/
-   bootstrap/cache/

In cPanel File Manager:

1. Select `storage` folder → Right-click → Change Permissions → 775
2. Select `bootstrap/cache` folder → Change Permissions → 775

### 7. Enable HTTPS Redirect

Your `.htaccess` in the `public` folder should already have:

```apache
# Force HTTPS (uncomment these lines)
RewriteCond %{HTTPS} off
RewriteCond %{HTTP:X-Forwarded-Proto} !https
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 8. Set Up Cron Jobs (Optional - for scheduled tasks)

In cPanel → Cron Jobs:

**Command:**

```bash
cd /home/username/sales-analytics && php artisan schedule:run >> /dev/null 2>&1
```

**Frequency:** Every 1 minute

### 9. Post-Deployment Checklist

✅ Visit your domain: `https://yourdomain.com`
✅ Login with default credentials: admin@example.com / Admin@123
✅ **IMMEDIATELY change the admin password**
✅ Test creating a sales report
✅ Test PDF export functionality
✅ Verify all permissions are correct
✅ Check that SSL/HTTPS is working
✅ Test user registration (if enabled)
✅ Review error logs: `storage/logs/laravel.log`

### 10. Security Hardening

#### Disable Dangerous PHP Functions

In cPanel → Select PHP Version → Options:

```
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

#### Set Up Regular Backups

1. Enable automatic cPanel backups
2. Or use cPanel Backup Wizard
3. Download backups weekly

#### Monitor Error Logs

Regularly check:

-   `storage/logs/laravel.log`
-   cPanel Error Logs

### 11. Troubleshooting Common Issues

#### "500 Internal Server Error"

**Solution:**

1. Check `storage/logs/laravel.log`
2. Verify `.env` file exists and is readable
3. Check file permissions (755 for directories, 644 for files)
4. Run: `php artisan config:clear` via terminal

#### "Database Connection Error"

**Solution:**

1. Verify database name starts with your cPanel username: `cpaneluser_dbname`
2. Check database host is `localhost`
3. Test connection via cPanel phpMyAdmin
4. Ensure database user has all privileges

#### "Route Not Found" or "404 on All Pages"

**Solution:**

1. Verify document root points to `public` folder
2. Check `.htaccess` exists in public folder
3. Verify mod_rewrite is enabled (usually enabled by default)
4. Clear route cache: `php artisan route:clear`

#### "Session/Login Not Working"

**Solution:**

1. Check `SESSION_DOMAIN` in `.env` matches your domain
2. Ensure `storage/framework/sessions` is writable (775)
3. Clear sessions: `php artisan session:clear`
4. Verify SSL certificate is properly installed

#### "Assets Not Loading (CSS/JS)"

**Solution:**

1. Verify `APP_URL` in `.env` matches your domain
2. Check `public/build` folder exists with compiled assets
3. If missing, run `npm install && npm run build` locally and upload
4. Check file permissions on `public/build/*`

#### "Permission Denied Errors"

**Solution:**

```bash
cd ~/sales-analytics
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;
```

### 12. Performance Optimization

#### Enable OPcache

In cPanel → Select PHP Version → Enable OPcache extension

#### Configure PHP Settings

Recommended PHP settings (cPanel → MultiPHP INI Editor):

```ini
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 64M
upload_max_filesize = 64M
```

#### Use Database Query Caching

Already configured via `.env`:

```env
CACHE_STORE=database
```

### 13. Updating the Application

```bash
# 1. Enable maintenance mode
php artisan down --secret="your-bypass-token"

# 2. Backup database
mysqldump -u user -p database > backup_$(date +%Y%m%d).sql

# 3. Upload new files (skip .env and storage/)

# 4. Update dependencies
composer install --optimize-autoloader --no-dev

# 5. Run migrations
php artisan migrate --force

# 6. Clear and rebuild cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Disable maintenance mode
php artisan up
```

### 14. Support & Resources

**Application Logs:**

-   `storage/logs/laravel.log`

**cPanel Error Logs:**

-   cPanel → Errors

**Laravel Documentation:**

-   https://laravel.com/docs/11.x

**Health Check Endpoint:**

-   `https://yourdomain.com/health`

### 15. Default Credentials

**Initial Admin Account:**

```
Email: admin@example.com
Password: Admin@123
```

**⚠️ CRITICAL: Change this password immediately after first login!**

---

## Quick Command Reference

| Task                 | Command                                               |
| -------------------- | ----------------------------------------------------- |
| Generate Key         | `php artisan key:generate --force`                    |
| Run Migrations       | `php artisan migrate --force`                         |
| Seed Admin User      | `php artisan db:seed --class=AdminUserSeeder --force` |
| Clear Cache          | `php artisan cache:clear`                             |
| Cache Config         | `php artisan config:cache`                            |
| Cache Routes         | `php artisan route:cache`                             |
| Clear Route Cache    | `php artisan route:clear`                             |
| Maintenance Mode ON  | `php artisan down`                                    |
| Maintenance Mode OFF | `php artisan up`                                      |
| List Routes          | `php artisan route:list`                              |
| Check Logs           | `tail -f storage/logs/laravel.log`                    |

---

**Need Help?** Check the full `DEPLOYMENT_GUIDE.md` for detailed instructions.
