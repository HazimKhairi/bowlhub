# Laravel to Hostinger: Deployment Checklist

## Complete Step-by-Step Deployment Checklist

Use this checklist during deployment to ensure nothing is missed. Print or keep this open during the deployment process.

---

## Phase 1: Pre-Deployment Preparation

### 1.1 Local Environment Verification

**Application Status**
- [ ] Application runs without errors locally
- [ ] All features tested and working
- [ ] No PHP errors/warnings in logs
- [ ] Database migrations run successfully
- [ ] File uploads work correctly
- [ ] All routes accessible

**Code Review**
- [ ] No debug code left (dd(), dump(), var_dump())
- [ ] No hardcoded localhost URLs
- [ ] Environment variables used for configuration
- [ ] Comments/documentation updated
- [ ] Git commit created with changes

**Configuration Files**
- [ ] `.env.production` file created
- [ ] Production database credentials confirmed
- [ ] `APP_DEBUG=false` in production config
- [ ] `APP_ENV=production` set
- [ ] `APP_URL` set to production domain
- [ ] Session domain configured
- [ ] Mail settings configured (if needed)

### 1.2 Hostinger Server Preparation

**Database Setup**
- [ ] Log in to Hostinger hPanel
- [ ] Navigate to Databases → MySQL Databases
- [ ] Create new database
  - [ ] Note database name (format: uXXXXXXXX_dbname)
  - [ ] Note database username (format: uXXXXXXXX_user)
  - [ ] Set strong password
  - [ ] Save credentials securely
- [ ] Create database user (if not auto-created)
- [ ] Grant all privileges to user
- [ ] Verify database connection from Hostinger

**Domain Configuration**
- [ ] Navigate to Domains section
- [ ] Find your domain
- [ ] Click Manage/Settings
- [ ] Set document root to `public_html` (for flattened structure)
- [ ] Save changes

**PHP Configuration**
- [ ] Navigate to Hosting → Advanced → PHP Configuration
- [ ] Set PHP version to 8.2 or higher
- [ ] Configure PHP settings:
  - [ ] `memory_limit = 256M`
  - [ ] `post_max_size = 20M`
  - [ ] `upload_max_filesize = 20M`
  - [ ] `max_execution_time = 300`
- [ ] Save changes

**SSL Certificate**
- [ ] Navigate to SSL section
- [ ] Install Let's Encrypt SSL (free)
- [ ] Verify SSL is active
- [ ] Test HTTPS access

### 1.3 Deployment Package Creation

**Run Deployment Script**
- [ ] Open terminal in project directory
- [ ] Run: `./deploy-to-hostinger.sh`
- [ ] Verify script completes without errors
- [ ] Note the location of created ZIP file

**Verify Deployment Package**
- [ ] ZIP file created successfully
- [ ] File size reasonable (< 5 MB)
- [ ] Extract to temp directory to verify
- [ ] Check index.php has correct paths (no ../)
- [ ] Verify .env file included
- [ ] Verify all required directories present
- [ ] Verify no .git directory included
- [ ] Verify no node_modules included
- [ ] Verify vendor is placeholder only

**Package Structure Verification**
```bash
# Run these commands to verify package
unzip -l bowling-system-deploy.zip | grep index.php
unzip -l bowling-system-deploy.zip | grep .htaccess
unzip -l bowling-system-deploy.zip | grep composer.json
unzip -l bowling-system-deploy.zip | grep ".env"
```

---

## Phase 2: File Upload & Extraction

### 2.1 Upload to Hostinger

**Choose Upload Method**
- [ ] File Manager (recommended for small files)
- [ ] FTP/SFTP (faster for large files)

**File Manager Method**
- [ ] Log in to Hostinger hPanel
- [ ] Navigate to File Manager
- [ ] Go to `public_html/` directory
- [ ] Click Upload button
- [ ] Select deployment ZIP file
- [ ] Wait for upload to complete
- [ ] Verify file uploaded

**FTP Method (if preferred)**
- [ ] Get FTP credentials from Hostinger
- [ ] Connect via FTP client (FileZilla, etc.)
- [ ] Navigate to `public_html/`
- [ ] Upload ZIP file
- [ ] Verify upload complete

