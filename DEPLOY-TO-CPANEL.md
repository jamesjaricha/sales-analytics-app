# Deploy Sales Analytics to https://sales.ulwazienergy.co.za

## ✅ PRE-DEPLOYMENT (Done on Local)

1. ✅ Production .env configured for sales.ulwazienergy.co.za
2. ⚠️ **YOU MUST DO:** Run `npm run build` to create production assets
3. ⚠️ **YOU MUST DO:** Ensure `public/build/` folder exists after npm build

## 📦 UPLOAD TO CPANEL

### Step 1: Access cPanel

-   Login to your cPanel at ulwazienergy.co.za
-   Go to File Manager

### Step 2: Upload Application Files

Upload the entire `sales-analytics-app` folder to:

```
/home/username/sales-analytics-app/
```

**Important Files to Upload:**

-   ✅ All `app/` folder
-   ✅ All `config/` folder
-   ✅ All `database/` folder
-   ✅ All `resources/` folder
-   ✅ All `routes/` folder
-   ✅ All `public/` folder (including `public/build/` and `public/images/`)
-   ✅ `bootstrap/` folder
-   ✅ `storage/` folder
-   ✅ `vendor/` folder
-   ✅ `artisan` file
-   ✅ `composer.json` and `composer.lock`
-   ✅ `.htaccess` (from public folder)

**DO NOT Upload:**

-   ❌ `.env` (create new one on server)
-   ❌ `node_modules/`
-   ❌ `.git/`

### Step 3: Point Domain to Public Folder

In cPanel, set document root for `sales.ulwazienergy.co.za` to:

```
/home/username/sales-analytics-app/public
```

## 🔧 CONFIGURE ON SERVER

### Step 1: Create Database

1. Go to cPanel → MySQL Databases
2. Create database: `ulwazien_sales` (or your preferred name)
3. Create user: `ulwazien_salesuser`
4. Set strong password
5. Add user to database with ALL PRIVILEGES

### Step 2: Create .env File

1. Copy `.env.production` to `.env`
2. Update these values:

```env
DB_DATABASE=ulwazien_sales
DB_USERNAME=ulwazien_salesuser
DB_PASSWORD=your_actual_password
```

### Step 3: Run Commands via Terminal (or SSH)

```bash
# Navigate to app directory
cd ~/sales-analytics-app

# Generate application key
php artisan key:generate

# Run migrations and seed admin user
php artisan migrate --seed

# Create storage symlink
php artisan storage:link

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## 🎯 POST-DEPLOYMENT VERIFICATION

1. Visit: https://sales.ulwazienergy.co.za
2. Should see login page with logo
3. Login credentials (from seeder):
    - Email: admin@example.com
    - Password: password
4. ⚠️ **IMMEDIATELY** change admin password after first login!

## 🔍 TROUBLESHOOTING

### If you see "500 Internal Server Error":

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log
```

### If you see "Mix Manifest Not Found":

-   Make sure you ran `npm run build` locally
-   Make sure `public/build/` folder was uploaded

### If database connection fails:

-   Verify database credentials in `.env`
-   Check if database user has correct privileges

### If images/logo don't show:

```bash
php artisan storage:link
```

## 📝 IMPORTANT NOTES

1. **Admin Password:** Change it immediately after first login
2. **Database Backups:** Set up automatic backups in cPanel
3. **SSL Certificate:** Ensure HTTPS is working (should be automatic)
4. **File Permissions:**
    - Files: 644
    - Folders: 755
    - storage/ and bootstrap/cache/: 775

## 🎉 YOU'RE DONE!

Your Sales Analytics app should now be live at:
**https://sales.ulwazienergy.co.za**

Default admin login:

-   Email: admin@example.com
-   Password: password (CHANGE THIS!)
