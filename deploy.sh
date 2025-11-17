#!/bin/bash

# Sales Analytics App Deployment Script
# Run this script to deploy the application to production

echo "🚀 Starting deployment of Sales Analytics App..."

# 1. Pull latest changes
echo "📥 Pulling latest changes from git..."
git pull origin main

# 2. Install/update dependencies
echo "📦 Installing/updating Composer dependencies..."
composer install --optimize-autoloader --no-dev

echo "📦 Installing/updating NPM dependencies..."
npm ci --production

# 3. Clear caches
echo "🧹 Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 4. Run database migrations
echo "🗃️ Running database migrations..."
php artisan migrate --force

# 5. Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Build assets
echo "🎨 Building production assets..."
npm run build

# 7. Set proper permissions
echo "🔐 Setting proper file permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 8. Restart services
echo "🔄 Restarting services..."
sudo service nginx reload
sudo service php8.2-fpm reload

# 9. Run queue workers (if using queues)
echo "⚙️ Restarting queue workers..."
php artisan queue:restart

echo "✅ Deployment completed successfully!"
echo "🌐 Your application should now be live at your domain."

# Optional: Run a basic health check
echo "🏥 Running health check..."
curl -f http://localhost/health || echo "❌ Health check failed - please verify manually"

echo "🎉 Deployment process finished!"