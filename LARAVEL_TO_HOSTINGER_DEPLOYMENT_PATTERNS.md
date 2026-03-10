# Laravel to Hostinger Deployment: Complete Pattern & Transformation Guide

## Overview

This guide documents the exact patterns, transformations, and configurations needed to deploy a Laravel application to Hostinger shared hosting. Based on real-world deployment experience, this guide provides actionable, step-by-step instructions to prevent common deployment issues.

**Project**: Bowling System (Laravel 12.0)
**Hosting**: Hostinger Shared Hosting
**Domain**: ukhuwah-strike-challenge.site
**PHP Version**: 8.2+
**Database**: MySQL

---

## Table of Contents

1. [Architecture Transformation](#1-architecture-transformation)
2. [File Modification Checklist](#2-file-modification-checklist)
3. [Transformation Matrix](#3-transformation-matrix)
4. [Configuration Change Template](#4-configuration-change-template)
5. [Common Gotchas & Prevention](#5-common-gotchas--prevention)
6. [Pre-Deployment Validation](#6-pre-deployment-validation)
7. [Post-Deployment Verification](#7-post-deployment-verification)
8. [Quick Reference Commands](#8-quick-reference-commands)

---

## 1. Architecture Transformation

### 1.1 Local Development Structure

```
bowling-system-backend/
├── app/                    # Application logic
├── bootstrap/              # Framework bootstrap
├── config/                 # Configuration files
├── database/               # Migrations & seeders
├── public/                 # Public web root (ENTRY POINT)
│   ├── index.php          # Main entry point
│   ├── .htaccess          # Apache rewrite rules
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript
│   └── storage → ../storage/app/public (symlink)
├── resources/              # Views & assets
├── routes/                 # Route definitions
├── storage/                # Application storage
├── tests/                  # Test files
├── vendor/                 # Composer dependencies
├── .env                    # Local environment
├── .env.production         # Production template
├── artisan                 # CLI tool
├── composer.json           # Dependencies
└── composer.lock           # Dependency lock
```

**Key Characteristics**:
- Public directory is separate from project root
- Vendor directory included locally
- Development environment active
- SQLite database for local testing
- Debug mode enabled

### 1.2 Hostinger Production Structure

```
public_html/               # Hostinger web root (FLATTENED STRUCTURE)
├── app/                   # Application logic
├── bootstrap/             # Framework bootstrap
├── config/                # Configuration files
├── database/              # Migrations & seeders
├── resources/             # Views & assets
├── routes/                # Route definitions
├── storage/               # Application storage
│   └── app/public/        # Public storage
├── tests/                 # Test files
├── vendor/                # Composer dependencies (INSTALLED ON SERVER)
├── css/                   # Stylesheets (MOVED FROM public/)
├── js/                    # JavaScript (MOVED FROM public/)
├── index.php              # Main entry point (MODIFIED PATHS)
├── .htaccess              # Apache rewrite rules
├── .env                   # Production environment
├── .env.production        # Backup
├── artisan                # CLI tool
├── composer.json          # Dependencies
├── composer.lock          # Dependency lock
├── favicon.ico            # Site icon
└── robots.txt             # SEO configuration
```

**Key Transformations**:
1. **Flattened structure**: No separate `public/` directory
2. **Modified paths**: `index.php` paths changed to work from root
3. **Server-side vendor**: Installed via Composer on server
4. **Production config**: Hardened security settings

### 1.3 Critical Architectural Differences

| Aspect | Local Development | Hostinger Production |
|--------|------------------|---------------------|
| **Document Root** | `project/public/` | `public_html/` |
| **Entry Point** | `public/index.php` | `index.php` |
| **Vendor Path** | `vendor/` | `vendor/` (same, but installed on server) |
| **Storage Path** | `storage/` | `storage/` (same) |
| **Asset Path** | `public/css/`, `public/js/` | `css/`, `js/` |
| **Public Storage** | `public/storage` (symlink) | Created manually |
| **Database** | SQLite (local) | MySQL (remote) |
| **Environment** | `APP_ENV=local` | `APP_ENV=production` |
| **Debug Mode** | `APP_DEBUG=true` | `APP_DEBUG=false` |

---

## 2. File Modification Checklist

### 2.1 Files That MUST Be Modified

#### A. index.php (CRITICAL)

**Location**: `public/index.php` (local) → `index.php` (production)

**Local Structure**:
```php
<?php
define('LARAVEL_START', microtime(true));

// Maintenance mode check
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
```

**Production Structure**:
```php
<?php
define('LARAVEL_START', microtime(true));

// Maintenance mode check
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Autoloader
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

**Changes Required**:
- Remove `../` from all paths (3 changes)
- File moves from `public/` to root

#### B. .env File

**Critical Variables to Change**:

```env
# Environment
APP_ENV=production                    # Changed from 'local'
APP_DEBUG=false                       # Changed from 'true'
APP_URL=https://yourdomain.com        # Changed from 'http://localhost'

# Database
DB_CONNECTION=mysql                   # May differ from local
DB_HOST=localhost                     # Hostinger MySQL host
DB_PORT=3306                          # Standard MySQL port
DB_DATABASE=your_db_name              # Hostinger database name
DB_USERNAME=your_db_user              # Hostinger database user
DB_PASSWORD=your_secure_password      # Strong password

# Session
SESSION_DOMAIN=.yourdomain.com        # Added for production
SESSION_DRIVER=database               # Recommended for production

# Logging
LOG_LEVEL=warning                     # Changed from 'debug'
```

#### C. .htaccess File

**Location**: `public/.htaccess` (local) → `.htaccess` (production)

**Content Remains the Same** but moves to root:
```apache
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
```

### 2.2 Files That Must Be Copied (No Changes)

| File | Local Path | Production Path | Notes |
|------|-----------|-----------------|-------|
| artisan | `/artisan` | `/artisan` | Make executable |
| composer.json | `/composer.json` | `/composer.json` | Required for composer install |
| composer.lock | `/composer.lock` | `/composer.lock` | Lock versions |
| app/ | `/app/*` | `/app/*` | All files |
| bootstrap/ | `/bootstrap/*` | `/bootstrap/*` | All files |
| config/ | `/config/*` | `/config/*` | All files |
| database/ | `/database/*` | `/database/*` | All files |
| resources/ | `/resources/*` | `/resources/*` | All files |
| routes/ | `/routes/*` | `/routes/*` | All files |
| storage/ | `/storage/*` | `/storage/*` | Directory structure only |
| tests/ | `/tests/*` | `/tests/*` | Optional |

### 2.3 Files That Move Location

| File Type | From | To | Reason |
|-----------|------|-----|---------|
| index.php | `public/index.php` | `index.php` | Entry point for flattened structure |
| .htaccess | `public/.htaccess` | `.htaccess` | Apache config at root |
| css/ | `public/css/*` | `css/*` | Assets at root level |
| js/ | `public/js/*` | `js/*` | Assets at root level |
| favicon.ico | `public/favicon.ico` | `favicon.ico` | Root level |
| robots.txt | `public/robots.txt` | `robots.txt` | SEO at root |

### 2.4 Files That Should NOT Be Deployed

| File/Directory | Reason |
|----------------|--------|
| `node_modules/` | Not needed for production, too large |
| `vendor/` | Installed on server via composer |
| `.git/` | Version control, not needed in production |
| `.env` (local) | Contains local credentials |
| `tests/` | Optional, not needed for runtime |
| `phpunit.xml` | Testing configuration |
| `.gitignore` | Not needed in production |
| `README.md` | Documentation, not required |
| `package.json` | Not needed if assets already built |
| `package-lock.json` | Not needed if assets already built |

---

## 3. Transformation Matrix

### 3.1 Path Transformation Reference

| Purpose | Local Path | Production Path | Command/Operation |
|---------|-----------|-----------------|-------------------|
| **Autoload** | `../vendor/autoload.php` | `vendor/autoload.php` | Modify in index.php |
| **Maintenance** | `../storage/framework/maintenance.php` | `storage/framework/maintenance.php` | Modify in index.php |
| **Bootstrap** | `../bootstrap/app.php` | `bootstrap/app.php` | Modify in index.php |
| **Storage Link** | `public/storage` | `storage/app/public/receipts` | Manual symlink creation |
| **Assets CSS** | `/css/style.css` | `/css/style.css` | No change (URL remains same) |
| **Assets JS** | `/js/app.js` | `/js/app.js` | No change (URL remains same) |

### 3.2 Environment Variable Transformation

| Variable | Local Value | Production Value | Impact |
|----------|-------------|------------------|---------|
| `APP_ENV` | `local` | `production` | Changes error handling, logging |
| `APP_DEBUG` | `true` | `false` | Hides detailed errors |
| `APP_URL` | `http://localhost` | `https://yourdomain.com` | URL generation |
| `DB_HOST` | `127.0.0.1` | `localhost` | Database connection |
| `DB_DATABASE` | `local_db_name` | `uXXXXXXXX_db_name` | Database selection |
| `DB_USERNAME` | `local_user` | `uXXXXXXXX_user` | Database authentication |
| `SESSION_DOMAIN` | `null` | `.yourdomain.com` | Session scope |
| `LOG_LEVEL` | `debug` | `warning` | Logging verbosity |

### 3.3 Permission Transformation

| Directory/File | Local Permissions | Production Permissions | Command |
|----------------|-------------------|------------------------|---------|
| All files | Default | `644` | `chmod 644 file` |
| All directories | Default | `755` | `chmod 755 directory` |
| `storage/` | Default | `775` | `chmod -R 775 storage/` |
| `bootstrap/cache/` | Default | `775` | `chmod -R 775 bootstrap/cache/` |
| `artisan` | Default | `775` | `chmod +x artisan` |

### 3.4 Database Transformation

| Aspect | Local | Production | Action Required |
|--------|-------|------------|-----------------|
| **Type** | SQLite | MySQL | Create MySQL database |
| **Host** | File-based | `localhost` | Configure in .env |
| **Credentials** | Not needed | Username/password | Set in .env |
| **Connection** | File path | TCP/IP | Laravel handles automatically |
| **Migrations** | Run manually | Run via SSH/Terminal | `php artisan migrate --force` |

---

## 4. Configuration Change Template

### 4.1 Production .env Template

Copy this template and customize for your deployment:

```env
APP_NAME="Your Application Name"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://yourdomain.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

# Custom Application Variables
ADMIN_PASSWORD=CHANGE_THIS_IMMEDIATELY

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXXXXXX_your_database_name
DB_USERNAME=uXXXXXXXX_your_username
DB_PASSWORD=your_secure_database_password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=.yourdomain.com

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

### 4.2 config/app.php Adjustments

**No code changes needed** - all controlled via .env variables:

```php
// These use .env values automatically
'env' => env('APP_ENV', 'production'),
'debug' => (bool) env('APP_DEBUG', false),
'url' => env('APP_URL', 'https://yourdomain.com'),
```

### 4.3 config/session.php Adjustments

**Critical for production**:

```php
// Ensure these match your .env
'driver' => env('SESSION_DRIVER', 'database'),
'domain' => env('SESSION_DOMAIN', '.yourdomain.com'),
```

### 4.4 config/database.php Adjustments

**Verify MySQL configuration**:

```php
'mysql' => [
    'driver' => 'mysql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    // ... rest of configuration
],
```

### 4.5 PHP Configuration (Hostinger hPanel)

Navigate to: **Hosting → Advanced → PHP Configuration**

**Required Settings**:
```ini
memory_limit = 256M
post_max_size = 20M
upload_max_filesize = 20M
max_execution_time = 300
max_input_vars = 5000
```

---

## 5. Common Gotchas & Prevention

### 5.1 Gotcha #1: Missing Vendor Directory

**Symptom**: 500 Internal Server Error
**Error Message**: "Class 'Illuminate\Foundation\Application' not found"

**Root Cause**: Vendor directory not installed on server

**Prevention**:
```bash
# ALWAYS run this after uploading files
composer install --no-dev --optimize-autoloader
```

**Verification**:
```bash
ls -la vendor/autoload.php  # Should exist
```

### 5.2 Gotcha #2: Incorrect Document Root

**Symptom**: File listing shown, or wrong page loads

**Root Cause**: Domain pointing to `public_html/` instead of `public_html/`

**Prevention**:
1. In Hostinger hPanel, go to **Domains → Your Domain → Manage**
2. Set **Document Root** to: `public_html`
3. Ensure files are flattened in `public_html/` (not in subdirectory)

**Verification**:
```bash
# Check index.php is at root, not in public/ subdirectory
ls -la public_html/index.php
```

### 5.3 Gotcha #3: Wrong index.php Paths

**Symptom**: 500 error, "No such file or directory"

**Root Cause**: index.php still using `../` paths

**Prevention**:
- Use deployment script that automatically fixes paths
- Or manually edit index.php to remove `../` from all paths

**Correct Structure**:
```php
require __DIR__.'/vendor/autoload.php';      // NOT ../vendor/
$app = require_once __DIR__.'/bootstrap/app.php';  // NOT ../bootstrap/
```

### 5.4 Gotcha #4: Storage Directory Not Writable

**Symptom**: 500 error, "Permission denied", logs not written

**Root Cause**: Insufficient permissions on storage directory

**Prevention**:
```bash
chmod -R 775 storage bootstrap/cache
chmod +x artisan
```

**Verification**:
```bash
# Test write permissions
touch storage/test.txt && rm storage/test.txt
```

### 5.5 Gotcha #5: Debug Mode in Production

**Symptom**: Detailed error pages shown to users

**Root Cause**: `APP_DEBUG=true` in production .env

**Prevention**:
- Always set `APP_DEBUG=false` in production
- Use `.env.production` as template
- Verify before deploying

**Verification**:
```bash
grep APP_DEBUG .env  # Should return: APP_DEBUG=false
```

### 5.6 Gotcha #6: Database Connection Failed

**Symptom**: "SQLSTATE[HY000] [2002] Connection refused"

**Root Cause**:
- Wrong database credentials
- Database not created
- Wrong host (should be `localhost`)

**Prevention**:
1. Create database in Hostinger first
2. Copy credentials exactly (including prefix)
3. Use `localhost` as host (not IP or domain)

**Verification**:
```bash
php artisan tinker
>>> DB::connection()->getPdo();
# Should return: PDO information without error
```

### 5.7 Gotcha #7: Old Cached Configuration

**Symptom**: Changes not reflecting, configuration errors

**Root Cause**: Old cached config from development

**Prevention**:
```bash
# Clear all caches after deployment
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5.8 Gotcha #8: Missing Storage Symlink

**Symptom**: Uploaded files not accessible via web

**Root Cause**: Storage symlink not created

**Prevention**:
```bash
# Standard Laravel method
php artisan storage:link

# Manual method for Hostinger
cd storage
ln -s app/public/receipts receipts
```

**Verification**:
```bash
ls -la public/storage  # Should be symlink
# Or for Hostinger structure:
ls -la storage/receipts  # Should be symlink
```

### 5.9 Gotcha #9: Session/Cookie Issues

**Symptom**: Users logged out frequently, session lost

**Root Cause**:
- Wrong session domain
- HTTP vs HTTPS mismatch
- Session driver misconfigured

**Prevention**:
```env
SESSION_DOMAIN=.yourdomain.com  # Note leading dot
SESSION_DRIVER=database  # More reliable than file
```

**Verification**:
- Ensure SSL is installed
- Check cookies are set for correct domain
- Test login/logout functionality

### 5.10 Gotcha #10: File Upload Size Limits

**Symptom**: Uploads fail silently or with error

**Root Cause**: PHP upload limits too small

**Prevention**:
```ini
# In Hostinger PHP Configuration
upload_max_filesize = 20M
post_max_size = 20M
memory_limit = 256M
```

**Verification**:
```bash
php -i | grep upload
# Should show updated values
```

---

## 6. Pre-Deployment Validation

### 6.1 Local Validation Checklist

Before creating deployment package, verify:

#### Application Health
- [ ] Application runs locally without errors
- [ ] All routes work correctly
- [ ] Database migrations run successfully
- [ ] File uploads work locally
- [ ] No PHP errors/warnings in logs
- [ ] All tests pass (if applicable)

#### Configuration Check
- [ ] `.env.production` file created
- [ ] Production database credentials confirmed
- [ ] `APP_DEBUG=false` in production config
- [ ] `APP_ENV=production` set
- [ ] Session domain configured
- [ ] Mail settings configured (if needed)

#### Code Quality
- [ ] No hardcoded localhost URLs
- [ ] No hardcoded file paths
- [ ] Environment variables used for all config
- [ ] Debug code removed (dd(), dump(), var_dump())
- [ ] Comments/documentation updated
- [ ] Git commit created with deployment changes

#### Asset Preparation
- [ ] CSS files built/minified (if applicable)
- [ ] JavaScript files built/minified (if applicable)
- [ ] Images optimized
- [ ] Favicon prepared
- [ ] Robots.txt configured

### 6.2 Deployment Package Validation

After running deployment script, verify:

#### Package Structure
```bash
# Check deployment package exists
ls -la bowling-system-deploy-*.zip

# Extract and verify structure
unzip -l bowling-system-deploy.zip | head -20

# Verify essential files present
unzip -l bowling-system-deploy.zip | grep -E "index.php|.htaccess|artisan|composer.json|.env"
```

#### File Count Check
```bash
# Should have ~500-1000 files (excluding vendor)
unzip -l bowling-system-deploy.zip | wc -l
```

#### Size Check
```bash
# Should be < 5 MB (without vendor)
ls -lh bowling-system-deploy.zip
# Expected: ~400 KB - 2 MB
```

#### Path Verification
```bash
# Extract to temp directory
mkdir /tmp/deploy-test
unzip bowling-system-deploy.zip -d /tmp/deploy-test

# Verify index.php paths
grep -E "(require|require_once)" /tmp/deploy-test/index.php
# Should show: require __DIR__.'/vendor/autoload.php';
# NOT: require __DIR__.'/../vendor/autoload.php';

# Cleanup
rm -rf /tmp/deploy-test
```

### 6.3 Pre-Upload Checklist

- [ ] Deployment package created successfully
- [ ] Package size reasonable (< 5 MB)
- [ ] Index.php paths verified (no ../)
- [ ] .env file included with production values
- [ ] All required directories present
- [ ] No .git directory in package
- [ ] No node_modules in package
- [ ] No vendor directory in package (placeholder only)
- [ ] Deployment instructions included
- [ ] Backup of current production made (if updating)

---

## 7. Post-Deployment Verification

### 7.1 Immediate Verification (Right After Deployment)

#### File Upload Verification
```bash
# Via SSH/Terminal or File Manager
ls -la public_html/  # Should show flattened structure
ls -la public_html/index.php  # Should exist at root
ls -la public_html/vendor/autoload.php  # Should exist after composer install
ls -la public_html/.env  # Should exist
ls -la public_html/artisan  # Should exist
```

#### Permission Verification
```bash
# Check storage is writable
ls -ld public_html/storage  # Should show drwxrwxr-x or similar
touch public_html/storage/test.txt  # Should succeed
rm public_html/storage/test.txt  # Cleanup

# Check cache is writable
ls -ld public_html/bootstrap/cache  # Should show drwxrwxr-x or similar
```

#### Dependency Installation Verification
```bash
# Verify vendor installed
ls public_html/vendor/ | head -10  # Should list packages
cat public_html/vendor/composer/autoload_classmap.php  # Should exist
```

### 7.2 Application Verification

#### Basic Functionality
- [ ] Homepage loads without errors
- [ ] No 500 errors
- [ ] CSS loads correctly (check page source)
- [ ] JavaScript loads correctly
- [ ] Images load correctly
- [ ] No console errors in browser

#### Route Verification
```bash
# On server, check routes are registered
php artisan route:list | wc -l  # Should show similar count to local
```

Test these URLs in browser:
- [ ] Homepage: `https://yourdomain.com`
- [ ] About/Info pages
- [ ] Registration page: `https://yourdomain.com/daftar`
- [ ] Admin login: `https://yourdomain.com/admin/login`
- [ ] Leaderboard: `https://yourdomain.com/kedudukan`

#### Database Verification
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
# Should return PDO object without error

# Check migrations
php artisan migrate:status
# Should show all migrations as "Run"
```

#### Form Verification
- [ ] Registration form submits successfully
- [ ] Login works correctly
- [ ] File uploads work (receipt images)
- [ ] Form validation works
- [ ] Success/error messages display

#### Session Verification
- [ ] Can log in
- [ ] Stay logged in across pages
- [ ] Logout works
- [ ] Session persists correctly

### 7.3 Security Verification

#### Configuration Security
```bash
# Verify debug mode is off
grep APP_DEBUG .env  # Should show APP_DEBUG=false

# Verify environment
grep APP_ENV .env  # Should show APP_ENV=production

# Check .env not accessible via web
curl https://yourdomain.com/.env  # Should return 404 or 403
```

#### File Security
- [ ] `.env` file not accessible via browser
- [ ] `.git` directory not exposed
- [ ] Sensitive files not accessible
- [ ] Error messages don't expose paths
- [ ] No PHP errors visible to users

#### SSL/HTTPS Verification
- [ ] SSL certificate installed
- [ ] HTTPS works correctly
- [ ] No mixed content warnings
- [ ] Redirects HTTP to HTTPS

### 7.4 Performance Verification

#### Cache Status
```bash
# Check caches are enabled
php artisan config:cache  # Should complete without error
php artisan route:cache  # Should complete without error
php artisan view:cache  # Should complete without error
```

#### Load Time Check
- [ ] Homepage loads in < 3 seconds
- [ ] Other pages load reasonably
- [ ] No excessive memory usage
- [ ] Database queries optimized

### 7.5 Monitoring Setup

#### Logging Verification
```bash
# Check logs are writable
ls -la storage/logs/laravel.log  # Should exist and be writable

# Trigger a test error
php artisan tinker
>>> trigger_error("Test error", E_USER_WARNING);

# Check log
tail -20 storage/logs/laravel.log  # Should show error
```

#### Error Monitoring
- [ ] Error logging configured
- [ ] Log rotation set up (if needed)
- [ ] Monitoring alerts configured (if available)

### 7.6 Final Production Checklist

- [ ] All features tested and working
- [ ] Admin password changed from default
- [ ] Database backed up
- [ ] SSL certificate active
- [ ] Monitoring configured
- [ ] Documentation updated
- [ ] Team notified of deployment
- [ ] Rollback plan documented

---

## 8. Quick Reference Commands

### 8.1 Deployment Preparation (Local)

```bash
# 1. Create production environment file
cp .env.example .env.production
nano .env.production

# 2. Run deployment script
./deploy-to-hostinger.sh

# 3. Verify deployment package
unzip -l bowling-system-deploy-*.zip
```

### 8.2 Server Deployment (Hostinger)

```bash
# 1. Navigate to project directory
cd public_html

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Set permissions
chmod -R 775 storage bootstrap/cache
chmod +x artisan

# 4. Run migrations
php artisan migrate --force

# 5. Clear and rebuild caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Create storage link
php artisan storage:link

# 7. Verify installation
php artisan about
```

### 8.3 Troubleshooting Commands

```bash
# Check Laravel version
php artisan --version

# Check PHP version
php -v

# Check composer
composer --version

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check routes
php artisan route:list

# Check configuration
php artisan config:cache

# View logs
tail -f storage/logs/laravel.log

# Check permissions
ls -la storage/
ls -la bootstrap/cache/

# Fix permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .

# Clear everything
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate:rollback
```

### 8.4 Maintenance Commands

```bash
# Enable maintenance mode
php artisan down

# Disable maintenance mode
php artisan up

# Backup database
mysqldump -u username -p database_name > backup.sql

# Restore database
mysql -u username -p database_name < backup.sql

# Optimize database
php artisan tinker
>>> DB::statement('OPTIMIZE TABLE participants');
>>> DB::statement('OPTIMIZE TABLE scores');
```

### 8.5 Update Deployment

```bash
# 1. Backup current version
cp -r public_html public_html.backup.$(date +%Y%m%d)

# 2. Upload new deployment package

# 3. Extract and install
composer install --no-dev --optimize-autoloader

# 4. Run migrations
php artisan migrate --force

# 5. Clear and rebuild caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Test thoroughly
# 7. Remove backup if successful (after 24 hours)
rm -rf public_html.backup.YYYYMMDD
```

---

## Appendix A: Deployment Script Template

Save as `deploy-to-hostinger.sh`:

```bash
#!/bin/bash

set -e  # Exit on error

PROJECT_NAME="your-project"
PROJECT_DIR="/path/to/local/project"
DEPLOY_DIR="${PROJECT_DIR}/hostinger-deploy"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
ZIP_FILE="${PROJECT_DIR}/${PROJECT_NAME}-deploy-${TIMESTAMP}.zip"

echo "Creating deployment package..."

# Clean previous deployments
rm -rf "$DEPLOY_DIR"
mkdir -p "$DEPLOY_DIR"

# Copy core directories
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

# Create placeholder vendor directory
mkdir -p "$DEPLOY_DIR/vendor"

# Fix index.php paths
cat > "$DEPLOY_DIR/index.php" << 'EOF'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
EOF

# Create ZIP package
cd "$DEPLOY_DIR"
zip -r "$ZIP_FILE" . -q
cd "$PROJECT_DIR"

echo "Deployment package created: $ZIP_FILE"
```

Make it executable:
```bash
chmod +x deploy-to-hostinger.sh
```

---

## Appendix B: File Structure Comparison

### Local Structure (Laravel Standard)
```
project/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/                 ← Web root points here
│   ├── index.php
│   ├── .htaccess
│   ├── css/
│   ├── js/
│   └── storage → ../storage/app/public
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── artisan
├── composer.json
└── composer.lock
```

### Hostinger Structure (Flattened)
```
public_html/              ← Web root points here
├── app/
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
├── vendor/
├── css/                   ← Moved from public/
├── js/                    ← Moved from public/
├── index.php              ← Moved from public/, paths fixed
├── .htaccess              ← Moved from public/
├── .env
├── artisan
├── composer.json
└── composer.lock
```

---

## Appendix C: Troubleshooting Decision Tree

```
 encountering an issue?
 │
 ├─→ 500 Internal Server Error?
 │   ├─→ Check vendor directory exists
 │   │   └─→ If no: composer install --no-dev
 │   ├─→ Check .env file exists
 │   │   └─→ If no: cp .env.production .env
 │   ├─→ Check permissions
 │   │   └─→ Fix: chmod -R 775 storage bootstrap/cache
 │   └─→ Check index.php paths
 │       └─→ Fix: Remove ../ from paths
 │
 ├─→ Database connection error?
 │   ├─→ Check database exists in Hostinger
 │   ├─→ Check credentials in .env
 │   ├─→ Verify DB_HOST=localhost
 │   └─→ Test: php artisan tinker → DB::connection()->getPdo()
 │
 ├─→ 404 Not Found?
 │   ├─→ Check .htaccess exists
 │   ├─→ Clear route cache
 │   ├─→ Check document root configuration
 │   └─→ Verify mod_rewrite enabled
 │
 ├─→ Changes not reflecting?
 │   ├─→ Clear all caches
 │   ├─→ Rebuild caches
 │   ├─→ Clear browser cache
 │   └─→ Check opcache (if enabled)
 │
 ├─→ File uploads not working?
 │   ├─→ Check PHP upload limits
 │   ├─→ Create storage symlink
 │   ├─→ Check permissions
 │   └─→ Verify form enctype="multipart/form-data"
 │
 └─→ Session issues?
     ├─→ Check SESSION_DOMAIN in .env
     ├─→ Verify HTTPS enabled
     ├─→ Clear session cache
     └─→ Check session driver
```

---

## Document Information

**Version**: 1.0
**Last Updated**: 2026-03-10
**Based On**: Real deployment experience with Laravel 12.0 on Hostinger
**Status**: Production-Tested and Verified

**Related Documentation**:
- `COMPREHENSIVE_DEPLOYMENT_GUIDE.md` - Detailed deployment walkthrough
- `QUICK_TROUBLESHOOTING.md` - Quick problem resolution
- `DEPLOYMENT_SUMMARY.md` - Deployment experience summary

**Support Resources**:
- Laravel Documentation: https://laravel.com/docs/deployment
- Hostinger Tutorials: https://www.hostinger.com/tutorials
- Laravel Forums: https://laracasts.com

---

**Note**: This guide is based on actual deployment experience and has been tested in production. Always test in a staging environment before deploying to production. Keep backups of your working versions before making changes.
