#!/bin/bash

#################################################
# Laravel to Hostinger Deployment Script
# Author: Automated deployment solution
# Version: 1.0
# Compatible: Laravel 12.x, Hostinger Shared Hosting
#################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="bowling-system"
PROJECT_DIR="/Users/radhifauzan/Desktop/side-projects/bowling-system-backend"
DEPLOY_DIR="${PROJECT_DIR}/hostinger-deploy"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
ZIP_FILE="${PROJECT_DIR}/${PROJECT_NAME}-deploy-${TIMESTAMP}.zip"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Hostinger Deployment Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

#################################################
# PHASE 1: Pre-deployment Checks
#################################################
echo -e "${YELLOW}[1/8] Pre-deployment validation...${NC}"

# Check if we're in the right directory
if [ ! -f "${PROJECT_DIR}/artisan" ]; then
    echo -e "${RED}Error: artisan.php not found. Are you in the Laravel project root?${NC}"
    exit 1
fi

# Check if .env.production exists
if [ ! -f "${PROJECT_DIR}/.env.production" ]; then
    echo -e "${RED}Error: .env.production not found. Please create it first.${NC}"
    exit 1
fi

# Check required tools
if ! command -v zip &> /dev/null; then
    echo -e "${RED}Error: zip command not found. Please install it.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Pre-deployment checks passed${NC}"
echo ""

#################################################
# PHASE 2: Clean Previous Deployments
#################################################
echo -e "${YELLOW}[2/8] Cleaning previous deployments...${NC}"

# Remove old deployment directory
if [ -d "$DEPLOY_DIR" ]; then
    rm -rf "$DEPLOY_DIR"
    echo -e "${GREEN}✓ Removed old deployment directory${NC}"
fi

# Remove old deployment zips (keep last 3)
find "$PROJECT_DIR" -name "${PROJECT_NAME}-deploy-*.zip" -type f -mtime +7 -delete 2>/dev/null || true

# Create fresh deployment directory
mkdir -p "$DEPLOY_DIR"

echo -e "${GREEN}✓ Deployment directory prepared${NC}"
echo ""

#################################################
# PHASE 3: Copy Project Files
#################################################
echo -e "${YELLOW}[3/8] Copying project files...${NC}"

# Copy core directories (these stay in root)
cp -r "$PROJECT_DIR/app" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/bootstrap" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/config" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/database" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/resources" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/routes" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/storage" "$DEPLOY_DIR/"
cp -r "$PROJECT_DIR/tests" "$DEPLOY_DIR/"

# Copy root files
cp "$PROJECT_DIR/artisan" "$DEPLOY_DIR/"
cp "$PROJECT_DIR/composer.json" "$DEPLOY_DIR/"
cp "$PROJECT_DIR/composer.lock" "$DEPLOY_DIR/"

# Copy public directory contents to root
cp -r "$PROJECT_DIR/public/"* "$DEPLOY_DIR/"

# Copy production .env
cp "$PROJECT_DIR/.env.production" "$DEPLOY_DIR/.env"

# Copy deployment script for Hostinger
if [ -f "$PROJECT_DIR/deploy.sh" ]; then
    cp "$PROJECT_DIR/deploy.sh" "$DEPLOY_DIR/"
    echo -e "${GREEN}✓ Deployment script included${NC}"
else
    echo -e "${RED}✗ Warning: deploy.sh not found in project root${NC}"
fi

# Create placeholder vendor directory
mkdir -p "$DEPLOY_DIR/vendor"

echo -e "${GREEN}✓ Project files copied${NC}"
echo ""

#################################################
# PHASE 4: Fix index.php for Hostinger
#################################################
echo -e "${YELLOW}[4/8] Fixing index.php paths for Hostinger...${NC}"

cat > "$DEPLOY_DIR/index.php" << 'EOF'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
EOF

echo -e "${GREEN}✓ index.php fixed for Hostinger structure${NC}"
echo ""

#################################################
# PHASE 5: Create .htaccess for Hostinger
#################################################
echo -e "${YELLOW}[5/9] Creating .htaccess for Hostinger...${NC}"

cat > "$DEPLOY_DIR/.htaccess" << 'EOF'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Handle X-XSRF-Token Header
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
EOF

echo -e "${GREEN}✓ .htaccess created for Hostinger${NC}"
echo ""

#################################################
# PHASE 6: Fix Storage Symlink Structure
#################################################
echo -e "${YELLOW}[5/8] Setting up storage structure for Hostinger...${NC}"

