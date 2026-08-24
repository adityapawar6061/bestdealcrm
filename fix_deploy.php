<?php
/**
 * One-click deployment fix
 * Access: https://bdfsloans.com/bestdealcrm/fix_deploy.php
 * This fixes the cPanel git merge conflict by resetting .htaccess
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$basePath = dirname(__FILE__);
$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'fix_conflict') {
        // Fix the .htaccess conflict
        $htaccess = $basePath . '/.htaccess';
        if (file_exists($htaccess)) {
            // Read current content and write clean version
            $cleanContent = 'RewriteEngine On
RewriteBase /bestdealcrm/

# Allow direct access to root-level PHP files (no auth required)
RewriteCond %{REQUEST_URI} ^/bestdealcrm/(install|reset_password|git_fix|fix_deploy|diagnose)\\.php$
RewriteRule ^ - [L]

# Allow direct access to files that exist
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]

# Route everything else through public/index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php [QSA,L]
';
            if (file_put_contents($htaccess, $cleanContent)) {
                $messages[] = "✅ .htaccess rewritten with clean content";
            } else {
                $errors[] = "❌ Could not write .htaccess";
            }
        }
        
        // Try git operations
        $output = [];
        $returnCode = 0;
        
        // Reset any uncommitted changes
        exec("cd {$basePath} && git checkout -- . 2>&1", $output, $returnCode);
        $messages[] = "git checkout: " . implode("\n", $output);
        
        // Pull latest
        $output = [];
        exec("cd {$basePath} && git pull origin main 2>&1", $output, $returnCode);
        $messages[] = "git pull: " . implode("\n", $output);
        
        // Check current state
        $output = [];
        exec("cd {$basePath} && git log --oneline -3 2>&1", $output, $returnCode);
        $messages[] = "Current commits:\n" . implode("\n", $output);
    }
    
    if ($action === 'check') {
        // Check file sizes
        $checkFiles = [
            'public/index.php',
            '.htaccess',
            'config/config.php',
            'config/database.php',
            'app/Controllers/AuthController.php',
            'app/Helpers/Session.php',
            'routes/web.php',
        ];
        
        foreach ($checkFiles as $file) {
            $fullPath = $basePath . '/' . $file;
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                $modified = date('Y-m-d H:i:s', filemtime($fullPath));
                $messages[] = "✅ {$file} — {$size} bytes, modified {$modified}";
            } else {
                $errors[] = "❌ {$file} — NOT FOUND";
            }
        }
        
        // Check index.php content
        $indexContent = file_get_contents($basePath . '/public/index.php');
        if (strpos($indexContent, 'chr(92)') !== false) {
            $messages[] = "✅ index.php has chr(92) autoloader fix";
        } elseif (strpos($indexContent, "str_replace('\\\\\\\\'") !== false) {
            $errors[] = "❌ index.php still has old broken autoloader!";
        } else {
            $messages[] = "⚠️ index.php autoloader type unknown";
        }
        
        if (strpos($indexContent, 'str_contains') !== false && strpos($indexContent, 'function_exists') !== false) {
            $messages[] = "✅ index.php has PHP 7.x polyfills";
        }
        
        // PHP version
        $messages[] = "PHP Version: " . phpversion();
    }
    
    if ($action === 'full_fix') {
        $messages[] = "=== FULL FIX ===";
        
        // 1. Rewrite .htaccess
        $cleanContent = 'RewriteEngine On
RewriteBase /bestdealcrm/

# Allow direct access to root-level PHP files
RewriteCond %{REQUEST_URI} ^/bestdealcrm/(install|reset_password|git_fix|fix_deploy|diagnose)\\.php$
RewriteRule ^ - [L]

# Allow direct access to files that exist
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^ - [L]

# Route everything else through public/index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php [QSA,L]
';
        file_put_contents($basePath . '/.htaccess', $cleanContent);
        $messages[] = "✅ Root .htaccess fixed";
        
        // 2. Fix public .htaccess
        $publicContent = 'RewriteEngine On
RewriteBase /bestdealcrm/

# Redirect Trailing Slashes
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)/$ /$1 [L,R=301]

# Handle Front Controller
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

# Protect sensitive files
RewriteRule ^\\.env - [F,L]
RewriteRule ^\\.git - [F,L]
RewriteRule ^config/ - [F,L]
';
        file_put_contents($basePath . '/public/.htaccess', $publicContent);
        $messages[] = "✅ Public .htaccess fixed";
        
        // 3. Fix permissions
        chmod($basePath, 0755);
        chmod($basePath . '/public', 0755);
        chmod($basePath . '/storage', 0755);
        chmod($basePath . '/storage/logs', 0755);
        $messages[] = "✅ Permissions fixed";
        
        // 4. Check index.php
        $indexContent = file_get_contents($basePath . '/public/index.php');
        if (strpos($indexContent, 'chr(92)') !== false) {
            $messages[] = "✅ index.php has correct autoloader";
        } else {
            $errors[] = "❌ index.php autoloader needs fixing";
        }
        
        // 5. Git reset
        $output = [];
        exec("cd {$basePath} && git checkout -- . 2>&1", $output, $returnCode);
        if ($returnCode === 0) {
            $messages[] = "✅ Git working tree cleaned";
        } else {
            $messages[] = "⚠️ Git checkout: " . implode("\n", $output);
        }
        
        $messages[] = "\n=== DONE ===";
        $messages[] = "Now try: https://bdfsloans.com/bestdealcrm/public/index.php";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fix Deployment - BestDeal CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light" style="padding:20px">
    <div class="container" style="max-width:800px">
        <h3 class="mb-4">🔧 Fix Deployment Tool</h3>
        
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" class="d-grid gap-2">
                    <button type="submit" name="action" value="full_fix" class="btn btn-success btn-lg">
                        🚀 FULL FIX — Click This First!
                    </button>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <button type="submit" name="action" value="fix_conflict" class="btn btn-warning w-100">
                                Fix Git Conflict
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" name="action" value="check" class="btn btn-info w-100">
                                Check Files
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if ($messages): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">Results</div>
            <div class="card-body">
                <pre style="white-space:pre-wrap;"><?= htmlspecialchars(implode("\n", $messages)) ?></pre>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($errors): ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">Errors</div>
            <div class="card-body">
                <pre style="white-space:pre-wrap;"><?= htmlspecialchars(implode("\n", $errors)) ?></pre>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="/bestdealcrm/public/index.php" class="btn btn-primary btn-lg">Go to Login</a>
        </div>
    </div>
</body>
</html>
