<?php

/**
 * Laravel Deployment File Change Detector & Transformer
 *
 * This system detects file changes since last deployment and applies
 * intelligent transformations for safe Laravel to Hostinger deployment.
 *
 * @version 1.0.0
 * @author Deployment System
 */

class DeploymentFileDetector
{
    private string $projectRoot;
    private string $deployDir;
    private string $lastDeployRef;
    private array $changedFiles = [];
    private array $fileCategories = [];
    private array $transformations = [];
    private array $skippedFiles = [];
    private array $validationErrors = [];

    /**
     * File type categories with their handling rules
     */
    private const FILE_CATEGORIES = [
        'views' => [
            'pattern' => '/resources\/views\/.*\.blade\.php$/',
            'transform' => 'checkTemplateLiterals',
            'copy' => true,
            'description' => 'Blade template files'
        ],
        'configs' => [
            'pattern' => '/config\/.*\.php$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Configuration files'
        ],
        'routes' => [
            'pattern' => '/routes\/.*\.php$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Route definition files'
        ],
        'assets_css' => [
            'pattern' => '/public\/css\/.*\.css$/',
            'transform' => null,
            'copy' => true,
            'description' => 'CSS assets'
        ],
        'assets_js' => [
            'pattern' => '/public\/js\/.*\.js$/',
            'transform' => null,
            'copy' => true,
            'description' => 'JavaScript assets'
        ],
        'migrations' => [
            'pattern' => '/database\/migrations\/.*\.php$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Database migrations'
        ],
        'controllers' => [
            'pattern' => '/app\/Http\/Controllers\/.*\.php$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Controller files'
        ],
        'models' => [
            'pattern' => '/app\/Models\/.*\.php$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Model files'
        ],
        'middleware' => [
            'pattern' => '/app\/Http\/Middleware\/.*\.php$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Middleware files'
        ],
        'seeders' => [
            'pattern' => '/database\/seeders\/.*\.php$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Database seeders'
        ],
        'index_php' => [
            'pattern' => '/^public\/index\.php$/',
            'transform' => 'transformIndexPhp',
            'copy' => false,
            'description' => 'Entry point file (special transformation)'
        ],
        'htaccess' => [
            'pattern' => '/^public\/\.htaccess$/',
            'transform' => 'moveHtaccess',
            'copy' => false,
            'description' => 'Apache configuration (moved to root)'
        ],
        'env_production' => [
            'pattern' => '/^\.env\.production$/',
            'transform' => 'validateEnvProduction',
            'copy' => false,
            'description' => 'Production environment (copied as .env)'
        ],
        'env' => [
            'pattern' => '/^\.env$/',
            'transform' => null,
            'copy' => false,
            'description' => 'Local environment (never deployed)'
        ],
        'artisan' => [
            'pattern' => '/^artisan$/',
            'transform' => null,
            'copy' => true,
            'description' => 'CLI tool'
        ],
        'composer' => [
            'pattern' => '/^composer\.(json|lock)$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Composer dependency files'
        ],
        'public_assets' => [
            'pattern' => '/^public\/(favicon\.ico|robots\.txt)$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Public root assets'
        ],
        'app_providers' => [
            'pattern' => '/app\/Providers\/.*\.php$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Service providers'
        ],
        'bootstrap' => [
            'pattern' => '/bootstrap\/(app\.php|providers\.php)$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Bootstrap files'
        ],
        'tests' => [
            'pattern' => '/tests\/.*\.php$/',
            'transform' => null,
            'copy' => true,
            'description' => 'Test files (optional)'
        ],
    ];

    /**
     * Files that should NEVER be deployed
     */
    private const EXCLUDED_FILES = [
        'node_modules',
        'vendor',
        '.git',
        '.DS_Store',
        '*.log',
        '.env.local',
        '.env.testing',
        'package-lock.json',
        'yarn.lock',
        'phpunit.xml',
        '.phpunit.result.cache',
        'deployment-package',
        'hostinger-deploy',
        '*.zip',
        '.idea',
        '.vscode',
        '*.cache',
    ];

    public function __construct(string $projectRoot, string $deployDir, ?string $lastDeployRef = null)
    {
        $this->projectRoot = rtrim($projectRoot, '/');
        $this->deployDir = rtrim($deployDir, '/');
        $this->lastDeployRef = $lastDeployRef ?? $this->findLastDeployRef();
    }

