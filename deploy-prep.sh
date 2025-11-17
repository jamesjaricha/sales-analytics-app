#!/bin/bash

# Sales Analytics App - Deployment Preparation Script
# This script prepares your application for deployment to cPanel

echo "=========================================="
echo "Sales Analytics - Deployment Preparation"
echo "=========================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from your Laravel project root."
    exit 1
fi

echo "✅ Laravel project detected"
echo ""

# Step 1: Clear all caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "✅ Caches cleared"
echo ""

# Step 2: Build production assets
echo "🔨 Building production assets..."
if command -v npm &> /dev/null; then
    npm run build
    if [ $? -eq 0 ]; then
        echo "✅ Assets built successfully"
    else
        echo "❌ Asset build failed. Please check for errors."
        exit 1
    fi
else
    echo "⚠️  npm not found. Please build assets manually with: npm run build"
fi
echo ""

# Step 3: Install production dependencies
echo "📦 Installing production dependencies..."
if command -v composer &> /dev/null; then
    composer install --no-dev --optimize-autoloader
    if [ $? -eq 0 ]; then
        echo "✅ Dependencies installed"
    else
        echo "❌ Composer install failed"
        exit 1
    fi
else
    echo "❌ Composer not found. Please install Composer first."
    exit 1
fi
echo ""

# Step 4: Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
echo "✅ Application optimized"
echo ""

# Step 5: Check for .env.production
if [ -f ".env.production" ]; then
    echo "✅ .env.production file found"
else
    echo "⚠️  Creating .env.production from .env"
    cp .env .env.production
    echo "⚠️  Please update .env.production with production settings!"
fi
echo ""

# Step 6: Create deployment package
echo "📦 Creating deployment package..."
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
PACKAGE_NAME="sales-analytics-app_${TIMESTAMP}.zip"

# Create zip excluding unnecessary files
zip -r $PACKAGE_NAME . \
  -x "node_modules/*" \
  -x ".git/*" \
  -x "storage/logs/*" \
  -x "storage/framework/cache/*" \
  -x "storage/framework/sessions/*" \
  -x "storage/framework/views/*" \
  -x ".env" \
  -x "*.log" \
  -x "tests/*" \
  -x ".vscode/*" \
  -x "deploy-prep.sh" \
  -x "deploy.sh"

if [ $? -eq 0 ]; then
    echo "✅ Package created: $PACKAGE_NAME"
    FILE_SIZE=$(du -h $PACKAGE_NAME | cut -f1)
    echo "📊 Package size: $FILE_SIZE"
else
    echo "❌ Failed to create package"
    exit 1
fi
echo ""

# Step 7: Display next steps
echo "=========================================="
echo "🎉 Preparation Complete!"
echo "=========================================="
echo ""
echo "📋 Next Steps:"
echo ""
echo "1. Upload $PACKAGE_NAME to your cPanel"
echo "2. Extract to ~/laravel-app/"
echo "3. Move public/* to public_html/"
echo "4. Create database in cPanel"
echo "5. Rename .env.production to .env"
echo "6. Update .env with database credentials"
echo "7. Run: php artisan migrate --force"
echo "8. Run: php artisan db:seed --class=AdminUserSeeder --force"
echo ""
echo "📚 See DEPLOYMENT.md for detailed instructions"
echo ""
echo "⚠️  IMPORTANT:"
echo "   - Update APP_URL in .env to your domain"
echo "   - Set APP_DEBUG=false"
echo "   - Set APP_ENV=production"
echo "   - Change default admin password after first login"
echo ""
echo "=========================================="
