# Bowling System - Complete Hostinger Deployment Documentation

## Project Overview

**Project Name**: Ukhuwah Strike Challenge Bowling System
**Framework**: Laravel 12.0
**PHP Version**: 8.2+
**Database**: MySQL
**Hosting**: Hostinger Shared Hosting
**Domain**: ukhuwah-strike-challenge.site

---

## Table of Contents

1. [Deployment Architecture](#deployment-architecture)
2. [Pre-Deployment Preparation](#pre-deployment-preparation)
3. [Deployment Process](#deployment-process)
4. [Issues Encountered and Solutions](#issues-encountered-and-solutions)
5. [Configuration Changes Required](#configuration-changes-required)
6. [Directory Structure Differences](#directory-structure-differences)
7. [Common Pitfalls to Avoid](#common-pitfalls-to-avoid)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Post-Deployment Checklist](#post-deployment-checklist)
10. [Maintenance and Updates](#maintenance-and-updates)

---

## Deployment Architecture

### Laravel Structure on Shared Hosting

Hostinger shared hosting requires a specific directory structure because the public directory must be the web root:

```
public_html/ (Hostinger web root)
├── app/                    (Application code)
├── bootstrap/              (Framework bootstrapping)
├── config/                 (Configuration files)
├── database/               (Migrations and seeders)
├── public/                 (Public web files - this becomes the document root)
│   ├── index.php          (Entry point)
│   ├── .htaccess          (Apache configuration)
│   ├── css/               (Stylesheets)
│   ├── js/                (JavaScript files)
│   └── storage/           (Public storage symlink)
├── resources/              (Views and raw assets)
├── routes/                 (Route definitions)
├── storage/                (Application storage)
├── vendor/                 (Composer dependencies - installed on server)
├── .env                    (Environment configuration)
├── artisan                 (CLI command tool)
├── composer.json           (PHP dependencies)
└── composer.lock           (Dependency lock file)
```

### Key Architecture Points

1. **Public Directory as Document Root**: On Hostinger, you must configure your domain to point to the `public/` directory, not the project root.

2. **Vendor Directory Exclusion**: The `vendor/` directory is intentionally excluded from the deployment package and must be installed via Composer on the server.

3. **Storage Permissions**: The `storage/` directory and `bootstrap/cache/` require special write permissions.

4. **Environment Configuration**: Production settings are maintained in `.env` file with `APP_ENV=production` and `APP_DEBUG=false`.

---

## Pre-Deployment Preparation

### 1. Local Environment Setup

Ensure your local environment matches production requirements:

```bash
# Check PHP version
php -v  # Should be 8.2 or higher

# Check Composer
composer --version  # Should be 2.x or higher

# Verify Laravel version
composer show laravel/framework  # Should be 12.x
```

### 2. Create Production Environment File

```bash
# Copy example environment file
cp .env.example .env.production

# Generate application key
php artisan key:generate

# Update production values
nano .env.production
```

### 3. Prepare Deployment Package

The deployment script (`deploy.sh`) creates a optimized package:

```bash
# Execute deployment script
./deploy.sh

# This creates:
# - deployment-package/ directory with all necessary files
# - bowling-system-deploy.zip archive
# - Excludes vendor/ and node_modules/
```

### 4. Database Preparation

Create your MySQL database on Hostinger:

1. Log in to Hostinger hPanel
2. Navigate to **Databases → MySQL Databases**
3. Create new database with these specifications:
   - Database Name: `u806676157_bowling_system`
   - Username: `u806676157_bowling_user`
   - Password: Use strong password (store securely)
   - Host: `localhost`

---

## Deployment Process

### Step 1: Upload Files to Hostinger

#### Option A: Using File Manager (Recommended)

1. **Log in to Hostinger hPanel**
2. **Navigate to Hosting → File Manager**
3. **Go to public_html directory**
4. **Upload the deployment ZIP**:
   - Click `Upload` button
   - Select `bowling-system-deploy.zip`
   - Wait for upload to complete

5. **Extract the ZIP file**:
   - Right-click on the ZIP file
   - Select `Extract`
   - Extract to current directory

6. **Move files to correct location**:
   - If extracted to `deployment-package/` subdirectory
   - Select all files inside `deployment-package/`
   - Move them up to `public_html/`

#### Option B: Using FTP/SFTP

1. **Get FTP credentials from Hostinger**:
   - Host: FTP hostname from hPanel
   - Username: FTP username
   - Password: FTP password
   - Port: 21 (FTP) or 22 (SFTP)

2. **Use FileZilla or similar client**:
   - Connect to server
   - Navigate to `public_html/`
   - Upload `bowling-system-deploy.zip`
   - Extract using File Manager or command line

### Step 2: Install Composer Dependencies

#### Using Hostinger Terminal (Preferred)

1. **Access Terminal**:
   - In hPanel, look for "Terminal" or "SSH Access"
   - Or use SSH client: `ssh username@hostname`

2. **Navigate to project directory**:
   ```bash
   cd public_html
   ```

3. **Verify Composer is available**:
   ```bash
   composer --version
   ```

4. **Install dependencies**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

   **Flags explained**:
   - `--no-dev`: Skip development dependencies
   - `--optimize-autoloader`: Optimize autoloader for production

#### Alternative: File Manager Composer Setup

Some Hostinger plans provide Composer in File Manager:

1. Navigate to `public_html/` in File Manager
2. Look for "Composer" icon or option
3. Run `composer install --no-dev --optimize-autoloader`

### Step 3: Configure Environment

1. **Verify .env file exists**:
   - Check that `.env.production` was copied to `.env`
   - If not, rename manually in File Manager

2. **Update database credentials** (if needed):
   ```bash
   # Edit .env file
   nano .env

   # Or use File Manager to edit
   ```

3. **Key .env settings for production**:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://ukhuwah-strike-challenge.site

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=u806676157_bowling_system
   DB_USERNAME=u806676157_bowling_user
   DB_PASSWORD=your_secure_password

   SESSION_DOMAIN=.ukhuwah-strike-challenge.site
   ```

### Step 4: Set File Permissions

Execute these commands via SSH/Terminal:

```bash
# Navigate to project root
cd public_html

# Set storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Make artisan executable
chmod +x artisan

# Set proper ownership (if needed)
chown -R YOUR_USERNAME:YOUR_GROUP .

# Alternative for some servers
chmod -R 755 .
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

**Permission Explanation**:
- `755`: Read/execute for owner, read/execute for group and others
- `775`: Write permissions for owner and group
- `storage/` and `bootstrap/cache/` need write permissions for Laravel to function

### Step 5: Initialize Application

```bash
# Run database migrations
php artisan migrate --force

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configurations for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link (for public file access)
php artisan storage:link

# Verify application status
php artisan about
```

### Step 6: Configure Domain Document Root (CRITICAL)

This is a **critical step** that many developers miss:

1. **In Hostinger hPanel**:
   - Go to **Hosting → Domains**
   - Find your domain: `ukhuwah-strike-challenge.site`
   - Click **Manage** or **Settings**

2. **Set document root**:
   - Change from `public_html` to `public_html/public`
   - **This is essential for Laravel security and functionality**

3. **Alternative approach** (if document root change is not possible):
   - Keep `public_html` as document root
   - Move contents of `public/` to `public_html/`
   - Update `index.php` paths to point to correct locations
   - **Not recommended** - security risk

---

## Issues Encountered and Solutions

### Issue 1: Directory Structure Mismatch

**Problem**: Laravel expects application code to be outside the web root, but Hostinger's default setup points to `public_html/`.

**Solution**: Configure domain document root to point to `public_html/public/` instead of `public_html/`.

**Steps**:
1. Access Hostinger hPanel
2. Navigate to Domains section
3. Edit domain settings
4. Change document root to include `/public` subdirectory

### Issue 2: Missing Vendor Directory

**Problem**: Application returns 500 error with message about missing autoload files.

**Root Cause**: Vendor directory is excluded from deployment package to keep size small.

**Solution**: Install vendor dependencies on server using Composer.

```bash
composer install --no-dev --optimize-autoloader
```

**Prevention**: Always include `composer.json` and `composer.lock` in deployment package.

### Issue 3: File Permission Issues

**Problem**: Application returns 500 error or cannot write logs/cache.

**Root Cause**: Storage directory lacks write permissions.

**Solution**:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .  # For some servers
```

**Prevention**: Set correct permissions immediately after deployment.

### Issue 4: Database Connection Errors

**Problem**: Application cannot connect to database.

**Common Causes**:
1. Incorrect database credentials in `.env`
2. Database not created on Hostinger
3. Database user lacks proper permissions
4. Wrong database host (should be `localhost`)

**Solutions**:
1. Verify credentials in `.env` file
2. Create database in Hostinger hPanel
3. Grant all privileges to database user
4. Ensure `DB_HOST=localhost`

### Issue 5: Incorrect Document Root

**Problem**: Application shows file listing or wrong page.

**Root Cause**: Domain pointing to wrong directory.

**Solution**: Configure domain to point to `public_html/public/` instead of `public_html/`.

### Issue 6: Missing Environment File

**Problem**: Application returns "No application encryption key" error.

**Root Cause**: `.env` file missing or not copied from `.env.production`.

**Solution**:
```bash
# Copy environment file
cp .env.production .env

# Or create from example
cp .env.example .env

# Generate encryption key
php artisan key:generate
```

### Issue 7: Cache and Config Issues

**Problem**: Changes not reflecting or configuration errors.

**Root Cause**: Old cached configurations.

**Solution**:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Issue 8: Storage Link Missing

**Problem**: Uploaded files not accessible via web.

**Root Cause**: Storage symlink not created.

**Solution**:
```bash
php artisan storage:link
```

---

## Configuration Changes Required

### 1. Production Environment Variables

Critical `.env` settings for production:

```env
# Application Environment
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ukhuwah-strike-challenge.site

# Security
APP_KEY=base64:YOUR_GENERATED_KEY

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u806676157_bowling_system
DB_USERNAME=u806676157_bowling_user
DB_PASSWORD=your_secure_password

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.ukhuwah-strike-challenge.site

# Cache Configuration
CACHE_STORE=database

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=warning
```

### 2. Config File Adjustments

#### `config/app.php`

```php
'env' => env('APP_ENV', 'production'),
'debug' => (bool) env('APP_DEBUG', false),
'url' => env('APP_URL', 'https://ukhuwah-strike-challenge.site'),
```

#### `config/session.php`

```php
'domain' => env('SESSION_DOMAIN', '.ukhuwah-strike-challenge.site'),
```

#### `config/database.php`

Ensure MySQL configuration matches Hostinger's setup:

```php
'mysql' => [
    'driver' => 'mysql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    // ... other settings
],
```

### 3. Web Server Configuration

#### Apache `.htaccess` Settings

The `.htaccess` file in `public/` directory should contain:

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

### 4. PHP Configuration

Ensure these PHP settings are configured in Hostinger:

```ini
memory_limit = 256M
post_max_size = 20M
upload_max_filesize = 20M
max_execution_time = 300
```

**How to change in Hostinger**:
1. Navigate to Hosting → Advanced → PHP Configuration
2. Update the values
3. Save changes

---

## Directory Structure Differences

### Local Development Structure

```
bowling-system-backend/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
│   ├── index.php
│   └── .htaccess
├── resources/
├── routes/
├── storage/
├── tests/
├── vendor/           # Installed locally
├── node_modules/     # Installed locally (excluded from git)
├── .env              # Local environment
├── .env.example
├── .env.production   # Production environment template
├── artisan
├── composer.json
└── composer.lock
```

### Production (Hostinger) Structure

```
public_html/          # Hostinger's default document root
├── public/           # ACTUAL document root (configure domain to point here)
│   ├── index.php
│   ├── .htaccess
│   ├── css/
│   ├── js/
│   └── storage → ../storage/app/public (symlink)
├── app/
├── bootstrap/
├── config/
├── database/
├── resources/
├── routes/
├── storage/
├── tests/
├── vendor/           # Installed on server
├── .env              # Production environment
├── .env.production   # Backup copy
├── artisan
├── composer.json
└── composer.lock
```

### Key Differences

1. **Document Root**: Production uses `public_html/public/` vs local can use any directory
2. **Vendor Location**: Vendor must be installed on server, not uploaded
3. **Environment Files**: Production uses `.env` (copied from `.env.production`)
4. **Storage Link**: Production needs `storage:link` to be run
5. **Permissions**: Production needs specific permission settings

### Deployment Package Structure

The deployment script creates an optimized package:

```
deployment-package/
├── app/              # Application code
├── bootstrap/        # Framework bootstrap files
├── config/           # Configuration files
├── database/         # Migrations and seeders
├── public/           # Public files
├── resources/        # Views and assets
├── routes/           # Route definitions
├── storage/          # Storage structure (empty directories)
├── tests/            # Test files
├── vendor/           # Empty directory (placeholder)
├── .env              # Copied from .env.production
├── artisan           # CLI tool
├── composer.json     # Dependency definitions
└── composer.lock     # Dependency versions
```

**Intentionally Excluded**:
- `node_modules/` - Not needed for production
- `vendor/` - Installed on server via Composer
- Development files and configurations

---

## Common Pitfalls to Avoid

### 1. Forgetting to Install Vendor Dependencies

**Mistake**: Uploading deployment package without running `composer install`.

**Consequence**: Application crashes with "Class not found" errors.

**Prevention**: Always run `composer install --no-dev --optimize-autoloader` after deployment.

### 2. Incorrect Document Root Configuration

**Mistake**: Leaving domain pointing to `public_html/` instead of `public_html/public/`.

**Consequence**: Security vulnerability, application routes not working, file listing exposed.

**Prevention**: Always configure domain to point to `public/` subdirectory.

### 3. Debug Mode Enabled in Production

**Mistake**: Leaving `APP_DEBUG=true` in production `.env`.

**Consequence**: Sensitive information exposed to users when errors occur.

**Prevention**: Always set `APP_DEBUG=false` in production.

### 4. Weak Database Credentials

**Mistake**: Using simple passwords or default credentials.

**Consequence**: Security vulnerability, potential data breach.

**Prevention**: Use strong, unique passwords for database and admin accounts.

### 5. Insufficient File Permissions

**Mistake**: Not setting proper permissions on storage directories.

**Consequence**: Application cannot write logs, cache, or uploaded files.

**Prevention**: Always set `775` permissions on `storage/` and `bootstrap/cache/`.

### 6. Missing Environment File

**Mistake**: Not copying `.env.production` to `.env` on server.

**Consequence**: Application fails to start with encryption key errors.

**Prevention**: Include `.env` file in deployment package or create immediately after deployment.

### 7. Forgetting to Run Migrations

**Mistake**: Not running database migrations after deployment.

**Consequence**: Database tables missing, application functionality broken.

**Prevention**: Always run `php artisan migrate --force` after deployment.

### 8. Not Clearing Caches

**Mistake**: Leaving old cached configurations from development.

**Consequence**: Application using wrong configuration, routes not working.

**Prevention**: Always clear and rebuild caches after deployment.

### 9. Ignoring SSL/HTTPS

**Mistake**: Not setting up SSL certificate or forcing HTTPS.

**Consequence**: Security vulnerability, mixed content warnings.

**Prevention**: Always enable SSL on Hostinger (usually free) and force HTTPS.

### 10. Not Testing After Deployment

**Mistake**: Assuming deployment worked without testing.

**Consequence**: Issues discovered by users instead of being caught early.

**Prevention**: Always test all functionality after deployment.

---

## Troubleshooting Guide

### 500 Internal Server Error

**Possible Causes**:
1. Missing vendor directory
2. Incorrect file permissions
3. Missing `.env` file
4. Wrong document root
5. PHP version incompatibility

**Diagnostic Steps**:
```bash
# Check Laravel logs
tail -n 50 storage/logs/laravel.log

# Check PHP error logs
tail -n 50 /var/log/apache2/error.log

# Verify vendor exists
ls -la vendor/

# Check permissions
ls -la storage/
ls -la bootstrap/cache/
```

**Solutions**:
```bash
# Install vendor if missing
composer install --no-dev --optimize-autoloader

# Fix permissions
chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Database Connection Error

**Possible Causes**:
1. Wrong database credentials
2. Database not created
3. Database user lacks permissions
4. Wrong database host

**Diagnostic Steps**:
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check .env values
cat .env | grep DB_
```

**Solutions**:
1. Verify credentials in Hostinger hPanel
2. Create database if missing
3. Grant all privileges to user
4. Ensure `DB_HOST=localhost`

### 404 Not Found Errors

**Possible Causes**:
1. Incorrect `.htaccess` configuration
2. Mod_rewrite not enabled
3. Wrong document root
4. Cache issues

**Solutions**:
```bash
# Clear route cache
php artisan route:clear
php artisan route:cache

# Verify .htaccess exists
ls -la public/.htaccess

# Restart Apache if needed
sudo service apache2 restart
```

### File Upload Issues

**Possible Causes**:
1. Insufficient PHP upload limits
2. Missing storage link
3. Incorrect permissions

**Solutions**:
```bash
# Create storage link
php artisan storage:link

# Check upload limits in PHP config
php -i | grep upload

# Verify permissions
ls -la storage/app/public/
```

### Session/Cookie Issues

**Possible Causes**:
1. Wrong session domain
2. Incorrect session driver
3. HTTPS/HTTP mismatch

**Solutions**:
```bash
# Clear session cache
php artisan cache:clear

# Verify .env session settings
SESSION_DOMAIN=.ukhuwah-strike-challenge.site
SESSION_DRIVER=database

# Ensure HTTPS is forced
```

### Performance Issues

**Possible Causes**:
1. Not using optimized caching
2. Missing opcache
3. Large file sizes
4. Unoptimized queries

**Solutions**:
```bash
# Enable caching
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize composer
composer install --optimize-autoloader --no-dev

# Check query performance
php artisan tinker
>>> DB::enableQueryLog();
```

---

## Post-Deployment Checklist

### Immediate Checks (After Deployment)

- [ ] Website loads without errors
- [ ] All routes are accessible
- [ ] Database connection working
- [ ] File uploads functional
- [ ] Session management working
- [ ] SSL certificate active
- [ ] No PHP errors in logs
- [ ] All caches cleared and rebuilt

### Functional Testing

- [ ] Registration form works
- [ ] Admin login functional
- [ ] Score submission works
- [ ] Leaderboard displays correctly
- [ ] File uploads (receipts) work
- [ ] Admin panel accessible
- [ ] All links working
- [ ] Forms submit correctly

### Security Verification

- [ ] `APP_DEBUG=false` in production
- [ ] Strong admin password
- [ ] Database credentials secure
- [ ] HTTPS enforced
- [ ] Sensitive files not accessible
- [ ] Document root points to `public/`
- [ ] File permissions correct
- [ ] `.env` file not accessible via web

### Performance Optimization

- [ ] Configuration cached
- [ ] Routes cached
- [ ] Views cached
- [ ] Autoloader optimized
- [ ] CDN enabled (if applicable)
- [ ] Image optimization
- [ ] Database queries optimized

### Monitoring Setup

- [ ] Error logging configured
- [ ] Log rotation set up
- [ ] Database backups configured
- [ ] Uptime monitoring set up
- [ ] Email notifications configured

---

## Maintenance and Updates

### Regular Maintenance Tasks

#### Weekly

- Check application logs for errors
- Verify database backups are running
- Monitor disk space usage
- Check for security updates

#### Monthly

- Update Laravel dependencies
- Review and optimize database
- Test disaster recovery procedures
- Review access logs

#### Quarterly

- Security audit
- Performance review
- Dependency updates
- Backup verification

### Updating the Application

#### Step 1: Prepare Local Updates

```bash
# Pull latest changes
git pull origin main

# Install/update dependencies
composer update

# Run tests
php artisan test

# Clear caches
php artisan cache:clear
```

#### Step 2: Create New Deployment Package

```bash
# Run deployment script
./deploy.sh

# Test deployment package locally if possible
```

#### Step 3: Deploy to Production

```bash
# Backup current version
cp -r public_html public_html.backup

# Upload new deployment package
# Extract and replace files

# Install new dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Test thoroughly
```

#### Step 4: Rollback Plan (If Issues Occur)

```bash
# Restore from backup
rm -rf public_html
mv public_html.backup public_html

# Or revert to previous commit
git revert HEAD
```

### Database Maintenance

#### Regular Backups

```bash
# Via Hostinger hPanel
# Navigate to Databases → MySQL Databases
# Select database → Backup

# Or via command line
mysqldump -u username -p database_name > backup.sql
```

#### Database Optimization

```bash
# Access MySQL
mysql -u username -p database_name

# Run optimization
OPTIMIZE TABLE participants;
OPTIMIZE TABLE scores;
OPTIMIZE TABLE team_members;
```

### Log Management

#### Rotate Logs

```bash
# Archive old logs
mv storage/logs/laravel.log storage/logs/laravel-$(date +%Y%m%d).log

# Create new log file
touch storage/logs/laravel.log

# Set proper permissions
chmod 664 storage/logs/laravel.log
```

#### Monitor Logs

```bash
# View recent errors
tail -f storage/logs/laravel.log

# Search for specific errors
grep "ERROR" storage/logs/laravel.log

# Count error types
grep -o "ERROR.*" storage/logs/laravel.log | sort | uniq -c
```

---

## Quick Reference Commands

### Essential Artisan Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Database operations
php artisan migrate --force
php artisan migrate:rollback
php artisan db:seed

# Storage operations
php artisan storage:link

# Application information
php artisan about
php artisan route:list
php artisan tinker
```

### File Permission Commands

```bash
# Standard permissions
chmod -R 755 .
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod +x artisan

# Fix ownership
chown -R www-data:www-data .

# Alternative for some servers
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

### Composer Commands

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Update dependencies
composer update

# Show installed packages
composer show

# Check for security issues
composer audit
```

---

## Contact and Support

### Hostinger Resources

- Documentation: https://support.hostinger.com
- Tutorials: https://www.hostinger.com/tutorials
- Live Chat: Available in hPanel

### Laravel Resources

- Documentation: https://laravel.com/docs
- Deployment Guide: https://laravel.com/docs/deployment
- Forums: https://laracasts.com

### Project-Specific Support

For issues specific to this Bowling System deployment:

1. Check this documentation first
2. Review Laravel logs: `storage/logs/laravel.log`
3. Review Hostinger error logs
4. Search Laravel forums for similar issues
5. Contact Hostinger support for server-specific issues

---

## Appendix

### Useful Hostinger Paths

- Document Root: `/home/u806676157/domains/ukhuwah-strike-challenge.site/public_html/public`
- PHP Error Log: `/home/u806676157/domains/ukhuwah-strike-challenge.site/logs`
- Access Log: `/home/u806676157/domains/ukhuwah-strike-challenge.site/logs`

### Database Credentials Reference

- Database Name: `u806676157_bowling_system`
- Username: `u806676157_bowling_user`
- Host: `localhost`
- Port: `3306`

### Default Admin Credentials

**IMPORTANT**: Change these immediately after first login!

- Admin URL: `/admin/login`
- Default Password: `admin123`

### SSH Connection Example

```bash
# Connect via SSH
ssh u806676157@ssh.hostinger.com

# Navigate to project
cd domains/ukhuwah-strike-challenge.site/public_html

# Run commands
php artisan migrate --force
```

---

**Document Version**: 1.0
**Last Updated**: March 9, 2026
**Maintained By**: Development Team
**Status**: Production Ready