    /**
     * Detect all changed files since last deployment
     */
    public function detectChanges(): array
    {
        $this->println("Detecting file changes since last deployment...");

        if (!$this->lastDeployRef) {
            $this->println("No previous deployment found. Performing initial deployment.");
            return $this->getAllDeployableFiles();
        }

        // Get git diff
        $gitDiff = $this->executeGitCommand("diff --name-status {$this->lastDeployRef} HEAD");
        $gitLines = explode("\n", trim($gitDiff));

        foreach ($gitLines as $line) {
            if (empty($line)) continue;

            $parts = preg_split('/\s+/', $line);
            $status = $parts[0];
            $file = $parts[1] ?? '';

            if (empty($file)) continue;

            $this->changedFiles[$file] = [
                'status' => $status,
                'category' => $this->categorizeFile($file),
                'action' => $this->determineAction($file, $status),
            ];
        }

        return $this->changedFiles;
    }

    /**
     * Categorize a file by type
     */
    private function categorizeFile(string $file): string
    {
        foreach (self::FILE_CATEGORIES as $category => $rules) {
            if (preg_match($rules['pattern'], $file)) {
                return $category;
            }
        }
        return 'unknown';
    }

    /**
     * Determine what action to take with a file
     */
    private function determineAction(string $file, string $status): string
    {
        // Check if file should be excluded
        if ($this->isExcluded($file)) {
            return 'skip';
        }

        $category = $this->categorizeFile($file);

        // Special handling for specific files
        if ($file === '.env') {
            return 'skip';
        }

        if ($file === '.env.production') {
            return 'copy_as_env';
        }

        if ($file === 'public/index.php') {
            return 'transform';
        }

        if ($file === 'public/.htaccess') {
            return 'move_to_root';
        }

        // Handle deletions
        if ($status === 'D') {
            return 'delete';
        }

        // Check if file needs transformation
        $categoryRules = self::FILE_CATEGORIES[$category] ?? null;
        if ($categoryRules && $categoryRules['transform']) {
            return 'transform';
        }

        return 'copy';
    }

