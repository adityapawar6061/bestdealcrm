<?php
/**
 * Diagnostic tool - access at: /bestdealcrm/diagnose.php
 * Tests each component and reports errors
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>BestDeal CRM Diagnostics</h2>";
echo "<pre>";

$rootPath = dirname(__DIR__);
echo "Root path: {$rootPath}\n";
echo "PHP version: " . phpversion() . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

// Test 1: Check ROOT_PATH is correct
echo "=== Test 1: File Structure ===\n";
$files = [
    'public/index.php' => $rootPath . '/public/index.php',
    'config/config.php' => $rootPath . '/config/config.php',
    'config/database.php' => $rootPath . '/config/database.php',
    'config/Router.php' => $rootPath . '/config/Router.php',
    'app/Helpers/Session.php' => $rootPath . '/app/Helpers/Session.php',
    'app/Helpers/Helpers.php' => $rootPath . '/app/Helpers/Helpers.php',
    'app/Controllers/AuthController.php' => $rootPath . '/app/Controllers/AuthController.php',
    'app/Controllers/BaseController.php' => $rootPath . '/app/Controllers/BaseController.php',
    'routes/web.php' => $rootPath . '/routes/web.php',
    '.env' => $rootPath . '/.env',
];

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    echo ($exists ? "✅" : "❌") . " {$name} ({$size} bytes)\n";
}

// Test 2: Check PHP functions
echo "\n=== Test 2: PHP Version Features ===\n";
echo "PHP " . phpversion() . "\n";
echo "str_contains: " . (function_exists('str_contains') ? '✅ native' : 'needs polyfill') . "\n";
echo "str_starts_with: " . (function_exists('str_starts_with') ? '✅ native' : 'needs polyfill') . "\n";

// Test 3: Polyfills
echo "\n=== Test 3: Polyfill Test ===\n";
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
echo "str_contains('hello world', 'world'): " . (str_contains('hello world', 'world') ? '✅' : '❌') . "\n";
echo "str_starts_with('/admin/dashboard', '/admin'): " . (str_starts_with('/admin/dashboard', '/admin') ? '✅' : '❌') . "\n";

// Test 4: Config loading
echo "\n=== Test 4: Config Loading ===\n";
try {
    require_once $rootPath . '/config/config.php';
    echo "✅ config.php loaded\n";
    echo "DB_HOST: " . getenv('DB_HOST') . "\n";
    echo "DB_NAME: " . getenv('DB_NAME') . "\n";
    echo "DB_USER: " . getenv('DB_USER') . "\n";
    echo "DB_PASS: " . (getenv('DB_PASS') ? '***SET***' : 'EMPTY') . "\n";
} catch (Throwable $e) {
    echo "❌ config.php error: " . $e->getMessage() . "\n";
}

// Test 5: Database connection
echo "\n=== Test 5: Database Connection ===\n";
try {
    $host = getenv('DB_HOST');
    $dbName = getenv('DB_NAME');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASS');
    
    $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ Database connected\n";
    
    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . count($tables) . "\n";
    
    $admin = $pdo->query("SELECT id, username, status FROM users WHERE username='admin'")->fetch();
    if ($admin) {
        echo "✅ Admin user exists: " . json_encode($admin) . "\n";
    } else {
        echo "❌ Admin user NOT found\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 6: Autoloader
echo "\n=== Test 6: Autoloader Test ===\n";
try {
    spl_autoload_register(function ($class) use ($rootPath) {
        if ($class === 'Database') {
            require_once $rootPath . '/config/database.php';
            return;
        }
        if ($class === 'Router') {
            require_once $rootPath . '/config/Router.php';
            return;
        }
        $file = $rootPath . '/app/' . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    });
    
    echo "Testing Database class... ";
    $db = Database::getInstance();
    echo "✅ Database loaded\n";
    
    echo "Testing Router class... ";
    $r = new Router();
    echo "✅ Router loaded\n";
    
    echo "Testing BaseController... ";
    // We can't instantiate BaseController without auth, but we can check if the file loads
    $file = $rootPath . '/app/Controllers/BaseController.php';
    if (file_exists($file)) {
        require_once $file;
        echo "✅ BaseController loaded\n";
    }
    
    echo "Testing AuthController file exists... ";
    $file = $rootPath . '/app/Controllers/AuthController.php';
    echo (file_exists($file) ? "✅" : "❌") . "\n";
    
} catch (Throwable $e) {
    echo "❌ Autoloader error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Test 7: Session
echo "\n=== Test 7: Session Test ===\n";
try {
    require_once $rootPath . '/app/Helpers/Session.php';
    echo "✅ Session.php loaded\n";
    echo "isAuthenticated: " . (isAuthenticated() ? 'true' : 'false') . "\n";
} catch (Throwable $e) {
    echo "❌ Session error: " . $e->getMessage() . "\n";
}

echo "\n=== DONE ===\n";
echo "</pre>";
