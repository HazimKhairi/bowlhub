# Intelligent File Change Detection & Transformation System

## Overview

This system provides intelligent, Git-based change detection and safe file transformations for Laravel deployment to Hostinger shared hosting. It solves the critical problem of knowing exactly what changed between deployments and applying the right transformations automatically.

## Architecture

```
Local Development                    Deployment System
│                                   │
│  1. Git Analysis                  │
│  ├─ Detect changed files          │
│  ├─ Categorize by type            │
│  └─ Determine action needed       │
│      │                            │
│      │  2. Validation             │
│      ├─ Check .env.production     │
│      ├─ Validate Blade files      │
│      ├─ Verify configs            │
│      └─ Check assets              │
│          │                        │
│          │  3. Transformation      │
│          ├─ Fix index.php paths   │
│          ├─ Move public files     │
│          ├─ Copy .env.production  │
│          └─ Apply category rules  │
│              │                    │
│              │  4. Deployment      │
│              ├─ Copy changed files │
│              ├─ Create manifest    │
│              └─ Generate ZIP       │
│                  │                │
│                  │  5. Hostinger   │
│                  └─ Upload & Deploy
```

## Core Components

### 1. File Change Detector (deployment-file-detector.php)

**Purpose**: Intelligently detect and categorize file changes using Git.

**Key Features**:
- Git-based change detection
- Automatic file categorization
- Transformation rule application
- Validation before deployment
- Deployment manifest generation

### 2. Intelligent Deployment Script (deploy-intelligent.sh)

**Purpose**: Orchestrate the entire deployment process with safety checks.

**Key Features**:
- Pre-deployment validation
- Git change tracking
- Automatic transformations
- Safety validations
- Deployment packaging

## File Categories & Transformations

### Category Definitions

| Category | Pattern | Transform | Copy | Description |
|----------|---------|-----------|------|-------------|
| **views** | `/resources/views/*.blade.php` | checkTemplateLiterals | Yes | Blade templates |
| **configs** | `/config/*.php` | None | Yes | Configuration files |
| **routes** | `/routes/*.php` | None | Yes | Route definitions |
| **assets_css** | `/public/css/*.css` | None | Yes | Stylesheets |
| **assets_js** | `/public/js/*.js` | None | Yes | JavaScript |
| **migrations** | `/database/migrations/*.php` | None | Yes | DB migrations |
| **controllers** | `/app/Http/Controllers/*.php` | None | Yes | Controllers |
| **models** | `/app/Models/*.php` | None | Yes | Models |
| **index_php** | `/public/index.php` | transformIndexPhp | No | Entry point |
| **htaccess** | `/public/.htaccess` | moveHtaccess | No | Apache config |
| **env_production** | `/.env.production` | validateEnvProduction | No | Production env |
| **env** | `/.env` | None | No | Never deploy |

### Transformation Rules

#### 1. Blade Views (`checkTemplateLiterals`)

**Checks**:
- Escaped template literals: `${`
- Syntax errors
- Incompatible directives

**Action**:
- Copy directly if safe
- Flag for review if issues found

**Example**:
```php
// Safe - copy directly
{{ $user->name }}

// Flagged - manual review needed
${ '{' . $variable . '}' }
```

#### 2. index.php (`transformIndexPhp`)

**Transformations**:
1. Remove `../` from all paths
2. Move from `public/` to root
3. Flatten path structure

**Before** (local):
```php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
```

**After** (Hostinger):
```php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
```

#### 3. .env.production (`validateEnvProduction`)

**Validations**:
- File exists
- Required variables present:
  - `APP_ENV`
  - `APP_DEBUG`
  - `APP_URL`
  - `DB_CONNECTION`
  - `DB_HOST`
  - `DB_DATABASE`
- Production settings:
  - `APP_ENV=production` (not `local`)
  - `APP_DEBUG=false` (not `true`)

**Action**:
- Copy as `.env` if valid
- Fail deployment if invalid

#### 4. .htaccess (`moveHtaccess`)

**Action**:
- Move from `public/.htaccess` to `.htaccess` (root)
- Content unchanged

#### 5. Assets (CSS/JS)

**Action**:
- Move from `public/css/` to `css/` (root)
- Move from `public/js/` to `js/` (root)
- Content unchanged

#### 6. Config Files

**Action**:
- Copy directly
- No transformations needed
- Environment-specific values in `.env`

#### 7. Routes, Migrations, Controllers, Models

**Action**:
- Copy directly
- No transformations needed

## Validation Rules

### Pre-Deployment Checks

