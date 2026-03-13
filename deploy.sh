#!/bin/bash

#################################################
# Hostinger Deployment Script
# Automated deployment for Bowling System
# Version: 1.1
#################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Hostinger Deployment Script${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

#################################################
# PHASE 1: Pre-deployment Checks
#################################################
echo -e "${YELLOW}[1/9] Pre-deployment validation...${NC}"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan not found. Are you in public_html?${NC}"
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}Error: .env file not found.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Pre-deployment checks passed${NC}"
echo ""

#################################################
# PHASE 1.5: Restore Storage from Backup
#################################################
echo -e "${YELLOW}[1.5/9] Restoring storage files from backup...${NC}"

# List available backups and let user choose
echo ""
echo -e "${BLUE}Available backups with storage:${NC}"
echo ""

BACKUP_OPTIONS=()
COUNTER=0

# Check each backup for storage files
for backup in public_html_backup public_html_backup1 public_html_backup2 public_html_backup3; do
    if [ -d "../$backup/storage" ] && [ -d "../$backup/storage/app/public/receipts" ] && [ -d "../$backup/storage/app/public/receipts/" ] && [ "$(ls -A ../$backup/storage/app/public/receipts/)" ]; then
        FILE_COUNT=$(ls -1 ../$backup/storage/app/public/receipts/ 2>/dev/null | wc -l)
        BACKUP_OPTIONS+=("$backup ($FILE_COUNT files)")
        COUNTER=$((COUNTER + 1))
        echo -e "  [$COUNTER] $backup ($FILE_COUNT files)"
    fi
done

echo ""

# If no backups found
if [ $COUNTER -eq 0 ]; then
    echo -e "${YELLOW}⚠ No backup storage found, starting fresh${NC}"
elif [ $COUNTER -eq 1 ]; then
    # Only one backup available, use it automatically
    BACKUP_TO_USE="${BACKUP_OPTIONS[0]%% *}"
    BACKUP_PATH="../$BACKUP_TO_USE/storage/app/public/receipts/"
    cp -r $BACKUP_PATH* storage/app/public/receipts/ 2>/dev/null
    echo -e "${GREEN}✓ Automatically restored from $BACKUP_TO_USE${NC}"
else
    # Multiple backups available, prompt user
    echo -e "${YELLOW}Which backup do you want to restore storage from?${NC}"
    echo -e "${YELLOW}Enter number (1-$COUNTER) or press Enter to skip:${NC}"
    read -r CHOICE

    if [ -n "$CHOICE" ] && [ "$CHOICE" -ge 1 ] && [ "$CHOICE" -le $COUNTER ]; then
        BACKUP_TO_USE="${BACKUP_OPTIONS[$CHOICE-1]%% *}"
        BACKUP_PATH="../$BACKUP_TO_USE/storage/app/public/receipts/"
        cp -r $BACKUP_PATH* storage/app/public/receipts/ 2>/dev/null
        echo -e "${GREEN}✓ Storage files restored from $BACKUP_TO_USE${NC}"
    else
        echo -e "${YELLOW}⚠ Skipped storage restore${NC}"
    fi
fi

echo ""

echo ""

#################################################
# PHASE 2: Install Dependencies
#################################################
echo -e "${YELLOW}[3/9] Installing dependencies...${NC}"

composer install --no-dev --optimize-autoloader && echo -e "${GREEN}✓ Dependencies installed${NC}" || echo -e "${RED}✗ Composer install failed${NC}"

echo ""

#################################################
# PHASE 3: Create Storage Directories
#################################################
echo -e "${YELLOW}[4/9] Creating storage directories...${NC}"

mkdir -p storage/app/public/receipts && echo -e "${GREEN}✓ Receipts directory created${NC}"
mkdir -p storage/framework/cache && echo -e "${GREEN}✓ Framework cache directory created${NC}"
mkdir -p storage/framework/sessions && echo -e "${GREEN}✓ Sessions directory created${NC}"
mkdir -p storage/framework/views && echo -e "${GREEN}✓ Views directory created${NC}"
mkdir -p storage/logs && echo -e "${GREEN}✓ Logs directory created${NC}"