    /**
     * Check if file should be excluded from deployment
     */
    private function isExcluded(string $file): bool
    {
        foreach (self::EXCLUDED_FILES as $pattern) {
            if (fnmatch($pattern, basename($file)) || str_contains($file, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all deployable files for initial deployment
     */
    private function getAllDeployableFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->projectRoot, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($this->projectRoot . '/', '', $file->getPathname());

                if (!$this->isExcluded($relativePath)) {
                    $files[$relativePath] = [
                        'status' => 'A',
                        'category' => $this->categorizeFile($relativePath),
                        'action' => $this->determineAction($relativePath, 'A'),
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * Apply transformations to changed files
     */
    public function applyTransformations(): bool
    {
        $this->println("Applying transformations...");

        foreach ($this->changedFiles as $file => $info) {
            if ($info['action'] === 'skip') {
                $this->skippedFiles[] = $file;
                continue;
            }

            $category = $info['category'];
            $categoryRules = self::FILE_CATEGORIES[$category] ?? null;

            if ($categoryRules && $categoryRules['transform'] && $info['action'] === 'transform') {
                $transformMethod = $categoryRules['transform'];
                if (method_exists($this, $transformMethod)) {
                    $result = $this->$transformMethod($file);
                    if (!$result['success']) {
                        $this->validationErrors[] = $result['error'];
                        return false;
                    }
                    $this->transformations[$file] = $result;
                }
            } elseif ($info['action'] === 'copy_as_env') {
                $this->copyAsEnv($file);
            } elseif ($info['action'] === 'move_to_root') {
                $this->moveToRoot($file);
            } elseif ($info['action'] === 'delete') {
                $this->deleteFromDeploy($file);
            } else {
                $this->copyToDeploy($file);
            }
        }

        return true;
    }

    /**
     * Transform index.php for Hostinger deployment
     */
    private function transformIndexPhp(string $file): array
    {
        $sourcePath = $this->projectRoot . '/' . $file;
        $destPath = $this->deployDir . '/index.php';

        $content = file_get_contents($sourcePath);

        // Replace ../ with nothing (flatten paths)
        $content = str_replace(
            ["__DIR__.'/../", '__DIR__."../'],
            ["__DIR__.'/", '__DIR__."'],
            $content
        );

        // Write transformed file
        file_put_contents($destPath, $content);

        return [
            'success' => true,
            'type' => 'index_php_transform',
            'source' => $file,
            'destination' => 'index.php',
            'transformations' => [
                'Removed ../ from paths',
                'Moved to root level',
            ],
        ];
    }

    /**
     * Check Blade files for template literals
     */
    private function checkTemplateLiterals(string $file): array
    {
        $sourcePath = $this->projectRoot . '/' . $file;
        $destPath = $this->deployDir . '/' . $file;
        $content = file_get_contents($sourcePath);

        // Check for problematic template literals
        $issues = [];
        if (preg_match('/\${\s*\{/', $content)) {
            $issues[] = 'Found escaped template literals that may cause issues';
        }

        if (!empty($issues)) {
            return [
                'success' => false,
                'error' => implode(', ', $issues),
                'file' => $file,
            ];
        }

        // Ensure destination directory exists
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        copy($sourcePath, $destPath);

        return [
            'success' => true,
            'type' => 'blade_copy',
            'source' => $file,
            'destination' => $file,
            'checked' => 'template_literals',
        ];
    }

    /**
     * Validate .env.production
     */
    private function validateEnvProduction(string $file): array
    {
        $sourcePath = $this->projectRoot . '/' . $file;

        if (!file_exists($sourcePath)) {
            return [
                'success' => false,
                'error' => '.env.production file not found',
                'file' => $file,
            ];
        }

        $content = file_get_contents($sourcePath);
        $requiredVars = ['APP_ENV', 'APP_DEBUG', 'APP_URL', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE'];
        $missingVars = [];

        foreach ($requiredVars as $var) {
            if (!preg_match("/^{$var}=/m", $content)) {
                $missingVars[] = $var;
            }
        }

        if (!empty($missingVars)) {
            return [
                'success' => false,
                'error' => 'Missing required variables: ' . implode(', ', $missingVars),
                'file' => $file,
            ];
        }

        // Validate production settings
        $issues = [];
        if (preg_match('/^APP_ENV=.*local/m', $content)) {
            $issues[] = 'APP_ENV should be set to production';
        }
        if (preg_match('/^APP_DEBUG=true/m', $content)) {
            $issues[] = 'APP_DEBUG should be false in production';
        }

        if (!empty($issues)) {
            return [
                'success' => false,
                'error' => implode(', ', $issues),
                'file' => $file,
            ];
        }

        // Copy as .env
        copy($sourcePath, $this->deployDir . '/.env');

        return [
            'success' => true,
            'type' => 'env_copy',
            'source' => $file,
            'destination' => '.env',
            'validated' => true,
        ];
    }

    /**
     * Copy .env.production as .env
     */
    private function copyAsEnv(string $file): void
    {
        $sourcePath = $this->projectRoot . '/' . $file;
        copy($sourcePath, $this->deployDir . '/.env');
        $this->transformations[$file] = [
            'action' => 'copied_as_env',
            'destination' => '.env',
        ];
    }

    /**
     * Move .htaccess to root
     */
    private function moveToRoot(string $file): void
    {
        $sourcePath = $this->projectRoot . '/' . $file;
        $destPath = $this->deployDir . '/' . basename($file);
        copy($sourcePath, $destPath);
        $this->transformations[$file] = [
            'action' => 'moved_to_root',
            'destination' => basename($file),
        ];
    }

    /**
     * Delete file from deployment
     */
    private function deleteFromDeploy(string $file): void
    {
        $destPath = $this->deployDir . '/' . $file;
        if (file_exists($destPath)) {
            unlink($destPath);
        }
        $this->transformations[$file] = [
            'action' => 'deleted',
        ];
    }

    /**
     * Copy file to deployment directory
     */
    private function copyToDeploy(string $file): void
    {
        $sourcePath = $this->projectRoot . '/' . $file;
        $destPath = $this->deployDir . '/' . $file;

        // Ensure destination directory exists
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        copy($sourcePath, $destPath);
    }

    /**
     * Generate deployment manifest
     */
    public function generateManifest(): array
    {
        $manifest = [
            'timestamp' => date('Y-m-d H:i:s'),
            'git_ref' => $this->lastDeployRef,
            'changed_files' => count($this->changedFiles),
            'by_category' => [],
            'by_action' => [],
            'transformations' => count($this->transformations),
            'skipped' => count($this->skippedFiles),
            'validation_errors' => $this->validationErrors,
        ];

        // Group by category
        foreach ($this->changedFiles as $file => $info) {
            $category = $info['category'];
            $action = $info['action'];

            if (!isset($manifest['by_category'][$category])) {
                $manifest['by_category'][$category] = [];
            }
            $manifest['by_category'][$category][] = $file;

            if (!isset($manifest['by_action'][$action])) {
                $manifest['by_action'][$action] = [];
            }
            $manifest['by_action'][$action][] = $file;
        }

        return $manifest;
    }

    /**
     * Save manifest to file
     */
    public function saveManifest(string $path): void
    {
        $manifest = $this->generateManifest();
        file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT));
    }

    /**
     * Find the last deployment reference
     */
    private function findLastDeployRef(): ?string
    {
        // Try to read from .last_deploy file
        $lastDeployFile = $this->projectRoot . '/.last_deploy';
        if (file_exists($lastDeployFile)) {
            return trim(file_get_contents($lastDeployFile));
        }

        // Try to find a deployment tag
        $tags = $this->executeGitCommand('tag -l "deploy-*"');
        if (!empty($tags)) {
            $tagLines = explode("\n", trim($tags));
            return end($tagLines);
        }

        return null;
    }

    /**
     * Save current deployment reference
     */
    public function saveDeployRef(string $ref): void
    {
        file_put_contents($this->projectRoot . '/.last_deploy', $ref);
    }

    /**
     * Execute git command
     */
    private function executeGitCommand(string $command): string
    {
        $fullCommand = sprintf('cd %s && git %s 2>&1', escapeshellarg($this->projectRoot), $command);
        return shell_exec($fullCommand) ?? '';
    }

    /**
     * Print line to console
     */
    private function println(string $message): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Get transformations applied
     */
    public function getTransformations(): array
    {
        return $this->transformations;
    }

    /**
     * Get skipped files
     */
    public function getSkippedFiles(): array
    {
        return $this->skippedFiles;
    }

    /**
     * Get changed files
     */
    public function getChangedFiles(): array
    {
        return $this->changedFiles;
    }
}

// Example usage
if (php_sapi_name() === 'cli' && realpath($argv[0]) === realpath(__FILE__)) {
    $projectRoot = dirname(__DIR__);
    $deployDir = $projectRoot . '/hostinger-deploy';

    $detector = new DeploymentFileDetector($projectRoot, $deployDir);

    echo "=== Laravel Deployment File Change Detector ===" . PHP_EOL;
    echo "Project Root: {$projectRoot}" . PHP_EOL;
    echo "Deploy Dir: {$deployDir}" . PHP_EOL;
    echo PHP_EOL;

    // Detect changes
    $changes = $detector->detectChanges();

    echo "Found " . count($changes) . " changed files" . PHP_EOL;
    echo PHP_EOL;

    // Group by category
    $byCategory = [];
    foreach ($changes as $file => $info) {
        $category = $info['category'];
        if (!isset($byCategory[$category])) {
            $byCategory[$category] = [];
        }
        $byCategory[$category][] = $file;
    }

    echo "Changes by Category:" . PHP_EOL;
    foreach ($byCategory as $category => $files) {
        echo "  {$category}: " . count($files) . " files" . PHP_EOL;
    }
    echo PHP_EOL;

    // Apply transformations
    if ($detector->applyTransformations()) {
        echo "Transformations applied successfully!" . PHP_EOL;
        echo PHP_EOL;

        // Save manifest
        $manifestPath = $deployDir . '/DEPLOYMENT_MANIFEST.json';
        $detector->saveManifest($manifestPath);
        echo "Manifest saved to: {$manifestPath}" . PHP_EOL;
    } else {
        echo "Transformation errors:" . PHP_EOL;
        foreach ($detector->getValidationErrors() as $error) {
            echo "  - {$error}" . PHP_EOL;
        }
        exit(1);
    }
}
