<?php
/**
 * BestDeal CRM - Git Deployment Fix Tool
 * Access: https://bdfsloans.com/bestdealcrm/git_fix.php
 * 
 * This fixes cPanel Git "Deploy Head Commit" issues by:
 * 1. Checking file structure
 * 2. Fixing permissions
 * 3. Verifying .cpanel.yml
 */

$messages = [];
$errors = [];
$basePath = dirname(__DIR__); // Parent of git_fix.php location

// Detect where the files actually are
$possiblePaths = [
    '/home/un7xnx5s1w7m/public_html/bdfsloans.com/bestdealcrm',
    '/home/un7xnx5s1w7m/public_html/bdfsloans.com',
    $_SERVER['DOCUMENT_ROOT'] . '/bdfsloans.com/bestdealcrm',
    $_SERVER['DOCUMENT_ROOT'],
];

$actualPath = dirname(__FILE__); // Where this file actually lives
$deployPath = '/home/un7xnx5s1w7m/public_html/bdfsloans.com/bestdealcrm';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // ========== ACTION: Check Structure ==========
    if ($action === 'check') {
        $messages[] = "=== File Structure Check ===";
        $messages[] = "Current file location: " . dirname(__FILE__);
        $messages[] = "Document root: " . $_SERVER['DOCUMENT_ROOT'];
        
        // Check key files
        $checkFiles = [
            'install.php' => dirname(__FILE__) . '/install.php',
            'reset_password.php' => dirname(__FILE__) . '/reset_password.php',
            '.cpanel.yml' => dirname(__FILE__) . '/.cpanel.yml',
            'public/index.php' => dirname(__FILE__) . '/public/index.php',
            'app/Views/auth/login.php' => dirname(__FILE__) . '/app/Views/auth/login.php',
        ];
        
        foreach ($checkFiles as $name => $path) {
            if (file_exists($path)) {
                $messages[] = "✅ {$name} exists (size: " . filesize($path) . " bytes)";
            } else {
                $errors[] = "❌ {$name} NOT FOUND at {$path}";
            }
        }
        
        // Check .cpanel.yml content
        $cpanelFile = dirname(__FILE__) . '/.cpanel.yml';
        if (file_exists($cpanelFile)) {
            $messages[] = "=== .cpanel.yml Content ===";
            $messages[] = "<pre>" . htmlspecialchars(file_get_contents($cpanelFile)) . "</pre>";
        }
    }
    
    // ========== ACTION: Fix Permissions ==========
    if ($action === 'fix_perms') {
        $messages[] = "=== Fixing Permissions ===";
        
        // Fix this directory
        $dirs = [
            dirname(__FILE__),
            dirname(__FILE__) . '/public',
            dirname(__FILE__) . '/public/assets',
            dirname(__FILE__) . '/public/assets/css',
            dirname(__FILE__) . '/public/assets/js',
            dirname(__FILE__) . '/app',
            dirname(__FILE__) . '/config',
            dirname(__FILE__) . '/database',
            dirname(__FILE__) . '/routes',
        ];
        
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                chmod($dir, 0755);
                $messages[] = "✅ chmod 755: " . str_replace(dirname(__FILE__), '.', $dir);
            }
        }
        
        // Fix files
        $files = [
            dirname(__FILE__) . '/install.php',
            dirname(__FILE__) . '/reset_password.php',
            dirname(__FILE__) . '/git_fix.php',
            dirname(__FILE__) . '/.cpanel.yml',
            dirname(__FILE__) . '/.htaccess',
            dirname(__FILE__) . '/public/index.php',
            dirname(__FILE__) . '/public/.htaccess',
        ];
        
        foreach ($files as $file) {
            if (file_exists($file)) {
                chmod($file, 0644);
                $messages[] = "✅ chmod 644: " . str_replace(dirname(__FILE__), '.', $file);
            }
        }
        
        // Recursive fix for app directory
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(dirname(__FILE__) . '/app', RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                chmod($item->getPathname(), 0755);
            } else {
                chmod($item->getPathname(), 0644);
            }
        }
        $messages[] = "✅ App directory permissions fixed recursively";
    }
    
    // ========== ACTION: Fix .cpanel.yml ==========
    if ($action === 'fix_cpanel') {
        $messages[] = "=== Fixing .cpanel.yml ===";
        
        $cpanelContent = <<<YML
---
deployment:
  tasks:
    - export DEPLOYPATH=/home/un7xnx5s1w7m/public_html/bdfsloans.com/bestdealcrm/
    - /bin/cp -R * \$DEPLOYPATH
    - /bin/chmod -R 755 \$DEPLOYPATH
    - /bin/chmod 644 \$DEPLOYPATH*.php
    - /bin/chmod 644 \$DEPLOYPATH*.htaccess
YML;
        
        $cpanelFile = dirname(__FILE__) . '/.cpanel.yml';
        if (file_put_contents($cpanelFile, $cpanelContent)) {
            $messages[] = "✅ .cpanel.yml updated with correct deployment path";
            $messages[] = "<pre>" . htmlspecialchars($cpanelContent) . "</pre>";
        } else {
            $errors[] = "❌ Could not write .cpanel.yml";
        }
    }
    
    // ========== ACTION: Create .htaccess for root ==========
    if ($action === 'fix_htaccess') {
        $messages[] = "=== Fixing .htaccess ===";
        
        // Root .htaccess
        $rootHtaccess = dirname(__FILE__) . '/.htaccess';
        $rootContent = <<<HTACCESS
RewriteEngine On
RewriteBase /bestdealcrm/

# Allow direct access to PHP files in root
RewriteCond %{REQUEST_FILENAME} \.php$
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ public/index.php [QSA,L]

# Send everything else to public
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/$1 [L]
HTACCESS;
        
        if (file_put_contents($rootHtaccess, $rootContent)) {
            $messages[] = "✅ Root .htaccess updated";
        }
        
        // Public .htaccess
        $publicHtaccess = dirname(__FILE__) . '/public/.htaccess';
        $publicContent = <<<HTACCESS
RewriteEngine On
RewriteBase /bestdealcrm/public/

# Handle Front Controller
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
HTACCESS;
        
        if (file_put_contents($publicHtaccess, $publicContent)) {
            $messages[] = "✅ Public .htaccess updated";
        }
    }
    
    // ========== ACTION: Test DB Connection ==========
    if ($action === 'test_db') {
        $messages[] = "=== Testing Database Connection ===";
        
        try {
            $pdo = new PDO("mysql:host=68.178.237.250;dbname=bestdealcrm;charset=utf8mb4", 'sayali', 'sayali@1234', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $messages[] = "✅ Database connected successfully!";
            
            // Check tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $messages[] = "Tables found: " . count($tables);
            foreach ($tables as $t) {
                $count = $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
                $messages[] = "  - {$t} ({$count} rows)";
            }
            
            // Check admin user
            $admin = $pdo->query("SELECT id, username, name, role_id, status FROM users WHERE username='admin'")->fetch();
            if ($admin) {
                $messages[] = "✅ Admin user found: " . json_encode($admin);
                
                // Verify password
                $hash = $pdo->query("SELECT password_hash FROM users WHERE username='admin'")->fetchColumn();
                $verify = password_verify('admin123', $hash);
                $messages[] = $verify ? "✅ Password 'admin123' is VALID" : "❌ Password 'admin123' is INVALID - needs reset";
            } else {
                $errors[] = "❌ Admin user NOT FOUND - run install.php first";
            }
            
        } catch (PDOException $e) {
            $errors[] = "❌ Database error: " . $e->getMessage();
        }
    }
    
    // ========== ACTION: Full Fix ==========
    if ($action === 'full_fix') {
        $messages[] = "=== Running Full Fix ===";
        
        // 1. Fix .cpanel.yml
        $cpanelContent = "---\ndeployment:\n  tasks:\n    - export DEPLOYPATH=/home/un7xnx5s1w7m/public_html/bdfsloans.com/bestdealcrm/\n    - /bin/cp -R * \$DEPLOYPATH\n    - /bin/chmod -R 755 \$DEPLOYPATH\n";
        file_put_contents(dirname(__FILE__) . '/.cpanel.yml', $cpanelContent);
        $messages[] = "✅ .cpanel.yml fixed";
        
        // 2. Fix permissions
        chmod(dirname(__FILE__), 0755);
        chmod(dirname(__FILE__) . '/public', 0755);
        chmod(dirname(__FILE__) . '/app', 0755);
        $messages[] = "✅ Directory permissions fixed";
        
        // 3. Fix admin password
        try {
            $pdo = new PDO("mysql:host=68.178.237.250;dbname=bestdealcrm;charset=utf8mb4", 'sayali', 'sayali@1234', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $admin = $pdo->query("SELECT id FROM users WHERE username='admin'")->fetch();
            
            if ($admin) {
                $pdo->prepare("UPDATE users SET password_hash=? WHERE username='admin'")->execute([$hash]);
                $messages[] = "✅ Admin password reset to 'admin123'";
            } else {
                $roleId = $pdo->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
                if (!$roleId) {
                    $pdo->exec("INSERT INTO roles (name, display_name, description) VALUES ('admin','Admin','Full access')");
                    $roleId = $pdo->lastInsertId();
                }
                $pdo->prepare("INSERT INTO users (name, email, username, password_hash, role_id, status) VALUES ('Super Admin','admin@bestdealcrm.com','admin',?,?,?)")
                    ->execute([$hash, $roleId, 'active']);
                $messages[] = "✅ Admin user created";
            }
            
            // Verify
            $hash2 = $pdo->query("SELECT password_hash FROM users WHERE username='admin'")->fetchColumn();
            $verify = password_verify('admin123', $hash2);
            $messages[] = $verify ? "✅ Password verification PASSED" : "❌ Password verification FAILED";
            
        } catch (PDOException $e) {
            $errors[] = "❌ DB Error: " . $e->getMessage();
        }
        
        $messages[] = "";
        $messages[] = "=== DONE! Try logging in now ===";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BestDeal CRM - Git Fix Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light" style="padding:20px">
    <div class="container" style="max-width:800px">
        <h3 class="mb-4">🔧 Git Deployment Fix Tool</h3>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5>Quick Actions</h5>
                <form method="POST" class="d-grid gap-2">
                    <button type="submit" name="action" value="full_fix" class="btn btn-success btn-lg">
                        🚀 FULL FIX (Click This First!)
                    </button>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <button type="submit" name="action" value="check" class="btn btn-info w-100">
                                📋 Check Files
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="action" value="fix_perms" class="btn btn-warning w-100">
                                🔐 Fix Permissions
                            </button>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="action" value="test_db" class="btn btn-secondary w-100">
                                🗄️ Test Database
                            </button>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <button type="submit" name="action" value="fix_cpanel" class="btn btn-outline-primary w-100">
                                📄 Fix .cpanel.yml
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" name="action" value="fix_htaccess" class="btn btn-outline-primary w-100">
                                🔗 Fix .htaccess
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
                <?php foreach ($messages as $msg): ?>
                    <div><?= $msg ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($errors): ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">Errors</div>
            <div class="card-body">
                <?php foreach ($errors as $err): ?>
                    <div class="text-danger"><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="/bestdealcrm/public/index.php" class="btn btn-primary btn-lg">Go to Login</a>
        </div>
    </div>
</body>
</html>
