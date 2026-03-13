# Laravel to Hostinger: Visual Transformation Guide

## Visual Transformation Overview

This guide provides visual representations of the exact transformations needed when deploying Laravel to Hostinger shared hosting.

---

## 1. Directory Structure Transformation

### Before: Local Development (Standard Laravel)

```
📂 bowling-system-backend/                    ← Project root
├── 📂 app/                                   ← Application code
├── 📂 bootstrap/                             ← Framework bootstrap
├── 📂 config/                                ← Configuration files
├── 📂 database/                              ← Migrations & seeders
├── 📂 node_modules/                          ❌ NOT deployed
├── 📂 public/                               ⭐ WEB ROOT (local)
│   ├── 📄 index.php                        ⭐ Entry point
│   ├── 📄 .htaccess                        ⭐ Apache config
│   ├── 📂 css/                             ← Stylesheets
│   ├── 📂 js/                              ← JavaScript
│   ├── 📄 favicon.ico
│   ├── 📄 robots.txt
│   └── 📂 storage → 📂 ../storage/app/public  🔗 Symlink
├── 📂 resources/                             ← Views & assets
├── 📂 routes/                                ← Route definitions
├── 📂 storage/                               ← App storage
│   ├── 📂 app/
│   │   └── 📂 public/
│   │       └── 📂 receipts/
│   ├── 📂 framework/
│   │   ├── 📂 cache/
│   │   ├── 📂 sessions/
│   │   └── 📂 views/
│   └── 📂 logs/
├── 📂 tests/                                 ← Test files
├── 📂 vendor/                               ❌ NOT deployed (installed on server)
├── 📄 .env                                   ❌ NOT deployed (use .env.production)
├── 📄 .env.example                           ← Template
├── 📄 .env.production                        ⭐ Production template
├── 📄 artisan                               ⭐ CLI tool
├── 📄 composer.json                         ⭐ Dependencies
├── 📄 composer.lock                         ⭐ Dependency lock
├── 📄 package.json                          ❌ NOT deployed
└── 📄 vite.config.js                        ❌ NOT deployed
```

### After: Hostinger Production (Flattened Structure)

```
📂 public_html/                              ⭐ WEB ROOT (Hostinger)
├── 📂 app/                                  ← Application code (same)
├── 📂 bootstrap/                            ← Framework bootstrap (same)
├── 📂 config/                               ← Configuration files (same)
├── 📂 database/                             ← Migrations & seeders (same)
├── 📂 resources/                            ← Views & assets (same)
├── 📂 routes/                               ← Route definitions (same)
├── 📂 storage/                              ← App storage (same)
│   ├── 📂 app/
│   │   └── 📂 public/
│   │       └── 📂 receipts/
│   ├── 📂 framework/
│   │   ├── 📂 cache/
│   │   ├── 📂 sessions/
│   │   └── 📂 views/
│   └── 📂 logs/
├── 📂 tests/                                ← Test files (same)
├── 📂 vendor/                               ⭐ INSTALLED ON SERVER
├── 📂 css/                                  ⭐ MOVED from public/
├── 📂 js/                                   ⭐ MOVED from public/
├── 📄 index.php                             ⭐ MOVED & MODIFIED
├── 📄 .htaccess                             ⭐ MOVED from public/
├── 📄 favicon.ico                           ⭐ MOVED from public/
├── 📄 robots.txt                            ⭐ MOVED from public/
├── 📄 .env                                  ⭐ Production environment
├── 📄 .env.production                       ← Backup copy
├── 📄 artisan                               ⭐ CLI tool (same)
├── 📄 composer.json                         ⭐ Dependencies (same)
└── 📄 composer.lock                         ⭐ Dependency lock (same)
```

### Key Visual Changes:

```
LOCAL:                              HOSTINGER:
public/ directory exists    →    No public/ directory (flattened)
├── index.php               →    index.php at root (paths fixed)
├── .htaccess               →    .htaccess at root
├── css/                    →    css/ at root level
├── js/                     →    js/ at root level
├── favicon.ico             →    favicon.ico at root
└── robots.txt              →    robots.txt at root
```

---

## 2. File Path Transformations

### index.php Path Changes

#### Local Version:
```php
<?php
define('LARAVEL_START', microtime(true));

// ❌ Uses ../ to go up from public/ directory
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// ❌ Uses ../ to go up from public/ directory
require __DIR__.'/../vendor/autoload.php';

// ❌ Uses ../ to go up from public/ directory
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
```

