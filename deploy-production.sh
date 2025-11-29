#!/bin/bash

###############################################################################
# Sales Analytics Application - Production Deployment Script for cPanel
#
# This script automates the deployment process on cPanel hosting.
# Run this script AFTER uploading files and configuring .env file
###############################################################################

set -e  # Exit on error

echo "======================================"
echo "Sales Analytics - Deployment Script"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Check if .env file exists
if [ ! -f ".env" ]; then
    print_error ".env file not found!"
    print_info "Please create .env file from .env.example.production"
    exit 1
fi

print_success ".env file found"

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    print_error "artisan file not found. Are you in the application root directory?"
    exit 1
fi

print_success "Application root directory confirmed"

echo ""
echo "Step 1: Setting file permissions..."
echo "-----------------------------------"

# Set directory permissions
find . -type d -exec chmod 755 {} \; 2>/dev/null || true
print_success "Directory permissions set to 755"

# Set file permissions
find . -type f -exec chmod 644 {} \; 2>/dev/null || true
print_success "File permissions set to 644"

# Make artisan executable
chmod +x artisan
print_success "Artisan made executable"

# Set storage and bootstrap/cache permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
print_success "Storage and cache directories set to 775"

echo ""
echo "Step 2: Installing/Updating Composer dependencies..."
echo "----------------------------------------------------"

if command -v composer &> /dev/null; then
    composer install --optimize-autoloader --no-dev --no-interaction
    print_success "Composer dependencies installed"
else
    print_warning "Composer not found. Please install dependencies manually:"
    print_info "  composer install --optimize-autoloader --no-dev"
fi

echo ""
echo "Step 3: Generating application key (if not set)..."
echo "--------------------------------------------------"

# Check if APP_KEY is empty in .env
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=\"\"" .env; then
    php artisan key:generate --force
    print_success "Application key generated"
else
    print_info "Application key already set"
fi

echo ""
echo "Step 4: Running database migrations..."
echo "--------------------------------------"

# Ask for confirmation before migrating
read -p "Run database migrations? This will modify the database. (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    print_success "Database migrations completed"
else
    print_warning "Database migrations skipped"
fi

echo ""
echo "Step 5: Seeding admin user..."
echo "----------------------------"

read -p "Create admin user? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan db:seed --class=AdminUserSeeder --force
    print_success "Admin user created"
    print_warning "Default credentials: admin@example.com / Admin@123"
    print_warning "CHANGE PASSWORD IMMEDIATELY AFTER LOGIN!"
else
    print_info "Admin user seeding skipped"
fi

echo ""
echo "Step 6: Creating storage symlink..."
echo "-----------------------------------"

php artisan storage:link 2>/dev/null || print_warning "Storage link already exists or failed"
print_success "Storage symlink created"

echo ""
echo "Step 7: Clearing caches..."
echo "-------------------------"

php artisan config:clear
print_success "Configuration cache cleared"

php artisan cache:clear
print_success "Application cache cleared"

php artisan route:clear
print_success "Route cache cleared"

php artisan view:clear
print_success "View cache cleared"

echo ""
echo "Step 8: Optimizing for production..."
echo "------------------------------------"

php artisan config:cache
print_success "Configuration cached"

php artisan route:cache
print_success "Routes cached"

php artisan view:cache
print_success "Views cached"

php artisan event:cache 2>/dev/null || print_info "Event cache skipped (not all Laravel versions support this)"

echo ""
echo "Step 9: Final checks..."
echo "----------------------"

# Check if APP_ENV is production
if grep -q "APP_ENV=production" .env; then
    print_success "APP_ENV set to production"
else
    print_warning "APP_ENV is not set to production"
fi

# Check if APP_DEBUG is false
if grep -q "APP_DEBUG=false" .env; then
    print_success "APP_DEBUG set to false"
else
    print_error "APP_DEBUG should be false in production!"
fi

# Check if APP_URL is set
if grep -q "APP_URL=https://" .env; then
    print_success "APP_URL uses HTTPS"
elif grep -q "APP_URL=http://yourdomain.com" .env || grep -q "APP_URL=http://127.0.0.1" .env || grep -q "APP_URL=http://localhost" .env; then
    print_warning "APP_URL not configured for production domain"
fi

# Check database configuration
if grep -q "DB_DATABASE=your_database_name" .env; then
    print_error "Database configuration not set!"
fi

echo ""
echo "======================================"
echo "Deployment Complete!"
echo "======================================"
echo ""
print_info "Next steps:"
echo "1. Verify your .env configuration"
echo "2. Test the application at your domain"
echo "3. Login and CHANGE THE DEFAULT ADMIN PASSWORD"
echo "4. Enable HTTPS/SSL if not already enabled"
echo "5. Set up cron jobs if needed"
echo "6. Configure backups"
echo ""
print_warning "Important: Review DEPLOYMENT_GUIDE.md for post-deployment checklist"
echo ""
