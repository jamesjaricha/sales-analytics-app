#!/bin/bash

###############################################################################
# Pre-Deployment Security & Code Audit Script
#
# This script checks for common security issues and outdated code patterns
# before deploying to production
###############################################################################

set +e  # Don't exit on errors, we want to report all issues

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

ISSUES_FOUND=0

print_header() {
    echo ""
    echo -e "${BLUE}================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================================${NC}"
}

print_check() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠ WARNING:${NC} $1"
    ((ISSUES_FOUND++))
}

print_error() {
    echo -e "${RED}✗ ERROR:${NC} $1"
    ((ISSUES_FOUND++))
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_header "Sales Analytics - Pre-Deployment Security Audit"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    print_error "Not in Laravel root directory"
    exit 1
fi

print_check "Running from Laravel root directory"

print_header "1. Environment Configuration Checks"

# Check .env file
if [ ! -f ".env" ]; then
    print_error ".env file not found"
else
    print_check ".env file exists"

    # Check APP_DEBUG
    if grep -q "APP_DEBUG=true" .env; then
        print_warning "APP_DEBUG is set to true (should be false in production)"
    else
        print_check "APP_DEBUG is false"
    fi

    # Check APP_ENV
    if grep -q "APP_ENV=local" .env || grep -q "APP_ENV=development" .env; then
        print_warning "APP_ENV is not set to production"
    else
        print_check "APP_ENV is set to production"
    fi

    # Check APP_KEY
    if grep -q "APP_KEY=$" .env || ! grep -q "APP_KEY=base64:" .env; then
        print_error "APP_KEY is not set or invalid"
    else
        print_check "APP_KEY is configured"
    fi

    # Check APP_URL
    if grep -q "APP_URL=http://127.0.0.1" .env || grep -q "APP_URL=http://localhost" .env; then
        print_warning "APP_URL is set to localhost (should be your production domain)"
    else
        print_check "APP_URL is configured for production"
    fi

    # Check database configuration
    if grep -q "DB_DATABASE=your_database_name" .env || grep -q "DB_USERNAME=root" .env; then
        print_error "Database credentials appear to be default/placeholder values"
    else
        print_check "Database credentials configured"
    fi

    # Check SESSION_SECURE_COOKIE
    if ! grep -q "SESSION_SECURE_COOKIE=true" .env; then
        print_warning "SESSION_SECURE_COOKIE should be true for HTTPS"
    else
        print_check "SESSION_SECURE_COOKIE is enabled"
    fi

    # Check SESSION_ENCRYPT
    if ! grep -q "SESSION_ENCRYPT=true" .env; then
        print_warning "SESSION_ENCRYPT should be true for production"
    else
        print_check "SESSION_ENCRYPT is enabled"
    fi
fi

print_header "2. File Permission Checks"

# Check storage directory permissions
if [ -d "storage" ]; then
    if [ -w "storage" ]; then
        print_check "Storage directory is writable"
    else
        print_error "Storage directory is not writable"
    fi
else
    print_error "Storage directory not found"
fi

# Check bootstrap/cache permissions
if [ -d "bootstrap/cache" ]; then
    if [ -w "bootstrap/cache" ]; then
        print_check "Bootstrap cache directory is writable"
    else
        print_error "Bootstrap cache directory is not writable"
    fi
else
    print_error "Bootstrap cache directory not found"
fi

print_header "3. Security Headers Middleware Check"

if [ -f "app/Http/Middleware/SecurityHeaders.php" ]; then
    print_check "SecurityHeaders middleware exists"

    # Check if it's registered in bootstrap/app.php
    if grep -q "SecurityHeaders" bootstrap/app.php; then
        print_check "SecurityHeaders middleware is registered"
    else
        print_warning "SecurityHeaders middleware may not be registered"
    fi
else
    print_error "SecurityHeaders middleware not found"
fi

print_header "4. CSRF Protection Check"

# Check if CSRF middleware is enabled
if grep -q "VerifyCsrfToken" app/Http/Kernel.php 2>/dev/null || grep -q "VerifyCsrfToken" bootstrap/app.php 2>/dev/null; then
    print_check "CSRF protection is enabled"
else
    # Laravel 11 has it enabled by default
    print_check "CSRF protection (default Laravel 11 behavior)"
fi

print_header "5. SQL Injection Prevention Check"

# Check for raw queries (potential SQL injection)
RAW_QUERIES=$(grep -r "DB::raw\|->raw(" app/Http/Controllers/ 2>/dev/null | wc -l)
if [ "$RAW_QUERIES" -gt 0 ]; then
    print_warning "Found $RAW_QUERIES instances of raw SQL queries - review for SQL injection risks"
    grep -rn "DB::raw\|->raw(" app/Http/Controllers/ 2>/dev/null | head -5
else
    print_check "No raw SQL queries found in controllers"
fi

print_header "6. Mass Assignment Protection Check"

# Check if models have $fillable or $guarded
MODELS_WITHOUT_PROTECTION=$(find app/Models -name "*.php" -exec grep -L "\$fillable\|\$guarded" {} \; 2>/dev/null | wc -l)
if [ "$MODELS_WITHOUT_PROTECTION" -gt 0 ]; then
    print_warning "$MODELS_WITHOUT_PROTECTION model(s) without mass assignment protection"
else
    print_check "All models have mass assignment protection"
fi

print_header "7. Password Hashing Check"

# Check for unhashed passwords
UNHASHED_PASSWORDS=$(grep -rn "password.*=.*\$request" app/Http/Controllers/ | grep -v "Hash::make" | wc -l)
if [ "$UNHASHED_PASSWORDS" -gt 0 ]; then
    print_error "Found potential unhashed password assignments"
    grep -rn "password.*=.*\$request" app/Http/Controllers/ | grep -v "Hash::make"
else
    print_check "Password hashing appears to be implemented"
fi

print_header "8. XSS Prevention Check"

# Check for {!! !!} usage (unescaped output)
UNESCAPED_OUTPUT=$(grep -r "{!! " resources/views/ 2>/dev/null | wc -l)
if [ "$UNESCAPED_OUTPUT" -gt 5 ]; then
    print_warning "Found $UNESCAPED_OUTPUT instances of unescaped output - review for XSS risks"
else
    print_check "Minimal use of unescaped output"
fi

print_header "9. Authentication & Authorization Checks"

# Check if routes are protected with auth middleware
if grep -q "middleware.*auth" routes/web.php; then
    print_check "Authentication middleware is used in routes"
else
    print_warning "No authentication middleware found in routes"
fi

# Check for role-based access control
if grep -q "role:" routes/web.php || grep -q "can:" routes/web.php; then
    print_check "Role/permission-based authorization is implemented"
else
    print_warning "No role/permission-based authorization found"
fi

print_header "10. Rate Limiting Check"

if grep -q "throttle:" routes/web.php; then
    print_check "Rate limiting is implemented"
else
    print_warning "No rate limiting found in routes"
fi

print_header "11. Dependencies Check"

print_info "Checking for outdated dependencies..."

if command -v composer &> /dev/null; then
    composer outdated --direct 2>/dev/null | head -10
    print_check "Composer dependencies checked (see above)"
else
    print_warning "Composer not found - cannot check dependencies"
fi

print_header "12. .htaccess Security Check"

if [ -f "public/.htaccess" ]; then
    print_check ".htaccess file exists"

    if grep -q "Options -Indexes" public/.htaccess; then
        print_check "Directory listing is disabled"
    else
        print_warning "Directory listing might be enabled"
    fi

    if grep -q "X-Frame-Options\|X-Content-Type-Options" public/.htaccess; then
        print_check "Security headers configured in .htaccess"
    else
        print_info "Security headers not in .htaccess (using middleware instead)"
    fi
else
    print_error ".htaccess file not found in public directory"
fi

print_header "13. Sensitive File Exposure Check"

# Check if .env is in .gitignore
if grep -q "^\.env$" .gitignore; then
    print_check ".env is in .gitignore"
else
    print_error ".env is NOT in .gitignore - security risk!"
fi

# Check if vendor is in .gitignore
if grep -q "^/vendor" .gitignore; then
    print_check "vendor directory is in .gitignore"
else
    print_warning "vendor directory is not in .gitignore"
fi

print_header "14. Laravel Version Check"

LARAVEL_VERSION=$(php artisan --version 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
if [ ! -z "$LARAVEL_VERSION" ]; then
    print_info "Laravel version: $LARAVEL_VERSION"
    MAJOR_VERSION=$(echo $LARAVEL_VERSION | cut -d. -f1)
    if [ "$MAJOR_VERSION" -ge 11 ]; then
        print_check "Using Laravel 11+ (latest major version)"
    else
        print_warning "Using Laravel $MAJOR_VERSION - consider upgrading"
    fi
else
    print_warning "Could not determine Laravel version"
fi

print_header "15. Production Readiness Check"

# Check if composer.lock exists
if [ -f "composer.lock" ]; then
    print_check "composer.lock exists"
else
    print_error "composer.lock not found - run 'composer install'"
fi

# Check if package-lock.json exists
if [ -f "package-lock.json" ]; then
    print_check "package-lock.json exists"
else
    print_warning "package-lock.json not found"
fi

# Check if assets are built
if [ -d "public/build" ] && [ "$(ls -A public/build)" ]; then
    print_check "Production assets are built"
else
    print_error "Production assets not found - run 'npm run build'"
fi

print_header "16. Logging Configuration Check"

if [ -f "config/logging.php" ]; then
    if grep -q "LOG_LEVEL.*error" .env; then
        print_check "Log level set to error for production"
    else
        print_warning "Log level might be too verbose for production"
    fi

    if grep -q "LOG_CHANNEL.*daily" .env; then
        print_check "Using daily log rotation"
    else
        print_info "Not using daily log rotation"
    fi
fi

print_header "Audit Summary"

echo ""
if [ $ISSUES_FOUND -eq 0 ]; then
    echo -e "${GREEN}✓ No critical issues found!${NC}"
    echo -e "${GREEN}Your application appears ready for production deployment.${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠ Found $ISSUES_FOUND potential issue(s)${NC}"
    echo -e "${YELLOW}Please review and fix the warnings/errors above before deploying to production.${NC}"
    echo ""
    echo -e "${BLUE}Recommended actions:${NC}"
    echo "1. Review and fix all errors (✗)"
    echo "2. Review all warnings (⚠) and determine if they apply to your setup"
    echo "3. Run 'composer audit' to check for security vulnerabilities"
    echo "4. Run 'npm audit' to check for npm package vulnerabilities"
    echo "5. Update dependencies to latest secure versions"
    echo "6. Test the application thoroughly in a staging environment"
    echo ""
    exit 1
fi