### 2.2 Extract Deployment Package

**Extraction Steps**
- [ ] In File Manager, locate uploaded ZIP
- [ ] Right-click ZIP file
- [ ] Select "Extract"
- [ ] Extract to current directory
- [ ] Wait for extraction to complete
- [ ] Delete ZIP file (optional, to save space)

**Verify Extraction**
```bash
# Via SSH/Terminal, check structure
cd public_html
ls -la

# Should show:
# - app/
# - bootstrap/
# - config/
# - database/
# - resources/
# - routes/
# - storage/
# - css/
# - js/
# - index.php
# - .htaccess
# - artisan
# - composer.json
# - composer.lock
# - .env
```

---

## Phase 3: Server-Side Setup

### 3.1 Install Dependencies

**Access Terminal/SSH**
- [ ] Log in to Hostinger hPanel
- [ ] Navigate to Terminal or use SSH
- [ ] Navigate to project: `cd public_html`

**Install Composer Dependencies**
```bash
# Check if composer is available
composer --version

# Install production dependencies
composer install --no-dev --optimize-autoloader

# Verify installation
ls -la vendor/autoload.php
```

**Verification Steps**
- [ ] Composer command executes without errors
- [ ] vendor directory created
- [ ] vendor/autoload.php exists
- [ ] No composer errors/warnings

### 3.2 Set File Permissions

**Execute Permission Commands**
```bash
# Navigate to project root
cd public_html

# Set storage permissions
chmod -R 775 storage

# Set cache permissions
chmod -R 775 bootstrap/cache

# Make artisan executable
chmod +x artisan

# Verify permissions
ls -la storage/
ls -la bootstrap/cache/
```

**Verification Steps**
- [ ] Permission commands execute without errors
- [ ] storage/ shows drwxrwxr-x permissions
- [ ] bootstrap/cache/ shows drwxrwxr-x permissions
- [ ] artisan shows -rwxrwxr-x permissions

**Test Write Permissions**
```bash
# Test storage is writable
touch storage/test.txt
# If this succeeds, remove test file
rm storage/test.txt
```

### 3.3 Run Database Migrations

**Execute Migrations**
```bash
# Run migrations
php artisan migrate --force

# Check migration status
php artisan migrate:status
```

**Verification Steps**
- [ ] Migrations execute without errors
- [ ] All migrations show as "Run"
- [ ] Database tables created
- [ ] No SQL errors

**Manual Verification** (optional)
- [ ] Log in to Hostinger phpMyAdmin
- [ ] Verify tables created
- [ ] Check table structure matches expectations

### 3.4 Create Storage Link

**Create Symlink**
```bash
# Standard Laravel method
php artisan storage:link

# Manual method (if above doesn't work)
cd storage
ln -s app/public/receipts receipts
cd ..
```

**Verification Steps**
- [ ] Symlink created without errors
- [ ] `ls -la storage/receipts` shows symlink
- [ ] Symlink points to correct location

---

## Phase 4: Cache Management

### 4.1 Clear All Caches

**Execute Clear Commands**
```bash
# Clear application cache
php artisan cache:clear

# Clear configuration cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear
```

**Verification Steps**
- [ ] All cache clear commands execute
- [ ] No cache errors
- [ ] Config cleared successfully
- [ ] Routes cleared successfully

### 4.2 Rebuild Production Caches

**Execute Cache Commands**
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache
```

**Verification Steps**
- [ ] Config cached successfully
- [ ] Routes cached successfully
- [ ] Views cached successfully
- [ ] No cache errors

---

## Phase 5: Post-Deployment Verification

### 5.1 Basic Functionality Tests

**Browser Tests**
- [ ] Open homepage: `https://yourdomain.com`
  - [ ] Page loads without errors
  - [ ] No 500 errors
  - [ ] CSS loads correctly
  - [ ] JavaScript loads correctly
  - [ ] Images display correctly
- [ ] Test about/info pages
- [ ] Test registration page: `https://yourdomain.com/daftar`
- [ ] Test admin login: `https://yourdomain.com/admin/login`
- [ ] Test leaderboard: `https://yourdomain.com/kedudukan`