# Remove the public/storage directory if it exists
if [ -d "$DEPLOY_DIR/storage" ]; then
    # Keep the storage directory but ensure proper structure
    mkdir -p "$DEPLOY_DIR/storage/app/public/receipts"
    mkdir -p "$DEPLOY_DIR/storage/app/public/templates"
    mkdir -p "$DEPLOY_DIR/storage/framework/cache"
    mkdir -p "$DEPLOY_DIR/storage/framework/sessions"
    mkdir -p "$DEPLOY_DIR/storage/framework/views"
    mkdir -p "$DEPLOY_DIR/storage/logs"
fi

echo -e "${GREEN}✓ Storage structure prepared${NC}"
echo ""

#################################################
# PHASE 7: Create Deployment Instructions
#################################################
echo -e "${YELLOW}[7/9] Creating deployment instructions...${NC}"

cat > "$DEPLOY_DIR/DEPLOY_INSTRUCTIONS.txt" << 'EOF'
HOSTINGER DEPLOYMENT INSTRUCTIONS
==================================

AUTOMATED STEPS (run in order):
==================================

1. Clear view cache:
   php artisan view:clear

2. Install dependencies:
   composer install --no-dev --optimize-autoloader

3. Set permissions:
   chmod -R 775 storage bootstrap/cache
   chmod +x artisan

4. Run database migrations:
   php artisan migrate --force

5. Optimize Laravel:
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache

6. Create storage symlink:
   cd storage
   ln -s app/public/receipts receipts

7. Clean up test files:
   rm test.php testinfo.php test-laravel.php test-route.php

VERIFICATION:
==================================
Test these URLs in your browser:
- Homepage: https://ukhuwah-strike-challenge.site
- Admin: https://ukhuwah-strike-challenge.site/admin
- Registration: https://ukhuwah-strike-challenge.site/daftar
- Leaderboard: https://ukhuwah-strike-challenge.site/kedudukan

TROUBLESHOOTING:
==================================
If you get 500 errors:
1. Check .env file exists and has correct database credentials
2. Verify storage/ directory has write permissions
3. Check Laravel logs: storage/logs/laravel.log

If assets don't load:
1. Clear browser cache (Cmd+Shift+R)
2. Check file permissions on css/ and js/ folders

If database doesn't connect:
1. Verify database credentials in .env
2. Check database exists in Hostinger MySQL Databases
3. Ensure database user has proper permissions
EOF

echo -e "${GREEN}✓ Deployment instructions created${NC}"
echo ""

#################################################
# PHASE 8: Create ZIP Package
#################################################
echo -e "${YELLOW}[8/9] Creating deployment ZIP package...${NC}"

cd "$DEPLOY_DIR"
zip -r "$ZIP_FILE" . -q
cd "$PROJECT_DIR"

# Get file size
FILE_SIZE=$(ls -lh "$ZIP_FILE" | awk '{print $5}')
FILE_COUNT=$(find "$DEPLOY_DIR" -type f | wc -l)

echo -e "${GREEN}✓ Deployment package created${NC}"
echo -e "  File: $(basename "$ZIP_FILE")"
echo -e "  Size: $FILE_SIZE"
echo -e "  Files: $FILE_COUNT"
echo ""

#################################################
# PHASE 9: Generate Summary
#################################################
echo -e "${YELLOW}[9/9] Deployment summary...${NC}"

cat << EOF

${GREEN}========================================${NC}
${GREEN}DEPLOYMENT READY!${NC}
${GREEN}========================================${NC}

${BLUE}Package Details:${NC}
  Location: ${ZIP_FILE}
  Size: ${FILE_SIZE}
  Files: ${FILE_COUNT}

${BLUE}Next Steps:${NC}
  1. Upload ${ZIP_FILE} to Hostinger File Manager
  2. Extract files to public_html/
  3. Follow DEPLOY_INSTRUCTIONS.txt
  4. Clear your browser cache
  5. Test your website!

${BLUE}Documentation:${NC}
  - COMPREHENSIVE_DEPLOYMENT_GUIDE.md
  - QUICK_TROUBLESHOOTING.md
  - DEPLOYMENT_SUMMARY.md

${YELLOW}Remember:${NC}
  - Keep your .env.production file updated
  - Always test locally before deploying
  - Backup your database before major changes
  - Never edit files directly in production

${GREEN}Happy deploying! 🚀${NC}

EOF

# Make the script executable
chmod +x "$0"

echo ""
echo -e "${GREEN}✓ Deployment script completed successfully!${NC}"
echo ""