#### Hostinger Version:
```php
<?php
define('LARAVEL_START', microtime(true));

// ✅ No ../ needed (already at root)
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// ✅ No ../ needed (already at root)
require __DIR__.'/vendor/autoload.php';

// ✅ No ../ needed (already at root)
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

### Visual Path Mapping:

```
LOCAL STRUCTURE:                     HOSTINGER STRUCTURE:
public/index.php                    index.php
     ↓                                    ↓
     require ../vendor/autoload.php      require vendor/autoload.php
     ↓                                    ↓
     ../vendor/autoload.php              vendor/autoload.php
     ↓                                    ↓
     (goes up one level)                 (already at root)

LOCAL: public/ → ../vendor/        HOSTINGER: root → vendor/
LOCAL: public/ → ../bootstrap/     HOSTINGER: root → bootstrap/
LOCAL: public/ → ../storage/       HOSTINGER: root → storage/
```

---

## 3. File Movement Visual Map

### Files That Move Location:

```
┌─────────────────────────────────────────────────────────────────┐
│                         FILE MOVEMENT MAP                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  LOCAL PATH                    →    HOSTINGER PATH              │
│  ────────────                       ────────────────            │
│                                                                  │
│  📄 public/index.php          →    📄 index.php                 │
│       (Modified paths)                  (Fixed paths)           │
│                                                                  │
│  📄 public/.htaccess          →    📄 .htaccess                 │
│       (Same content)                     (Same content)        │
│                                                                  │
│  📄 public/css/style.css      →    📄 css/style.css             │
│       (Asset)                             (Asset)              │
│                                                                  │
│  📄 public/js/app.js         →    📄 js/app.js                 │
│       (Asset)                             (Asset)              │
│                                                                  │
│  📄 public/favicon.ico        →    📄 favicon.ico               │
│       (Icon)                              (Icon)                │
│                                                                  │
│  📄 public/robots.txt         →    📄 robots.txt                │
│       (SEO)                               (SEO)                 │
│                                                                  │
│  📄 public/storage →          →    📄 storage/app/public/       │
│    ../storage/app/public/            (manual symlink)           │
│    (symlink)                                                    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Files That Stay (No Location Change):

```
┌─────────────────────────────────────────────────────────────────┐
│                    UNCHANGED LOCATIONS                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  📂 app/              →    📂 app/              (same location)  │
│  📂 bootstrap/        →    📂 bootstrap/        (same location)  │
│  📂 config/           →    📂 config/           (same location)  │
│  📂 database/         →    📂 database/         (same location)  │
│  📂 resources/        →    📂 resources/        (same location)  │
│  📂 routes/           →    📂 routes/           (same location)  │
│  📂 storage/          →    📂 storage/          (same location)  │
│  📂 tests/            →    📂 tests/            (same location)  │
│  📄 artisan           →    📄 artisan           (same location)  │
│  📄 composer.json     →    📄 composer.json     (same location)  │
│  📄 composer.lock     →    📄 composer.lock     (same location)  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Configuration Transformation Flow

### Environment Variable Changes:

```
┌─────────────────────────────────────────────────────────────────┐
│                  .env TRANSFORMATION MAP                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  VARIABLE              LOCAL VALUE          PRODUCTION VALUE     │
│  ──────────            ────────────         ─────────────────    │
│                                                                  │
│  APP_ENV               local         →     production            │
│  APP_DEBUG             true          →     false                 │
│  APP_URL               http://localhost  →  https://yourdomain.com│
│                                                                  │
│  DB_CONNECTION         sqlite        →     mysql                 │
│  DB_HOST               (file-based)  →     localhost             │
│  DB_DATABASE           local.sqlite  →     uXXXXXXXX_db_name     │
│  DB_USERNAME           (none)        →     uXXXXXXXX_user        │
│  DB_PASSWORD           (none)        →     secure_password       │
│                                                                  │
│  SESSION_DOMAIN        null          →     .yourdomain.com       │
│  SESSION_DRIVER        file          →     database              │
│                                                                  │
│  LOG_LEVEL             debug         →     warning               │
│                                                                  │
│  CACHE_STORE           file          →     database              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. Permission Transformation

### Permission Changes Visual:

