# Hostinger Deployment Guide
## Bowling System - Ukhuwah Strike Challenge

### What You Have:
- Deployment ZIP: `bowling-system-deploy.zip` (418 KB)
- Production `.env` file configured with your database credentials
- All necessary Laravel files included

### What's NOT Included (Intentional):
- `vendor/` directory - We'll install via Composer on server
- `node_modules/` - Not needed for production
- Development files

---

## Step 1: Upload Files to Hostinger

### Option A: Using File Manager (Recommended for simplicity)
1. Log in to Hostinger
2. Go to: Hosting → File Manager
3. Navigate to: `public_html/`
4. Upload: `bowling-system-deploy.zip`
5. Right-click the ZIP → Extract
6. Move all files from `deployment-package/` to `public_html/`

### Option B: Using FTP (Faster for large files)
1. Get FTP credentials from Hostinger
2. Use FileZilla or similar FTP client
3. Upload the ZIP file
4. Extract on server

---

## Step 2: Install Composer Dependencies

Hostinger provides SSH access or terminal in hPanel. You need to install vendor dependencies:

### Using Hostinger Terminal:
1. In hPanel, look for "Terminal" or "SSH"
2. Navigate to your directory: `cd public_html`
3. Run: `composer install --no-dev --optimize-autoloader`

### Alternative: Hostinger Composer Setup
Some Hostinger plans have Composer pre-configured. Check if you can run:
- `composer install` from the file manager

---

## Step 3: Set File Permissions

Run these commands via SSH/Terminal:

```bash
# Make storage writable
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Make artisan executable
chmod +x artisan
```

---

## Step 4: Run Database Migrations

```bash
# Run migrations to create tables
php artisan migrate --force

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Step 5: Verify Deployment

1. Open browser: `https://ukhuwah-strike-challenge.site`
2. You should see your application home page
3. Test admin login: `https://ukhuwah-strike-challenge.site/admin/login`

---

## Troubleshooting

### 500 Internal Server Error:
- Check `.env` file exists and has correct database credentials
- Verify `storage/` and `bootstrap/cache/` are writable
- Check Laravel logs: `storage/logs/laravel.log`

### Database Connection Error:
- Verify database credentials in `.env`
- Ensure database exists in Hostinger MySQL Databases
- Check database user has proper permissions

### 404 Not Found:
- Verify `.htaccess` exists in `public/` folder
- Check Apache mod_rewrite is enabled

### Permission Issues:
```bash
# Fix all permissions
chmod -R 755 .
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R YOUR_USERNAME:YOUR_GROUP .
```

---

## Security Notes:

- `APP_DEBUG=false` - Error details are hidden from users
- `APP_KEY` - Your application encryption key is set
- Session domain is configured for your domain
- Admin password: `admin123` (change this in production!)

---

## Post-Deployment Checklist:

- [ ] Website loads at domain
- [ ] Admin login works
- [ ] Registration form works
- [ ] Database tables created correctly
- [ ] File uploads work (receipts)
- [ ] Change admin password
- [ ] Set up SSL certificate (HTTPS)

---

## Next Steps:

1. **Change Admin Password**: Update `ADMIN_PASSWORD` in `.env` or via database
2. **Set Up SSL**: Enable HTTPS in Hostinger (usually free)
3. **Backup**: Set up regular database backups
4. **Monitor**: Check logs regularly for issues

---

## Need Help?

- Laravel Docs: https://laravel.com/docs/deployment
- Hostinger Tutorials: https://support.hostinger.com
- Laravel on Shared Hosting: https://laravel.com/docs/deployment#configuration