**Console Verification**
- [ ] Open browser developer console (F12)
- [ ] Check Console tab for JavaScript errors
- [ ] Check Network tab for failed requests
- [ ] Verify all assets load (200 status codes)

### 5.2 Form Functionality Tests

**Registration Form**
- [ ] Navigate to registration page
- [ ] Fill out form with test data
- [ ] Submit form
- [ ] Verify submission successful
- [ ] Check database for new record
- [ ] Verify file upload works (if applicable)

**Login Form**
- [ ] Navigate to login page
- [ ] Enter credentials
- [ ] Submit form
- [ ] Verify login successful
- [ ] Verify session persists across pages
- [ ] Test logout functionality

**Admin Panel**
- [ ] Access admin login
- [ ] Login with admin credentials
- [ ] Verify admin panel accessible
- [ ] Test admin functionality
- [ ] Change admin password (important!)

**Score Submission**
- [ ] Navigate to score submission page
- [ ] Fill out form
- [ ] Submit score
- [ ] Verify submission successful
- [ ] Check leaderboard updates

### 5.3 Database Verification

**Connection Test**
```bash
# Via SSH/Terminal
php artisan tinker
>>> DB::connection()->getPdo();
# Should return PDO object without error
>>> exit
```

**Data Verification**
- [ ] Registration data saved correctly
- [ ] Login data works
- [ ] Score submissions saved
- [ ] Leaderboard displays correctly
- [ ] Admin functions work

**Migration Status**
```bash
php artisan migrate:status
# All migrations should show "Run"
```

### 5.4 Security Verification

**Configuration Security**
```bash
# Verify production settings
grep APP_DEBUG .env
# Should return: APP_DEBUG=false

grep APP_ENV .env
# Should return: APP_ENV=production
```

**File Security**
- [ ] Test `.env` not accessible: `https://yourdomain.com/.env`
  - Should return 404 or 403
- [ ] Test no sensitive files exposed
- [ ] Verify error messages don't show paths
- [ ] Check no PHP errors visible to users

**SSL/HTTPS**
- [ ] Access site via HTTPS
- [ ] Verify SSL certificate active
- [ ] Check for no mixed content warnings
- [ ] Verify HTTP redirects to HTTPS

### 5.5 Performance Verification

**Load Time**
- [ ] Homepage loads in < 3 seconds
- [ ] Other pages load reasonably
- [ ] No excessive memory usage
- [ ] Database queries fast enough

**Cache Status**
```bash
# Verify caches enabled
php artisan config:cache
php artisan route:cache
php artisan view:cache
# Should complete without re-caching
```

### 5.6 Logging Verification

**Log Files**
```bash
# Check logs exist and are writable
ls -la storage/logs/laravel.log

# View recent log entries
tail -20 storage/logs/laravel.log

# Trigger test error (optional)
php artisan tinker
>>> trigger_error("Test error", E_USER_WARNING);
>>> exit

# Check error logged
tail -5 storage/logs/laravel.log
```

---

## Phase 6: Final Production Setup

### 6.1 Security Finalization

**Password Changes**
- [ ] Change admin password from default
- [ ] Update database passwords (if needed)
- [ ] Update .env file with new passwords
- [ ] Clear config cache after password changes

**Default Credentials Check**
- [ ] Remove default test accounts
- [ ] Create proper admin accounts
- [ ] Set appropriate permissions
- [ ] Document admin credentials securely

### 6.2 Monitoring Setup

**Error Monitoring**
- [ ] Set up error logging
- [ ] Configure log rotation (if needed)
- [ ] Set up uptime monitoring (if available)
- [ ] Configure email alerts (if available)

**Backup Setup**
- [ ] Set up database backups
- [ ] Configure backup schedule
- [ ] Test backup restoration
- [ ] Document backup procedures

### 6.3 Documentation

**Update Documentation**
- [ ] Update deployment documentation
- [ ] Document any custom configurations
- [ ] Create troubleshooting guide
- [ ] Document rollback procedures

**Team Communication**
- [ ] Notify team of deployment
- [ ] Share deployment details
- [ ] Provide access credentials
- [ ] Document any known issues

---

## Phase 7: Rollback Preparation

### 7.1 Create Backup

