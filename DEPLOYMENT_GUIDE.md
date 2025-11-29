# Sales Analytics Application - Deployment Guide for cPanel

## Pre-Deployment Checklist

### 1. Server Requirements

-   ✅ PHP 8.2 or higher
-   ✅ MySQL 5.7+ or MariaDB 10.3+
-   ✅ Composer 2.x
-   ✅ Node.js 18+ and NPM (for building assets)
-   ✅ Required PHP Extensions:
    -   BCMath
    -   Ctype
    -   Fileinfo
    -   JSON
    -   Mbstring
    -   OpenSSL
    -   PDO
    -   Tokenizer
    -   XML
    -   GD or Imagick
    -   DOM

### 2. Pre-Deployment Preparation (Local)

#### Step 1: Build Production Assets

```bash
npm install
npm run build
```

#### Step 2: Optimize Composer

```bash
composer install --optimize-autoloader --no-dev
```

#### Step 3: Clear Development Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## cPanel Deployment Steps

### Step 1: Create Database

1. Log into cPanel
2. Go to **MySQL Databases**
3. Create a new database: `your_database_name`
4. Create a new MySQL user with a strong password
5. Add user to database with ALL PRIVILEGES
6. Note down:
    - Database name
    - Database username
    - Database password
    - Database host (usually `localhost`)

### Step 2: Upload Files

#### Option A: Using File Manager

1. Upload the ZIP file containing your application
2. Extract in `public_html` or a subdirectory
3. **Important**: Move all files EXCEPT `public` folder contents to a directory ABOVE `public_html`

#### Option B: Using Git (Recommended)

```bash
cd ~/
git clone your-repository-url sales-analytics
cd sales-analytics
```

### Step 3: Configure Web Root

**Critical**: Your web server should point to the `public` directory, not the application root.

#### Using cPanel:

1. Go to **Domains** → **Addon Domains** or **Subdomains**
2. Set Document Root to: `/home/username/sales-analytics/public`

### Step 4: Set Up Environment File

1. Copy `.env.example.production` to `.env`

```bash
cp .env.example.production .env
```

2. Edit `.env` file with your actual values:

```bash
nano .env
```

Update these critical values:

```env
APP_NAME="Your Sales Analytics"
APP_ENV=production
APP_KEY=  # Leave blank for now, we'll generate it
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_actual_database_name
DB_USERNAME=your_actual_db_username
DB_PASSWORD=your_actual_db_password

SESSION_DOMAIN=.yourdomain.com
```

### Step 5: Set File Permissions

```bash
cd ~/sales-analytics

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make artisan executable
chmod +x artisan

# Set storage and bootstrap/cache permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# If you're not the owner, you may need to set group ownership
chgrp -R www-data storage bootstrap/cache
```

### Step 6: Install Dependencies (if not already done)

```bash
cd ~/sales-analytics
composer install --optimize-autoloader --no-dev
```

### Step 7: Generate Application Key

```bash
php artisan key:generate --force
```

### Step 8: Run Database Migrations

```bash
php artisan migrate --force
```

This will create all necessary tables:

-   users
-   daily_sales_reports
-   daily_sales_items
-   products
-   deductions
-   sessions
-   cache
-   jobs

### Step 9: Create Admin User

```bash
php artisan db:seed --class=AdminUserSeeder --force
```

**Default admin credentials** (CHANGE IMMEDIATELY):

-   Email: admin@example.com
-   Password: Admin@123

### Step 10: Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache
```

### Step 11: Create Symbolic Link for Storage

```bash
php artisan storage:link
```

### Step 12: Set Up HTTPS (SSL Certificate)

1. In cPanel, go to **SSL/TLS Status**
2. Enable AutoSSL or install Let's Encrypt certificate
3. Force HTTPS redirect (add to `.htaccess` in `public` folder)

## .htaccess Configuration

Ensure your `public/.htaccess` includes:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

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

## Post-Deployment Security Checklist

### 1. Change Default Admin Password

```
Login → Profile → Change Password
```

### 2. Verify Security Headers

Visit your site and check headers using browser dev tools or:

```bash
curl -I https://yourdomain.com
```

Should see:

-   `X-Content-Type-Options: nosniff`
-   `X-Frame-Options: DENY`
-   `X-XSS-Protection: 1; mode=block`
-   `Strict-Transport-Security: max-age=31536000`
-   `Content-Security-Policy: ...`

### 3. Disable Directory Listing

Add to `.htaccess`:

```apache
Options -Indexes
```

### 4. Hide Sensitive Files

Ensure `.env`, `.git`, `composer.json`, etc. are NOT accessible:

```apache
# In public/.htaccess
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 5. Set Up Cron Jobs (if using queues)

In cPanel Cron Jobs, add:

```bash
* * * * * cd /home/username/sales-analytics && php artisan schedule:run >> /dev/null 2>&1
```

### 6. Enable Maintenance Mode (for updates)

```bash
php artisan down --secret="your-secret-bypass-token"
# Perform updates
php artisan up
```

## Troubleshooting

### 500 Internal Server Error

1. Check `storage/logs/laravel.log`
2. Verify file permissions
3. Clear cache: `php artisan cache:clear`
4. Check `.env` configuration

### Database Connection Issues

1. Verify database credentials in `.env`
2. Test connection: `php artisan tinker` then `DB::connection()->getPdo();`
3. Check if database host is correct (try `localhost` and `127.0.0.1`)

### Session/Login Issues

1. Clear sessions: `php artisan session:clear`
2. Verify `SESSION_DOMAIN` matches your domain
3. Check cookie settings in `.env`

### Asset Not Loading

1. Verify `APP_URL` in `.env`
2. Run `npm run build` locally
3. Upload `public/build` folder
4. Check file permissions

## Updating the Application

1. Enable maintenance mode:

```bash
php artisan down
```

2. Pull latest code (or upload files)

3. Update dependencies:

```bash
composer install --optimize-autoloader --no-dev
npm install && npm run build
```

4. Run migrations:

```bash
php artisan migrate --force
```

5. Clear and cache:

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. Disable maintenance mode:

```bash
php artisan up
```

## Backup Strategy

### Database Backup (Daily)

Set up cPanel backup or use:

```bash
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### File Backup

-   Use cPanel backup feature
-   Or sync to cloud storage

### Automated Backups

Add to cron:

```bash
0 2 * * * /path/to/backup-script.sh
```

## Monitoring

1. **Error Logs**: Check `storage/logs/laravel.log` regularly
2. **Health Check**: Visit `/health` endpoint
3. **Uptime Monitoring**: Use services like UptimeRobot
4. **Performance**: Monitor using New Relic, Blackfire, or similar

## Support

For issues, check:

1. Laravel documentation: https://laravel.com/docs
2. Application logs: `storage/logs/laravel.log`
3. Web server logs (cPanel → Error Logs)

---

**Security Note**: Never commit `.env` file to version control. Always use strong passwords and keep your application updated.