1. **Environment Validation**
   ```bash
   ✓ .env.production exists
   ✓ APP_ENV=production
   ✓ APP_DEBUG=false
   ✓ Database credentials present
   ```

2. **File Structure Validation**
   ```bash
   ✓ index.php will be transformed
   ✓ .htaccess will be moved
   ✓ Assets will be relocated
   ✓ No .env file in deployment
   ```

3. **Blade Template Validation**
   ```bash
   ✓ No escaped template literals
   ✓ No syntax errors
   ✓ Compatible directives
   ```

4. **Asset Validation**
   ```bash
   ✓ CSS files are present
   ✓ JS files are present
   ✓ Assets are built (if using Vite)
   ```

### Validation Errors

The system will fail deployment if:

- `.env.production` is missing
- `.env.production` has `APP_ENV=local`
- `.env.production` has `APP_DEBUG=true`
- Required database variables missing
- Blade files have problematic template literals
- Critical files missing from deployment

## Deployment Process

### Phase 1: Detection

```bash
# System analyzes Git changes
git diff --name-status LAST_DEPLOY HEAD

# Output:
M       app/Http/Controllers/UserController.php
A       resources/views/new-page.blade.php
D       public/old-file.css
```

### Phase 2: Categorization

```php
// Each file is categorized
[
    'app/Http/Controllers/UserController.php' => [
        'status' => 'M',
        'category' => 'controllers',
        'action' => 'copy'
    ],
    'resources/views/new-page.blade.php' => [
        'status' => 'A',
        'category' => 'views',
        'action' => 'transform'  // Check for template literals
    ],
    'public/old-file.css' => [
        'status' => 'D',
        'category' => 'assets_css',
        'action' => 'delete'
    ]
]
```

### Phase 3: Transformation

```php
// Apply transformations based on category
foreach ($files as $file => $info) {
    if ($info['action'] === 'transform') {
        $transformMethod = FILE_CATEGORIES[$info['category']]['transform'];
        $this->$transformMethod($file);
    }
}
```

### Phase 4: Validation

```bash
# Run pre-deployment checks
✓ index.php present
✓ .env present
✓ APP_ENV=production
✓ APP_DEBUG=false
✓ artisan present
✓ composer.json present
✓ All directories present
```

### Phase 5: Deployment

```bash
# Create deployment package
zip -r deployment.zip hostinger-deploy/

# Save deployment manifest
echo "CURRENT_COMMIT" > .last_deploy
```

## Deployment Manifest

The system generates a manifest file (`DEPLOYMENT_MANIFEST.json`) containing:

```json
{
    "timestamp": "2026-03-10 22:45:00",
    "git_ref": "a81c462commit-for-staging",
    "changed_files": 15,
    "by_category": {
        "views": 3,
        "controllers": 2,
        "configs": 1,
        "routes": 1,
        "assets_css": 2,
        "migrations": 1
    },
    "by_action": {
        "copy": 10,
        "transform": 3,
        "delete": 2
    },
    "transformations": 3,
    "skipped": 5,
    "validation_errors": []
}
```

## Usage

### Initial Deployment

```bash
# Run the intelligent deployment script
./deploy-intelligent.sh

# Output:
[1/9] Pre-deployment validation...
✓ Pre-deployment checks passed

[2/9] Detecting file changes via Git...
Current branch: staging
Current commit: a81c462...
No previous deployment found - performing initial deployment

[3/9] Cleaning previous deployments...
✓ Deployment directory prepared

[4/9] Detecting and categorizing files...
✓ File detection completed

[5/9] Applying file transformations...
✓ index.php transformed (paths flattened)
✓ .env.production → .env

[6/9] Setting up storage structure...
✓ Storage structure prepared

[7/9] Validating deployment package...
✓ index.php present
✓ .env present
✓ APP_ENV=production
✓ APP_DEBUG=false
✓ artisan present
✓ composer.json present

[8/9] Creating deployment instructions...
✓ Deployment instructions created

[9/9] Creating deployment package...
✓ Deployment package created

INTELLIGENT DEPLOYMENT COMPLETE!
Package: bowling-system-deploy-20260310_224500.zip
```

### Subsequent Deployments

```bash
# Make changes to your code
git add .
git commit -m "Add new feature"

# Run deployment
./deploy-intelligent.sh

# Output shows only changed files
[2/9] Detecting file changes via Git...
Current branch: staging
Current commit: b92c573...
Last deployment: a81c462...
Changes detected:
+5 additions
~3 modifications
-1 deletions
```

### Standalone File Detector