**Before Major Changes**
```bash
# Backup current version
cp -r public_html public_html.backup.$(date +%Y%m%d)

# Backup database
mysqldump -u username -p database_name > backup.sql
```

### 7.2 Document Rollback Procedure

**Rollback Steps**
- [ ] Document current deployment version
- [ ] Save rollback commands
- [ ] Test rollback procedure (if possible)
- [ ] Document restore points

**Rollback Commands**
```bash
# Restore files
rm -rf public_html
mv public_html.backup.YYYYMMDD public_html

# Restore database
mysql -u username -p database_name < backup.sql

# Reinstall dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Quick Reference: Common Commands

### Deployment Commands
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Set permissions
chmod -R 775 storage bootstrap/cache
chmod +x artisan

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Verification Commands
```bash
# Check Laravel version
php artisan --version

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check routes
php artisan route:list

# View logs
tail -f storage/logs/laravel.log

# Check application status
php artisan about
```

### Troubleshooting Commands
```bash
# Fix permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .

# Clear everything
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate:rollback

# Re-optimize
composer dump-autoload
php artisan optimize
```

---

## Emergency Quick Fixes

### 500 Internal Server Error
```bash
# Check vendor installed
ls -la vendor/autoload.php
# If missing: composer install --no-dev

# Check .env exists
ls -la .env
# If missing: cp .env.production .env

# Check permissions
ls -la storage/
# Fix: chmod -R 775 storage bootstrap/cache

# Check logs
tail -50 storage/logs/laravel.log
```

### Database Connection Error
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();

# Verify .env settings
cat .env | grep DB_

# Check database exists in Hostinger
# Navigate to Databases → MySQL Databases
```

### Changes Not Reflecting
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear browser cache
# Chrome: Cmd+Shift+R (Mac) / Ctrl+Shift+R (Windows)
```

### File Upload Not Working
```bash
# Create storage link
php artisan storage:link

# Check permissions
ls -la storage/app/public/
chmod -R 775 storage

# Check PHP limits
php -i | grep upload
```

---

## Deployment Success Criteria

### Minimum Requirements for Successful Deployment

**Functionality**
- [ ] Homepage loads without errors
- [ ] All routes accessible
- [ ] Forms submit successfully
- [ ] Database operations work
- [ ] File uploads functional

**Security**
- [ ] APP_DEBUG=false
- [ ] SSL certificate active
- [ ] .env not accessible
- [ ] Admin password changed
- [ ] No sensitive files exposed

**Performance**
- [ ] Pages load in reasonable time
- [ ] No excessive errors in logs
- [ ] Caches enabled
- [ ] Database queries optimized

**Monitoring**
- [ ] Error logging configured
- [ ] Backups set up
- [ ] Monitoring configured
- [ ] Documentation complete

---

## Post-Deployment Monitoring

### First 24 Hours
- [ ] Check error logs every few hours
- [ ] Monitor site performance
- [ ] Test all user flows
- [ ] Check database performance
- [ ] Monitor user feedback

### First Week
- [ ] Daily log checks
- [ ] Performance monitoring
- [ ] User feedback collection
- [ ] Security monitoring
- [ ] Backup verification

### Ongoing
- [ ] Weekly log reviews
- [ ] Monthly security audits
- [ ] Quarterly dependency updates
- [ ] Regular backup testing
- [ ] Performance optimization

---

## Document Information

**Version**: 1.0
**Last Updated**: 2026-03-10
**Purpose**: Step-by-step deployment checklist
**Usage**: Print or keep open during deployment

**Related Documents**:
- `LARAVEL_TO_HOSTINGER_DEPLOYMENT_PATTERNS.md` - Detailed patterns and transformations
- `DEPLOYMENT_TRANSFORMATIONS.md` - Visual transformation guide
- `COMPREHENSIVE_DEPLOYMENT_GUIDE.md` - Complete deployment guide
- `QUICK_TROUBLESHOOTING.md` - Quick problem resolution

**Support**:
- Laravel Docs: https://laravel.com/docs/deployment
- Hostinger Support: https://support.hostinger.com
- Project Documentation: See related docs above

---

**Remember**: Check off each item as you complete it. This ensures nothing is missed during deployment. If you encounter issues, refer to the troubleshooting section or related documentation.