```
┌─────────────────────────────────────────────────────────────────┐
│                    PERMISSION CHANGES                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  PATH                   DEFAULT      PRODUCTION    COMMAND       │
│  ─────                  ───────      ────────────  ───────       │
│                                                                  │
│  All directories        755    →     755       (usually OK)     │
│  All files              644    →     644       (usually OK)     │
│                                                                  │
│  storage/               ???    →     775       chmod -R 775     │
│  storage/app/public/    ???    →     775       chmod -R 775     │
│  storage/framework/     ???    →     775       chmod -R 775     │
│  storage/logs/          ???    →     775       chmod -R 775     │
│                                                                  │
│  bootstrap/cache/       ???    →     775       chmod -R 775     │
│                                                                  │
│  artisan                ???    →     775       chmod +x         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

VISUAL REPRESENTATION:

Before:                              After:
drwxr-xr-x storage/                  drwxrwxr-x storage/
-rw-r--r-- file.php                  -rw-r--r-- file.php
-rwxr-xr-x artisan                   -rwxrwxr-x artisan

       │                                     │
       │  775 = rwxrwxr-x                    │  Writeable by group
       │  rwx = owner (read/write/execute)   │
       │  rwx = group  (read/write/execute)  │
       │  r-x = others (read/execute)        │
```

---

## 6. Deployment Process Flowchart

```
┌─────────────────────────────────────────────────────────────────┐
│                  DEPLOYMENT PROCESS FLOW                         │
└─────────────────────────────────────────────────────────────────┘

LOCAL MACHINE                           HOSTINGER SERVER
│                                     │
│  1. PREPARE                         │
│  ├─ Update .env.production          │
│  ├─ Test locally                    │
│  └─ Run deploy script               │
│      │                              │
│      ├── Create deployment package  │
│      ├── Fix index.php paths        │
│      ├── Flatten structure          │
│      └── Create ZIP                 │
│          │                          │
│          │  2. UPLOAD               │
│          ├─────────────────────────>│
│          │  Upload ZIP              │
│          │                          │
│          │                          │  3. EXTRACT
│          │                          ├─ Extract ZIP
│          │                          └─ Verify structure
│          │                          │
│          │                          │  4. INSTALL
│          │                          ├─ composer install
│          │                          ├─ Set permissions
│          │                          ├─ Run migrations
│          │                          ├─ Create storage link
│          │                          └─ Clear & cache config
│          │                          │
│          │                          │  5. VERIFY
│          │                          ├─ Test homepage
│          │                          ├─ Test routes
│          │                          ├─ Test forms
│          │                          └─ Test database
│          │                          │
│          │  ✅ DEPLOYMENT COMPLETE   │
```

---

## 7. URL Structure Transformation

### URL Mapping:

```
┌─────────────────────────────────────────────────────────────────┐
│                     URL STRUCTURE CHANGES                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  LOCAL URL                         PRODUCTION URL                │
│  ──────────                        ────────────────              │
│                                                                  │
│  http://localhost              →   https://yourdomain.com       │
│                                                                  │
│  http://localhost/daftar       →   https://yourdomain.com/daftar│
│                                                                  │
│  http://localhost/admin/login  →   https://yourdomain.com/admin  │
│                                                                  │
│  http://localhost/kedudukan    →   https://yourdomain.com/kedudukan│
│                                                                  │
│  /css/style.css                →   /css/style.css  (same!)      │
│                                                                  │
│  /js/app.js                    →   /js/app.js      (same!)      │
│                                                                  │
│  /storage/receipts/file.jpg    →   /storage/receipts/file.jpg   │
│                                  (or create symlink manually)    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

IMPORTANT: Asset URLs remain the same!
- CSS/JS URLs don't change
- Image URLs don't change
- Only domain name changes
```

---

## 8. Database Transformation Visual

### Database Migration:

```
┌─────────────────────────────────────────────────────────────────┐
│                    DATABASE TRANSFORMATION                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  LOCAL                            PRODUCTION                     │
│  ──────                           ───────────                    │
│                                                                  │
│  ┌─────────────┐                 ┌─────────────┐                │
│  │   SQLite    │                 │    MySQL     │                │
│  │  (File)     │      MIGRATE    │  (Server)   │                │
│  │             │   ────────────→  │             │                │
│  │ database    │                 │ uXXXXXXXX_  │                │
│  │ .sqlite     │                 │ bowling_db  │                │
│  └─────────────┘                 └─────────────┘                │
│       │                                │                        │
│       │                                │                        │
│  Connection: file-based          Connection: TCP/IP             │
│  Host: (file path)              Host: localhost                │
│  Port: (none)                   Port: 3306                     │
│  User: (none)                   User: uXXXXXXXX_user           │
│  Pass: (none)                   Pass: secure_password          │
│                                                                  │
│  MIGRATIONS:                                                   │
│  ┌─────────────┐                 ┌─────────────┐                │
│  │ php artisan │      SAME       │ php artisan │                │
│  │   migrate   │   ───────────→  │   migrate   │                │
│  └─────────────┘                 └─────────────┘                │
│                                                                  │
│  Tables created automatically in both cases!                    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 9. Common Error Patterns & Solutions

### Error Pattern Visual Guide:

```
┌─────────────────────────────────────────────────────────────────┐
│                    ERROR PATTERNS & FIXES                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ERROR                           PATTERN         SOLUTION        │
│  ──────                        ────────────     ─────────        │
│                                                                  │
│  "Class not found"               →  Missing vendor  →  composer  │
│                                                     install      │
│                                                                  │
│  "No such file or directory"     →  Wrong paths     →  Fix       │
│  in index.php                                          index.php │
│                                                     remove ../   │
│                                                                  │
│  "Permission denied"             →  storage not     →  chmod 775 │
│                                  →  writable        │  storage/  │
│                                                                  │
│  "Connection refused"            →  Wrong DB creds  →  Fix .env  │
│                                  →  DB missing      →  Create DB │
│                                                                  │
│  "404 Not Found"                 →  Wrong document  →  Configure │
│                                  →  root            →  domain    │
│                                                     to root     │
│                                                                  │
│  "Changes not                    →  Old cache       →  Clear all │
│   reflecting"                    →  stuck           │  caches    │
│                                                                  │
│  "File upload                    →  Missing symlink →  Create    │
│   not working"                   →  Wrong perms     │  storage:  │
│                                                     link       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 10. Quick Transformation Checklist

### Pre-Deployment Visual Checklist:

```
┌─────────────────────────────────────────────────────────────────┐
│                 PRE-DEPLOYMENT CHECKLIST                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  LOCAL PREPARATION:                                              │
│  ☐ Update .env.production with production values                │
│  ☐ Test application locally                                     │
│  ☐ Run deployment script                                        │
│  ☐ Verify deployment package created                            │
│  ☐ Check index.php paths fixed (no ../)                         │
│                                                                  │
│  HOSTINGER PREPARATION:                                          │
│  ☐ Create MySQL database in Hostinger                           │
│  ☐ Create database user with privileges                         │
│  ☐ Note database credentials (exact format)                     │
│  ☐ Configure domain document root (if needed)                   │
│                                                                  │
│  UPLOAD:                                                         │
│  ☐ Upload deployment ZIP to Hostinger                           │
│  ☐ Extract ZIP in public_html/                                  │
│  ☐ Verify files are at correct locations                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Post-Deployment Visual Checklist:

```
┌─────────────────────────────────────────────────────────────────┐
│                POST-DEPLOYMENT CHECKLIST                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  SERVER SETUP:                                                   │
│  ☐ Install vendor: composer install --no-dev                    │
│  ☐ Set permissions: chmod -R 775 storage bootstrap/cache        │
│  ☐ Make artisan executable: chmod +x artisan                    │
│                                                                  │
│  APPLICATION SETUP:                                              │
│  ☐ Run migrations: php artisan migrate --force                  │
│  ☐ Clear caches: php artisan cache:clear                        │
│  ☐ Rebuild caches: php artisan config:cache                     │
│  ☐ Create storage link: php artisan storage:link               │
│                                                                  │
│  VERIFICATION:                                                   │
│  ☐ Homepage loads without errors                                │
│  ☐ All routes work correctly                                    │
│  ☐ Forms submit successfully                                    │
│  ☐ File uploads work                                            │
│  ☐ Database operations work                                     │
│  ☐ No PHP errors in logs                                       │
│  ☐ SSL certificate working                                      │
│                                                                  │
│  SECURITY:                                                       │
│  ☐ APP_DEBUG=false                                              │
│  ☐ .env file not accessible via web                             │
│  ☐ Change default admin password                                │
│  ☐ Verify HTTPS enforced                                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 11. Transformation Summary Table

### Complete Transformation Reference:

```
┌───────────────────┬─────────────────────┬───────────────────────┐
│ ASPECT            │ LOCAL VALUE         │ PRODUCTION VALUE      │
├───────────────────┼─────────────────────┼───────────────────────┤
│ Structure         │ Standard Laravel    │ Flattened             │
│ Document Root     │ project/public/     │ public_html/          │
│ index.php         │ public/index.php   │ index.php             │
│ index.php paths   │ ../vendor/, etc.    │ vendor/, etc.         │
│ .htaccess         │ public/.htaccess    │ .htaccess             │
│ Assets location   │ public/css/, public/js/ │ css/, js/        │
│ Vendor            │ Locally installed   │ Installed on server   │
│ Environment       │ .env (local)        │ .env (production)     │
│ APP_ENV           │ local               │ production            │
│ APP_DEBUG         │ true                │ false                 │
│ APP_URL           │ http://localhost    │ https://domain.com    │
│ Database          │ SQLite              │ MySQL                 │
│ DB_HOST           │ (file)              │ localhost             │
│ DB_DATABASE       │ local.sqlite        │ uXXXXXXXX_db          │
│ DB_USERNAME       │ (none)              │ uXXXXXXXX_user        │
│ DB_PASSWORD       │ (none)              │ secure_password       │
│ SESSION_DOMAIN    │ null                │ .domain.com           │
│ SESSION_DRIVER    │ file                │ database              │
│ LOG_LEVEL         │ debug               │ warning               │
│ CACHE_STORE       │ file                │ database              │
│ Storage perms     │ Default             │ 775                   │
│ Cache perms       │ Default             │ 775                   │
│ Storage link      │ php artisan storage │ Manual or artisan     │
│ SSL               │ None                │ Required              │
└───────────────────┴─────────────────────┴───────────────────────┘
```

---

## 12. Deployment Script Visual Flow

### What the Deployment Script Does:

```
┌─────────────────────────────────────────────────────────────────┐
│              DEPLOYMENT SCRIPT EXECUTION FLOW                    │
└─────────────────────────────────────────────────────────────────┘

START
 │
 ├─→ [1/8] PRE-DEPLOYMENT CHECKS
 │   ├─ Check artisan exists
 │   ├─ Check .env.production exists
 │   └─ Check required tools available
 │
 ├─→ [2/8] CLEAN PREVIOUS DEPLOYMENTS
 │   ├─ Remove old deployment directory
 │   ├─ Create fresh deployment directory
 │   └─ Clean up old ZIP files
 │
 ├─→ [3/8] COPY PROJECT FILES
 │   ├─ Copy app/
 │   ├─ Copy bootstrap/
 │   ├─ Copy config/
 │   ├─ Copy database/
 │   ├─ Copy resources/
 │   ├─ Copy routes/
 │   ├─ Copy storage/
 │   ├─ Copy tests/
 │   ├─ Copy artisan
 │   ├─ Copy composer.json
 │   ├─ Copy composer.lock
 │   ├─ Copy .env.production → .env
 │   └─ Copy public/* → root level
 │
 ├─→ [4/8] FIX INDEX.PHP PATHS
 │   ├─ Create new index.php at root
 │   ├─ Remove ../ from all paths
 │   └─ Verify correct structure
 │
 ├─→ [5/8] SETUP STORAGE STRUCTURE
 │   ├─ Create storage/app/public/receipts/
 │   ├─ Create storage/framework/cache/
 │   ├─ Create storage/framework/sessions/
 │   ├─ Create storage/framework/views/
 │   └─ Create storage/logs/
 │
 ├─→ [6/8] CREATE DEPLOYMENT INSTRUCTIONS
 │   ├─ Create DEPLOY_INSTRUCTIONS.txt
 │   ├─ Include step-by-step commands
 │   └─ Add troubleshooting tips
 │
 ├─→ [7/8] CREATE ZIP PACKAGE
 │   ├─ Navigate to deployment directory
 │   ├─ Create ZIP archive
 │   ├─ Calculate file size
 │   └─ Count files
 │
 └─→ [8/8] GENERATE SUMMARY
     ├─ Show package location
     ├─ Show package size
     ├─ Display next steps
     └─ Provide documentation links

END
```

---

## Document Information

**Version**: 1.0
**Last Updated**: 2026-03-10
**Purpose**: Visual guide for Laravel to Hostinger deployment transformations
**Companion to**: `LARAVEL_TO_HOSTINGER_DEPLOYMENT_PATTERNS.md`

**Usage**: Use this guide alongside the detailed patterns document to visually understand the transformations needed for deployment.

**Key Visual Aids**:
- Directory structure comparisons
- Path transformation mappings
- File movement diagrams
- Configuration flow charts
- Error pattern guides
- Checklist visualizations

**Remember**: The visual representations here show the exact transformations needed. Follow these patterns to avoid common deployment issues.