```bash
# Run just the file detector
php deployment-file-detector.php

# Output:
=== Laravel Deployment File Change Detector ===
Project Root: /path/to/project
Deploy Dir: /path/to/hostinger-deploy

Found 15 changed files

Changes by Category:
  views: 3 files
  controllers: 2 files
  configs: 1 files
  routes: 1 files
  assets_css: 2 files
  migrations: 1 files

Transformations applied successfully!
Manifest saved to: hostinger-deploy/DEPLOYMENT_MANIFEST.json
```

## File Action Matrix

| Git Status | File Type | Action | Description |
|------------|-----------|--------|-------------|
| **A** (Added) | View | Transform & Copy | Check template literals, then copy |
| **A** (Added) | Config | Copy | Copy directly |
| **A** (Added) | index.php | Transform | Flatten paths, move to root |
| **A** (Added) | .htaccess | Move | Move to root |
| **A** (Added) | Asset | Move | Move to root (css/, js/) |
| **M** (Modified) | View | Transform & Copy | Recheck, then copy |
| **M** (Modified) | Config | Copy | Update with changes |
| **M** (Modified) | index.php | Transform | Reapply path fixes |
| **D** (Deleted) | Any | Delete | Remove from deployment |
| **D** (Deleted) | .env | Skip | Never was deployed |
| **--** (Unchanged) | Any | Skip | Not in deployment package |

## Excluded Files

These files are **never** deployed:

```
node_modules/          # NPM dependencies
vendor/                # PHP dependencies (installed on server)
.git/                  # Version control
.DS_Store              # macOS files
*.log                  # Log files
.env.local             # Local environment
.env.testing           # Testing environment
package-lock.json      # NPM lock file
yarn.lock              # Yarn lock file
phpunit.xml            # PHPUnit config
.phpunit.result.cache  # PHPUnit cache
deployment-package/    # Old deployment
hostinger-deploy/      # Old deployment
*.zip                  # Deployment archives
.idea/                 # JetBrains IDE
.vscode/               # VS Code
*.cache                # Cache files
```

## Troubleshooting

### Issue: "No previous deployment found"

**Cause**: First time running the script or `.last_deploy` file missing.

**Solution**: Normal for initial deployment. Script will deploy all files.

### Issue: "Validation failed: APP_ENV not set to production"

**Cause**: `.env.production` has `APP_ENV=local`.

**Solution**:
```bash
# Edit .env.production
nano .env.production

# Change:
APP_ENV=production
```

### Issue: "Blade template validation failed"

**Cause**: Template contains problematic syntax.

**Solution**:
```php
// Bad - escaped template literal
${ '{' . $var . '}' }

// Good - use Blade directives
@php echo $var @endphp
// or
{{ $var }}
```

### Issue: "Deployment package not created"

**Cause**: Validation failed or transformation error.

**Solution**: Check validation output, fix errors, retry.

## Advanced Usage

### Custom File Categories

Add custom categories in `deployment-file-detector.php`:

```php
private const FILE_CATEGORIES = [
    // ... existing categories ...

    'custom_type' => [
        'pattern' => '/custom\/path\/.*\.ext$/',
        'transform' => 'customTransformMethod',
        'copy' => true,
        'description' => 'Custom file type'
    ],
];
```

### Custom Transformations

```php
private function customTransformMethod(string $file): array
{
    $sourcePath = $this->projectRoot . '/' . $file;
    $content = file_get_contents($sourcePath);

    // Apply custom transformations
    $content = str_replace('foo', 'bar', $content);

    // Write transformed file
    $destPath = $this->deployDir . '/' . $file;
    file_put_contents($destPath, $content);

    return [
        'success' => true,
        'type' => 'custom_transform',
        'source' => $file,
        'destination' => $file,
    ];
}
```

### Integration with CI/CD

```yaml
# .github/workflows/deploy.yml
name: Deploy to Hostinger

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2

      - name: Run Intelligent Deployment
        run: |
          php deployment-file-detector.php
          ./deploy-intelligent.sh

      - name: Upload to Hostinger
        # Add your upload logic here
```

## Performance Considerations

### Large Repositories

For repositories with thousands of files:

1. **Use Git efficiently**: Only analyze changed files
2. **Cache categories**: Store file categories between runs
3. **Parallel processing**: Process multiple files simultaneously

### Optimization Tips

```php
// Cache Git results
private ?array $gitDiffCache = null;

private function getGitDiff(): array
{
    if ($this->gitDiffCache === null) {
        $gitDiff = $this->executeGitCommand('diff --name-status HEAD');
        $this->gitDiffCache = $this->parseGitDiff($gitDiff);
    }
    return $this->gitDiffCache;
}
```

