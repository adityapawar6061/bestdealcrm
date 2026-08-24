<?php
/**
 * Minimal PHP test - access at /bestdealcrm/ping.php
 * If this returns "pong", PHP works. If 500, server PHP is broken.
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "pong - PHP " . phpversion() . " OK";

// Test autoloader
$rootPath = dirname(__DIR__);
echo "<br>ROOT_PATH would be: {$rootPath}";

// Test if index.php exists and what it contains
$indexFile = $rootPath . '/public/index.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    $hasChr = strpos($content, 'chr(92)') !== false;
    $hasOldAutoloader = strpos($content, "str_replace('\\\\\\\\'") !== false;
    echo "<br>index.php exists: " . filesize($indexFile) . " bytes";
    echo "<br>Has chr(92) fix: " . ($hasChr ? 'YES' : 'NO');
    echo "<br>Has old broken autoloader: " . ($hasOldAutoloader ? 'YES (BROKEN)' : 'NO');
    
    // Show line 50-60 of index.php
    $lines = explode("\n", $content);
    echo "<br><br><strong>Lines 50-65 of public/index.php:</strong><pre>";
    for ($i = 49; $i < min(65, count($lines)); $i++) {
        echo ($i+1) . ": " . htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
} else {
    echo "<br>index.php NOT FOUND at {$indexFile}";
}

// Test DB
echo "<br><br><strong>Testing Database:</strong><br>";
try {
    $envFile = $rootPath . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        $env = [];
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $env[trim($key)] = trim(trim($value), '"\'');
            }
        }
        echo "DB_HOST: " . ($env['DB_HOST'] ?? 'NOT SET') . "<br>";
        
        $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "✅ Database connected!<br>";
        $admin = $pdo->query("SELECT id,username,status FROM users WHERE username='admin'")->fetch();
        echo $admin ? "✅ Admin found: " . json_encode($admin) : "❌ No admin user";
    } else {
        echo "❌ .env not found";
    }
} catch (PDOException $e) {
    echo "❌ DB Error: " . $e->getMessage();
}
