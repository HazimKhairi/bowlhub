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
echo -e "${YELLOW}[1.5/9] Restoring storage from backup...${NC}"

echo ""
echo -e "${BLUE}Available storage backups:${NC}"
echo ""

BACKUP_OPTIONS=()
COUNTER=0

# Find all directories matching public_html_v*
for backup in ../public_html_v*; do
    if [ -d "$backup" ] && [ -d "$backup/storage" ]; then
        BACKUP_NAME=$(basename "$backup")
        BACKUP_OPTIONS+=("$backup")
        COUNTER=$((COUNTER + 1))
        echo -e "  [$COUNTER] $BACKUP_NAME"
    fi
done

echo ""

# If no backups found
if [ $COUNTER -eq 0 ]; then
    echo -e "${YELLOW}⚠ No storage backups found (public_html_v*), skipping${NC}"
elif [ $COUNTER -eq 1 ]; then
    # Only one backup available, use it automatically
    BACKUP_TO_USE="${BACKUP_OPTIONS[0]}"
    BACKUP_NAME=$(basename "$BACKUP_TO_USE")

    # Step 1: Remove receipts inside storage
    if [ -d "storage/receipts" ]; then
        rm -rf storage/receipts
        echo -e "${GREEN}✓ Removed storage/receipts${NC}"
    fi

    # Step 2: Copy storage from backup
    cp -r "$BACKUP_TO_USE/storage/"* storage/ 2>/dev/null
    echo -e "${GREEN}✓ Copied storage from $BACKUP_NAME${NC}"

    # Step 3: Clear caches
    php artisan view:clear && echo -e "${GREEN}✓ View cache cleared${NC}"
    php artisan cache:clear && echo -e "${GREEN}✓ Application cache cleared${NC}"
    php artisan config:clear && echo -e "${GREEN}✓ Config cache cleared${NC}"
else
    # Multiple backups available, prompt user
    echo -e "${YELLOW}Which backup do you want to restore storage from?${NC}"
    echo -e "${YELLOW}Enter number (1-$COUNTER) or press Enter to skip:${NC}"
    read -r CHOICE

    if [ -n "$CHOICE" ] && [ "$CHOICE" -ge 1 ] && [ "$CHOICE" -le $COUNTER ]; then
        BACKUP_TO_USE="${BACKUP_OPTIONS[$CHOICE-1]}"
        BACKUP_NAME=$(basename "$BACKUP_TO_USE")

        # Step 1: Remove receipts inside storage
        if [ -d "storage/receipts" ]; then
            rm -rf storage/receipts
            echo -e "${GREEN}✓ Removed storage/receipts${NC}"
        fi

        # Step 2: Copy storage from backup
        cp -r "$BACKUP_TO_USE/storage/"* storage/ 2>/dev/null
        echo -e "${GREEN}✓ Copied storage from $BACKUP_NAME${NC}"

        # Step 3: Clear caches
        php artisan view:clear && echo -e "${GREEN}✓ View cache cleared${NC}"
        php artisan cache:clear && echo -e "${GREEN}✓ Application cache cleared${NC}"
        php artisan config:clear && echo -e "${GREEN}✓ Config cache cleared${NC}"
    else
        echo -e "${YELLOW}⚠ Skipped storage restore${NC}"
    fi
fi

echo ""

#################################################
# PHASE 2: Install Dependencies
#################################################
echo -e "${YELLOW}[3/9] Installing dependencies...${NC}"

composer install --no-dev --optimize-autoloader && echo -e "${GREEN}✓ Dependencies installed${NC}" || echo -e "${RED}✗ Composer install failed${NC}"

echo ""

#################################################
# PHASE 3: Set Permissions
#################################################
echo -e "${YELLOW}[4/6] Setting permissions...${NC}"

chmod -R 775 storage bootstrap/cache && echo -e "${GREEN}✓ Storage permissions set${NC}" || echo -e "${RED}✗ Failed to set permissions${NC}"
chmod +x artisan && echo -e "${GREEN}✓ Artisan made executable${NC}" || echo -e "${RED}✗ Failed to make artisan executable${NC}"

echo ""

#################################################
# PHASE 4: Run Database Migrations
#################################################
echo -e "${YELLOW}[5/6] Running database migrations...${NC}"

php artisan migrate --force && echo -e "${GREEN}✓ Migrations completed${NC}" || echo -e "${RED}✗ Migrations failed${NC}"

echo ""

#################################################
# PHASE 5: Clear All Caches
#################################################
echo -e "${YELLOW}[6/6] Clearing all caches...${NC}"

php artisan view:clear && echo -e "${GREEN}✓ View cache cleared${NC}" || echo -e "${RED}✗ Failed to clear view cache${NC}"
php artisan cache:clear && echo -e "${GREEN}✓ Application cache cleared${NC}" || echo -e "${RED}✗ Failed to clear cache${NC}"
php artisan config:clear && echo -e "${GREEN}✓ Config cache cleared${NC}" || echo -e "${RED}✗ Failed to clear config cache${NC}"
php artisan route:clear && echo -e "${GREEN}✓ Route cache cleared${NC}" || echo -e "${RED}✗ Failed to clear route cache${NC}"

echo ""

#################################################
# PHASE 6: Optimize Laravel
#################################################
echo -e "${YELLOW}[6/6] Optimizing Laravel...${NC}"

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
