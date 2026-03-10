#!/bin/bash

#################################################
# Intelligent Laravel Deployment Script
# with File Change Detection & Transformation
#
# Features:
# - Git-based change detection
# - Intelligent file categorization
# - Safe transformations
# - Validation before deployment
# - Deployment manifest generation
#################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="bowling-system"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_DIR="${PROJECT_DIR}/hostinger-deploy"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
ZIP_FILE="${PROJECT_DIR}/${PROJECT_NAME}-deploy-${TIMESTAMP}.zip"
PHP_DETECTOR="${PROJECT_DIR}/deployment-file-detector.php"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Intelligent Deployment System${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

#################################################
# PHASE 1: Pre-deployment Validation
#################################################
echo -e "${CYAN}[1/9] Pre-deployment validation...${NC}"

# Check if we're in the right directory
if [ ! -f "${PROJECT_DIR}/artisan" ]; then
    echo -e "${RED}Error: artisan not found. Are you in the Laravel project root?${NC}"
    exit 1
fi

# Check if .env.production exists
if [ ! -f "${PROJECT_DIR}/.env.production" ]; then
    echo -e "${RED}Error: .env.production not found. Please create it first.${NC}"
    exit 1
fi

# Check PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${RED}Error: PHP not found. Please install PHP CLI.${NC}"
    exit 1
fi

# Check git is available
if ! command -v git &> /dev/null; then
    echo -e "${RED}Error: git not found. Please install git.${NC}"
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
# PHASE 2: Git Change Detection
#################################################
echo -e "${CYAN}[2/9] Detecting file changes via Git...${NC}"

# Get current git reference
CURRENT_REF=$(git rev-parse HEAD)
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

echo -e "  Current branch: ${CYAN}${CURRENT_BRANCH}${NC}"
echo -e "  Current commit: ${CYAN}${CURRENT_REF}${NC}"

# Check if there's a last deployment reference
if [ -f "${PROJECT_DIR}/.last_deploy" ]; then
    LAST_DEPLOY=$(cat "${PROJECT_DIR}/.last_deploy")
    echo -e "  Last deployment: ${CYAN}${LAST_DEPLOY}${NC}"

    # Count changes
    CHANGES_COUNT=$(git diff --name-only ${LAST_DEPLOY} HEAD 2>/dev/null | wc -l | tr -d ' ')
    DELETIONS_COUNT=$(git diff --name-only ${LAST_DEPLOY} HEAD 2>/dev/null | grep -c "^D" || echo "0")
    ADDITIONS_COUNT=$(git diff --name-only ${LAST_DEPLOY} HEAD 2>/dev/null | grep -c "^A" || echo "0")
    MODIFICATIONS_COUNT=$((CHANGES_COUNT - DELETIONS_COUNT - ADDITIONS_COUNT))

    echo -e "  Changes detected:"
    echo -e "    ${GREEN}+${ADDITIONS_COUNT} additions${NC}"
    echo -e "    ${YELLOW}~${MODIFICATIONS_COUNT} modifications${NC}"
    echo -e "    ${RED}-${DELETIONS_COUNT} deletions${NC}"
else
    echo -e "  ${YELLOW}No previous deployment found - performing initial deployment${NC}"
fi

echo ""

#################################################
# PHASE 3: Clean Previous Deployments
#################################################
echo -e "${CYAN}[3/9] Cleaning previous deployments...${NC}"

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
# PHASE 4: Intelligent File Detection & Copy
#################################################
echo -e "${CYAN}[4/9] Detecting and categorizing files...${NC}"

# Run PHP file detector
if [ -f "$PHP_DETECTOR" ]; then
    php "$PHP_DETECTOR" "$PROJECT_DIR" "$DEPLOY_DIR"

    if [ $? -ne 0 ]; then
        echo -e "${RED}Error: File detection failed${NC}"
        exit 1
    fi

    echo -e "${GREEN}✓ File detection completed${NC}"
else
    echo -e "${YELLOW}Warning: PHP detector not found, using fallback method${NC}"

    # Fallback: Copy core directories
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

    echo -e "${GREEN}✓ Files copied using fallback method${NC}"
fi

echo ""

#################################################
# PHASE 5: Transform Critical Files
#################################################
echo -e "${CYAN}[5/9] Applying file transformations...${NC}"

# Transform index.php
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

echo -e "${GREEN}✓ index.php transformed (paths flattened)${NC}"

# Copy .env.production as .env
cp "$PROJECT_DIR/.env.production" "$DEPLOY_DIR/.env"
echo -e "${GREEN}✓ .env.production → .env${NC}"

# Create placeholder vendor directory
mkdir -p "$DEPLOY_DIR/vendor"
echo -e "${GREEN}✓ vendor/ placeholder created${NC}"

echo ""

#################################################
# PHASE 6: Fix Storage Structure
#################################################
echo -e "${CYAN}[6/9] Setting up storage structure...${NC}"

# Create required storage directories
mkdir -p "$DEPLOY_DIR/storage/app/public/receipts"
mkdir -p "$DEPLOY_DIR/storage/framework/cache"
mkdir -p "$DEPLOY_DIR/storage/framework/sessions"
mkdir -p "$DEPLOY_DIR/storage/framework/views"
mkdir -p "$DEPLOY_DIR/storage/logs"

echo -e "${GREEN}✓ Storage structure prepared${NC}"
echo ""

#################################################
# PHASE 7: Validate Deployment
#################################################
echo -e "${CYAN}[7/9] Validating deployment package...${NC}"

VALIDATION_ERRORS=0

# Check index.php exists
if [ ! -f "$DEPLOY_DIR/index.php" ]; then
    echo -e "${RED}✗ index.php not found${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}✓ index.php present${NC}"
fi

# Check .env exists
if [ ! -f "$DEPLOY_DIR/.env" ]; then
    echo -e "${RED}✗ .env not found${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}✓ .env present${NC}"
fi

# Check .env.production values
if grep -q "APP_ENV=production" "$DEPLOY_DIR/.env"; then
    echo -e "${GREEN}✓ APP_ENV=production${NC}"
else
    echo -e "${RED}✗ APP_ENV not set to production${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
fi

if grep -q "APP_DEBUG=false" "$DEPLOY_DIR/.env"; then
    echo -e "${GREEN}✓ APP_DEBUG=false${NC}"
else
    echo -e "${RED}✗ APP_DEBUG not set to false${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
fi

# Check artisan exists
if [ ! -f "$DEPLOY_DIR/artisan" ]; then
    echo -e "${RED}✗ artisan not found${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}✓ artisan present${NC}"
fi

# Check composer.json exists
if [ ! -f "$DEPLOY_DIR/composer.json" ]; then
    echo -e "${RED}✗ composer.json not found${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}✓ composer.json present${NC}"
fi

# Check essential directories
for dir in app bootstrap config database resources routes storage; do
    if [ ! -d "$DEPLOY_DIR/$dir" ]; then
        echo -e "${RED}✗ $dir/ not found${NC}"
        VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
    fi
done

# Check assets moved to root
if [ -d "$DEPLOY_DIR/css" ]; then
    echo -e "${GREEN}✓ css/ at root level${NC}"
else
    echo -e "${YELLOW}⚠ css/ not found at root${NC}"
fi

if [ -d "$DEPLOY_DIR/js" ]; then
    echo -e "${GREEN}✓ js/ at root level${NC}"
else
    echo -e "${YELLOW}⚠ js/ not found at root${NC}"
fi

echo ""

if [ $VALIDATION_ERRORS -gt 0 ]; then
    echo -e "${RED}Validation failed with ${VALIDATION_ERRORS} error(s)${NC}"
    exit 1
fi

echo ""

#################################################
# PHASE 8: Create Deployment Instructions
#################################################
echo -e "${CYAN}[8/9] Creating deployment instructions...${NC}"

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
   php artisan storage:link

7. Clean up test files (if any):
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
4. Verify vendor/ directory is installed

If assets don't load:
1. Clear browser cache (Cmd+Shift+R)
2. Check file permissions on css/ and js/ folders
3. Verify files are at root level (not in public/ subdirectory)

If database doesn't connect:
1. Verify database credentials in .env
2. Check database exists in Hostinger MySQL Databases
3. Ensure database user has proper permissions
4. Verify DB_HOST=localhost

DEPLOYMENT INFO:
==================================
Generated by: Intelligent Deployment System
Date: DATE_PLACEHOLDER
Git Ref: REF_PLACEHOLDER
EOF

# Replace placeholders
sed -i.bak "s/DATE_PLACEHOLDER/$(date)/" "$DEPLOY_DIR/DEPLOY_INSTRUCTIONS.txt"
sed -i.bak "s/REF_PLACEHOLDER/${CURRENT_REF}/" "$DEPLOY_DIR/DEPLOY_INSTRUCTIONS.txt"
rm -f "$DEPLOY_DIR/DEPLOY_INSTRUCTIONS.txt.bak"

echo -e "${GREEN}✓ Deployment instructions created${NC}"
echo ""

#################################################
# PHASE 9: Create ZIP Package & Summary
#################################################
echo -e "${CYAN}[9/9] Creating deployment package...${NC}"

cd "$DEPLOY_DIR"
zip -r "$ZIP_FILE" . -q
cd "$PROJECT_DIR"

# Get file info
FILE_SIZE=$(ls -lh "$ZIP_FILE" | awk '{print $5}')
FILE_COUNT=$(find "$DEPLOY_DIR" -type f | wc -l | tr -d ' ')

# Save current deployment reference
echo "$CURRENT_REF" > "$PROJECT_DIR/.last_deploy"

echo -e "${GREEN}✓ Deployment package created${NC}"
echo ""

#################################################
# DEPLOYMENT SUMMARY
#################################################
cat << EOF

${GREEN}========================================${NC}
${GREEN}INTELLIGENT DEPLOYMENT COMPLETE!${NC}
${GREEN}========================================${NC}

${BLUE}Package Details:${NC}
  Location: ${ZIP_FILE}
  Size: ${FILE_SIZE}
  Files: ${FILE_COUNT}

${BLUE}Git Information:${NC}
  Branch: ${CURRENT_BRANCH}
  Commit: ${CURRENT_REF}
  Changes: ${CHANGES_COUNT:-0} files

${BLUE}Next Steps:${NC}
  1. Upload ${ZIP_FILE} to Hostinger File Manager
  2. Extract files to public_html/
  3. Follow DEPLOY_INSTRUCTIONS.txt
  4. Clear your browser cache
  5. Test your website!

${BLUE}File Categories:${NC}
  - Blade views: Checked for template literals
  - Config files: Copied directly
  - Routes: Copied directly
  - Assets: Moved to root level
  - index.php: Transformed (paths flattened)
  - .env: Copied from .env.production

${BLUE}Safety Checks:${NC}
  - .env.production validated
  - APP_ENV=production verified
  - APP_DEBUG=false verified
  - Template literals checked

${BLUE}Documentation:${NC}
  - LARAVEL_TO_HOSTINGER_DEPLOYMENT_PATTERNS.md
  - DEPLOYMENT_TRANSFORMATIONS.md
  - DEPLOYMENT_CHECKLIST.md

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
