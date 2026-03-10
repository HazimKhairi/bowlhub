# Bowling System - Quick Troubleshooting Reference

## Common Issues & Quick Fixes

### 500 Internal Server Error

**Immediate Check:**
```bash
# Check Laravel logs
tail -50 storage/logs/laravel.log

# Verify vendor directory exists
ls -la vendor/

# Check .env file
cat .env
```

**Quick Fixes:**
```bash
# Install missing dependencies
composer install --no-dev --optimize-autoloader

# Fix permissions
chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

### Database Connection Issues

**Symptoms:**
- "SQLSTATE[HY000] [2002] Connection refused"
- "Access denied for user"

**Quick Fixes:**
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();

# Verify .env database settings
nano .env
# Ensure:
# DB_HOST=localhost
# DB_DATABASE=u806676157_bowling_system
# DB_USERNAME=u806676157_bowling_user
```

**If Database Not Created:**
1. Go to Hostinger hPanel → Databases → MySQL Databases
2. Create database with exact name from .env
3. Create user with all privileges
4. Update .env if credentials differ

---

### 404 Not Found

**Quick Fixes:**
```bash
# Clear route cache
php artisan route:clear
php artisan route:cache

# Verify .htaccess exists
ls -la public/.htaccess

# Check document root points to public/ directory
# In Hostinger: Domains → Manage → Document Root
# Should be: public_html/public
```

---

### File Upload Not Working

**Quick Fixes:**
```bash
# Create storage link
php artisan storage:link

# Check permissions
ls -la storage/app/public/
chmod -R 775 storage

# Verify PHP upload limits
php -i | grep upload
# Should be at least:
# upload_max_filesize = 20M
# post_max_size = 20M
```

---

### Admin Login Not Working

**Quick Fixes:**
```bash
# Verify .env settings
cat .env | grep ADMIN_PASSWORD

# Clear session cache
php artisan cache:clear

# Check database for admin user
php artisan tinker
>>> App\Models\User::where('email', 'admin@example.com')->first();
```

**Default Credentials:**
- Email: admin@example.com
- Password: admin123
- **CHANGE IMMEDIATELY AFTER FIRST LOGIN!**

---

### Changes Not Reflecting

**Quick Fixes:**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

### Storage/Permission Issues

**Symptoms:**
- Cannot write logs
- Cannot upload files
- Cache errors

**Quick Fixes:**
```bash
# Fix all permissions
chmod -R 755 .
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chmod +x artisan

# For some servers:
chown -R www-data:www-data .

# Verify storage link
ls -la public/storage
```

---

### Session/Cookie Issues

**Quick Fixes:**
```bash
# Clear session data
php artisan cache:clear

# Check .env session settings
SESSION_DOMAIN=.ukhuwah-strike-challenge.site
SESSION_DRIVER=database

# Ensure HTTPS enabled
# In Hostinger: SSL → Let's Encrypt → Install
```

---

## Emergency Procedures

### Site Completely Down

```bash
# 1. Check server status
# Hostinger hPanel → Dashboard → Server Status

# 2. Check error logs
tail -100 storage/logs/laravel.log
tail -100 /var/log/apache2/error.log

# 3. Verify .env exists and is valid
cat .env

# 4. Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# 5. Clear everything
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan migrate:status
```

### Rollback to Previous Version

```bash
# Backup current version first
cp -r public_html public_html.backup.$(date +%Y%m%d)

# Restore from backup
rm -rf public_html
mv public_html.backup.YYYYMMDD public_html

# Or via git
git revert HEAD
./deploy.sh
# Redeploy...
```

---

## Pre-Deployment Checklist

**Before Deploying:**
- [ ] Test all features locally
- [ ] Update .env.production with production values
- [ ] Run database migrations on test database
- [ ] Clear all caches
- [ ] Run `./deploy.sh` to create deployment package
- [ ] Test deployment package if possible

**After Deploying:**
- [ ] Install vendor dependencies: `composer install --no-dev`
- [ ] Set permissions: `chmod -R 775 storage bootstrap/cache`
- [ ] Configure document root to point to `public/`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Create storage link: `php artisan storage:link`
- [ ] Clear and rebuild caches
- [ ] Test all functionality
- [ ] Verify HTTPS is working
- [ ] Change admin password

---

## Essential Commands Reference

```bash
# Cache Management
php artisan cache:clear                    # Clear application cache
php artisan config:clear                   # Clear config cache
php artisan config:cache                   # Rebuild config cache
php artisan route:clear                    # Clear route cache
php artisan route:cache                    # Rebuild route cache
php artisan view:clear                     # Clear compiled views
php artisan view:cache                     # Rebuild view cache

# Database Operations
php artisan migrate --force                # Run migrations
php artisan migrate:rollback               # Rollback last migration
php artisan migrate:status                 # Show migration status
php artisan db:seed                        # Run database seeders

# Storage Operations
php artisan storage:link                   # Create storage symlink

# Application Info
php artisan about                          # Show application info
php artisan route:list                     # List all routes
php artisan tinker                         # Interactive REPL

# Maintenance Mode
php artisan down                           # Enable maintenance mode
php artisan up                             # Disable maintenance mode
```

---

## File Permissions Quick Reference

```bash
# Standard Laravel permissions
chmod -R 755 .                            # All files/directories
chmod -R 775 storage                      # Storage directory
chmod -R 775 bootstrap/cache              # Cache directory
chmod +x artisan                          # Make artisan executable

# For servers with specific ownership
chown -R www-data:www-data .              # Set correct owner

# Verify permissions
ls -la storage/                           # Check storage permissions
ls -la bootstrap/cache/                   # Check cache permissions
```

---

## Configuration Files Quick Reference

### .env (Essential Settings)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ukhuwah-strike-challenge.site

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=u806676157_bowling_system
DB_USERNAME=u806676157_bowling_user

SESSION_DOMAIN=.ukhuwah-strike-challenge.site
SESSION_DRIVER=database

CACHE_STORE=database
```

### Hostinger-Specific Paths

- Document Root: `public_html/public` (configure domain to point here)
- Storage: `public_html/storage`
- Logs: `public_html/storage/logs/laravel.log`
- Public Files: `public_html/public/storage` (symlink)

---

## Contact & Support

### First-Line Resources
1. This troubleshooting guide
2. Laravel logs: `storage/logs/laravel.log`
3. Hostinger error logs in hPanel
4. [Laravel Documentation](https://laravel.com/docs)

### When to Contact Hostinger Support
- Server configuration issues
- Database connection problems (if database exists)
- SSL certificate issues
- File permission issues that can't be resolved
- PHP version conflicts

### When to Check Laravel Resources
- Application errors in logs
- Route configuration issues
- View/template issues
- Database migration problems
- Laravel-specific functionality

---

## Quick Diagnostic Commands

```bash
# Full system check
php artisan about                          # Laravel version, environment, etc.

# Database check
php artisan tinker                         # Then: DB::connection()->getPdo();

# Route check
php artisan route:list                     # Show all registered routes

# Cache check
php artisan config:cache                   # Rebuild and check for errors

# Permission check
ls -la storage/                           # Check if writable
ls -la bootstrap/cache/                   # Check if writable
ls -la public/storage                     # Check if symlink exists

# Log check
tail -50 storage/logs/laravel.log        # Recent errors
```

---

**Remember**: Most issues can be resolved by:
1. Clearing caches
2. Fixing permissions
3. Verifying .env configuration
4. Checking logs for specific errors
5. Ensuring vendor dependencies are installed