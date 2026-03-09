#!/bin/bash

# Create deployment package for Hostinger
echo "Creating deployment package..."

# Create temp directory
rm -rf deployment-package
mkdir -p deployment-package

# Copy necessary directories (only those that exist)
[ -d "app" ] && cp -r app deployment-package/
[ -d "bootstrap" ] && cp -r bootstrap deployment-package/
[ -d "config" ] && cp -r config deployment-package/
[ -d "database" ] && cp -r database deployment-package/
[ -d "lang" ] && cp -r lang deployment-package/
[ -d "public" ] && cp -r public deployment-package/
[ -d "resources" ] && cp -r resources deployment-package/
[ -d "routes" ] && cp -r routes deployment-package/
[ -d "storage" ] && cp -r storage deployment-package/
[ -d "tests" ] && cp -r tests deployment-package/

# Copy root files
cp artisan deployment-package/ 2>/dev/null
cp composer.json deployment-package/ 2>/dev/null
cp composer.lock deployment-package/ 2>/dev/null
cp .env.production deployment-package/.env 2>/dev/null

# Create empty vendor directory (placeholder)
mkdir -p deployment-package/vendor

echo "Deployment package created successfully!"
echo "Location: deployment-package/"
echo "Size: $(du -sh deployment-package | cut -f1)"