## Security Considerations

### Protected Files

- `.env` is **never** deployed
- `.env.production` is validated before deployment
- `.git` directory is excluded
- Development configs are excluded

### Validation Security

```php
// Validate .env.production
if (preg_match('/^APP_DEBUG=true/m', $content)) {
    return [
        'success' => false,
        'error' => 'APP_DEBUG must be false in production',
    ];
}
```

### Path Traversal Prevention

```php
// Prevent path traversal attacks
$sourcePath = realpath($this->projectRoot . '/' . $file);
if (!$sourcePath || strpos($sourcePath, $this->projectRoot) !== 0) {
    throw new SecurityException("Invalid file path");
}
```

## Best Practices

### 1. Always Test Locally First

```bash
# Test the deployment script locally
./deploy-intelligent.sh

# Verify the package contents
unzip -l bowling-system-deploy-*.zip

# Test in staging environment first
git checkout staging
./deploy-intelligent.sh
```

### 2. Keep .env.production Updated

```bash
# Before deployment, ensure .env.production is current
diff .env.example .env.production
```

### 3. Review Changes Before Deployment

```bash
# See what will be deployed
git diff LAST_DEPLOY HEAD

# Or use the detector
php deployment-file-detector.php
```

### 4. Backup Before Major Deployments

```bash
# Tag the working version
git tag backup-before-$(date +%Y%m%d)
git push origin backup-before-$(date +%Y%m%d)
```

### 5. Use Branching Strategy

```bash
# Feature branch workflow
git checkout -b feature/new-feature
# Make changes
git commit -am "Add new feature"

# Merge to staging
git checkout staging
git merge feature/new-feature
./deploy-intelligent.sh

# Test on staging, then merge to main
git checkout main
git merge staging
./deploy-intelligent.sh
```

## Monitoring & Logging

### Deployment Log

The system creates a deployment log with:

```json
{
    "timestamp": "2026-03-10 22:45:00",
    "git_ref": "a81c462",
    "files_deployed": 15,
    "transformations_applied": 3,
    "validation_passed": true,
    "deployment_time_seconds": 12.5
}
```

### Error Tracking

```php
// Log validation errors
foreach ($this->validationErrors as $error) {
    error_log("[DEPLOYMENT ERROR] {$error}");
}
```

## Comparison: Manual vs. Intelligent Deployment

| Aspect | Manual Deployment | Intelligent System |
|--------|------------------|-------------------|
| **Change Detection** | Manual review | Git-based automatic |
| **File Categorization** | None | Automatic |
| **Transformations** | Manual editing | Automatic |
| **Validation** | Manual checks | Automated validation |
| **Safety** | Error-prone | Multiple safety checks |
| **Speed** | Slow (10-30 min) | Fast (2-5 min) |
| **Traceability** | Poor | Excellent (manifest) |
| **Error Recovery** | Difficult | Easy (rollback) |

## Future Enhancements

### Planned Features

1. **Incremental Deployment**: Only upload changed files via FTP
2. **Rollback Support**: Automatic rollback on deployment failure
3. **Database Migration Safety**: Pre-flight migration checks
4. **Asset Optimization**: Automatic minification of CSS/JS
5. **Cache Busting**: Automatic cache invalidation
6. **Multi-Environment**: Support for staging, production, etc.
7. **Web Interface**: Dashboard for deployment management
8. **Slack/Discord Notifications**: Deployment status notifications

### Contribution

To contribute to this system:

1. Test thoroughly in staging
2. Add validation for new features
3. Update documentation
4. Add error handling
5. Maintain backward compatibility

## System Requirements

### Local Requirements

- PHP 8.1+
- Git
- Bash shell
- zip utility

### Server Requirements (Hostinger)

- PHP 8.2+
- MySQL
- Composer
- SSH access (recommended)

## License

This deployment system is part of the Bowling System project.

## Support

For issues or questions:

1. Check this documentation
2. Review `DEPLOYMENT_TRANSFORMATIONS.md`
3. Check `LARAVEL_TO_HOSTINGER_DEPLOYMENT_PATTERNS.md`
4. Review `DEPLOYMENT_CHECKLIST.md`

## Version History

- **1.0.0** (2026-03-10): Initial intelligent deployment system
  - Git-based change detection
  - File categorization
  - Automatic transformations
  - Validation system
  - Deployment manifest

---

**Remember**: This system is designed to make deployment safer and faster, but always test thoroughly in staging before deploying to production.