echo ""

#################################################
# PHASE 4: Setup Storage Symlink
#################################################
echo -e "${YELLOW}[5/9] Setting up storage symlink...${NC}"

cd storage

# Remove existing symlink if it exists
if [ -L "receipts" ]; then
    rm -f receipts
    echo -e "${GREEN}✓ Removed old symlink${NC}"
fi

# Create new symlink
ln -s app/public/receipts receipts && echo -e "${GREEN}✓ Storage symlink created${NC}" || echo -e "${RED}✗ Failed to create symlink${NC}"

cd ..

echo ""

#################################################
# PHASE 5: Set Permissions
#################################################
echo -e "${YELLOW}[6/9] Setting permissions...${NC}"

chmod -R 775 storage bootstrap/cache && echo -e "${GREEN}✓ Storage permissions set${NC}" || echo -e "${RED}✗ Failed to set permissions${NC}"
chmod +x artisan && echo -e "${GREEN}✓ Artisan made executable${NC}" || echo -e "${RED}✗ Failed to make artisan executable${NC}"

echo ""

#################################################
# PHASE 6: Run Database Migrations
#################################################
echo -e "${YELLOW}[7/9] Running database migrations...${NC}"

php artisan migrate --force && echo -e "${GREEN}✓ Migrations completed${NC}" || echo -e "${RED}✗ Migrations failed${NC}"

echo ""

#################################################
# PHASE 7: Clear All Caches
#################################################
echo -e "${YELLOW}[8/9] Clearing all caches...${NC}"

php artisan view:clear && echo -e "${GREEN}✓ View cache cleared${NC}" || echo -e "${RED}✗ Failed to clear view cache${NC}"
php artisan cache:clear && echo -e "${GREEN}✓ Application cache cleared${NC}" || echo -e "${RED}✗ Failed to clear cache${NC}"
php artisan config:clear && echo -e "${GREEN}✓ Config cache cleared${NC}" || echo -e "${RED}✗ Failed to clear config cache${NC}"
php artisan route:clear && echo -e "${GREEN}✓ Route cache cleared${NC}" || echo -e "${RED}✗ Failed to clear route cache${NC}"

echo ""

#################################################
# PHASE 8: Optimize Laravel
#################################################
echo -e "${YELLOW}[9/9] Optimizing Laravel...${NC}"

php artisan config:cache && echo -e "${GREEN}✓ Config cached${NC}" || echo -e "${RED}✗ Failed to cache config${NC}"
php artisan route:cache && echo -e "${GREEN}✓ Routes cached${NC}" || echo -e "${RED}✗ Failed to cache routes${NC}"
php artisan view:cache && echo -e "${GREEN}✓ Views cached${NC}" || echo -e "${RED}✗ Failed to cache views${NC}"

echo ""

#################################################
# DEPLOYMENT COMPLETE
#################################################
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}DEPLOYMENT COMPLETED!${NC}"
echo -e "${GREEN}========================================${NC}"

cat << EOF

${BLUE}Deployment Summary:${NC}
  - Storage files restored from backup (if available)
  - Dependencies installed
  - Storage structure set up
  - Permissions configured
  - Database migrated
  - All caches cleared
  - Laravel optimized

${BLUE}Next Steps:${NC}
  1. Clear your browser cache (Cmd+Shift+R)
  2. Test your website
  3. Check functionality

${BLUE}Note:${NC}
  Storage files are automatically preserved from public_html_backup
  No need to manually copy storage anymore!

${BLUE}Quick Test Commands:${NC}
  php artisan --version
  ls -la storage/receipts

${GREEN}Happy deploying!${NC}

EOF
