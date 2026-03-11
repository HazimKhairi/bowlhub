#!/bin/bash

#################################################
# Hostinger Deployment Script
# Automated deployment for Bowling System
# Version: 1.0
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
echo -e "${YELLOW}[1/8] Pre-deployment validation...${NC}"

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
# PHASE 2: Clear All Caches
#################################################
echo -e "${YELLOW}[2/8] Clearing all caches...${NC}"

php artisan view:clear && echo -e "${GREEN}✓ View cache cleared${NC}" || echo -e "${RED}✗ Failed to clear view cache${NC}"
php artisan cache:clear && echo -e "${GREEN}✓ Application cache cleared${NC}" || echo -e "${RED}✗ Failed to clear cache${NC}"
php artisan config:clear && echo -e "${GREEN}✓ Config cache cleared${NC}" || echo -e "${RED}✗ Failed to clear config cache${NC}"
php artisan route:clear && echo -e "${GREEN}✓ Route cache cleared${NC}" || echo -e "${RED}✗ Failed to clear route cache${NC}"

echo ""

#################################################
# PHASE 3: Install Dependencies
#################################################
echo -e "${YELLOW}[3/8] Installing dependencies...${NC}"

composer install --no-dev --optimize-autoloader && echo -e "${GREEN}✓ Dependencies installed${NC}" || echo -e "${RED}✗ Composer install failed${NC}"

echo ""

#################################################
# PHASE 4: Create Storage Directories
#################################################
echo -e "${YELLOW}[4/8] Creating storage directories...${NC}"

mkdir -p storage/app/public/receipts && echo -e "${GREEN}✓ Receipts directory created${NC}"
mkdir -p storage/framework/cache && echo -e "${GREEN}✓ Framework cache directory created${NC}"
mkdir -p storage/framework/sessions && echo -e "${GREEN}✓ Sessions directory created${NC}"
mkdir -p storage/framework/views && echo -e "${GREEN}✓ Views directory created${NC}"
mkdir -p storage/logs && echo -e "${GREEN}✓ Logs directory created${NC}"

echo ""

#################################################
# PHASE 5: Setup Storage Symlink
#################################################
echo -e "${YELLOW}[5/8] Setting up storage symlink...${NC}"

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
# PHASE 6: Set Permissions
#################################################
echo -e "${YELLOW}[6/8] Setting permissions...${NC}"

chmod -R 775 storage bootstrap/cache && echo -e "${GREEN}✓ Storage permissions set${NC}" || echo -e "${RED}✗ Failed to set permissions${NC}"
chmod +x artisan && echo -e "${GREEN}✓ Artisan made executable${NC}" || echo -e "${RED}✗ Failed to make artisan executable${NC}"

echo ""

#################################################
# PHASE 7: Run Database Migrations
#################################################
echo -e "${YELLOW}[7/8] Running database migrations...${NC}"

php artisan migrate --force && echo -e "${GREEN}✓ Migrations completed${NC}" || echo -e "${RED}✗ Migrations failed${NC}"

echo ""

#################################################
# PHASE 8: Optimize Laravel
#################################################
echo -e "${YELLOW}[8/8] Optimizing Laravel...${NC}"

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
  - All caches cleared
  - Dependencies installed
  - Storage structure set up
  - Permissions configured
  - Database migrated
  - Laravel optimized

${BLUE}Next Steps:${NC}
  1. Clear your browser cache (Cmd+Shift+R)
  2. Test your website
  3. Check functionality

${BLUE}Quick Test Commands:${NC}
  php artisan --version
  ls -la storage/receipts

${GREEN}Happy deploying!${NC}

EOF
