#!/bin/bash

# Sales Analytics - Production Deployment Script
# For: https://sales.ulwazienergy.co.za

echo "🚀 Deploying Sales Analytics Application..."
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the application root directory."
    exit 1
fi

# 1. Generate Application Key (if not set)
echo "🔑 Generating application key..."
php artisan key:generate --force

# 2. Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Run database migrations
echo "💾 Running database migrations..."
php artisan migrate --force

# 4. Seed database with admin user
echo "👤 Creating admin user..."
php artisan db:seed --class=AdminUserSeeder --force

# 5. Create storage symlink
echo "🔗 Creating storage symlink..."
php artisan storage:link

# 6. Cache for production
echo "⚡ Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Set proper permissions
echo "🔒 Setting file permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

echo ""
echo "✅ Deployment completed successfully!"
echo ""
echo "🎉 Your application is now live at: https://sales.ulwazienergy.co.za"
echo ""
echo "📝 Default Admin Credentials:"
echo "   Email: admin@example.com"
echo "   Password: password"
echo ""
echo "⚠️  IMPORTANT: Change the admin password immediately after first login!"
echo ""
