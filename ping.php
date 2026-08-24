#!/usr/bin/env php
<?php
/**
 * Diagnostic tool - access at: /bestdealcrm/ping.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h2>BestDeal CRM - Server Diagnostic</h2>";

// Figure out where we actually are
$thisDir = __DIR__;
echo "<p><strong>This file location:</strong> {$thisDir}</p>";
echo "<p><strong>dirname(__DIR__):</strong> " . dirname(__DIR__) . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";

// Find the real project root by looking for .env or config/config.php
$candidates = [dirname(__DIR__), __DIR__, dirname(dirname(__DIR__))];
$rootPath = null;
foreach ($candidates as $dir) {
    if (file_exists($dir . '/.env') || file_exists($dir . '/config/config.php') || file_exists($dir . '/config/database.php')) {
        $rootPath = $dir;
        break;
    }
}

if ($rootPath) {
    echo "<p style='color:green'><strong>Project root found:</strong> {$rootPath}</p>";
} else {
    echo "<p style='color:red'><strong>Project root NOT FOUND!</strong> Searched:</p>";
    echo "<ul>";
    foreach ($candidates as $dir) {
        echo "<li>{$dir}</li>";
    }
    echo "</ul>";
    
    // List what IS in parent dir
    echo "<p><strong>Files in " . dirname(__DIR__) . ":</strong></p><ul>";
    $items = scandir(dirname(__DIR__));
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = dirname(__DIR__) . '/' . $item;
        $type = is_dir($fullPath) ? 'DIR' : 'FILE (' . filesize($fullPath) . ' bytes)';
        echo "<li>{$item} [{$type}]</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>Files in " . dirname(dirname(__DIR__)) . ":</strong></p><ul>";
    $items = scandir(dirname(dirname(__DIR__)));
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = dirname(dirname(__DIR__)) . '/' . $item;
        $type = is_dir($fullPath) ? 'DIR' : 'FILE (' . filesize($fullPath) . ' bytes)';
        echo "<li>{$item} [{$type}]</li>";
    }
    echo "</ul>";
    exit;
}

// Check key files
echo "<h3>File Checks</h3><ul>";
$files = ['.env', 'config/config.php', 'config/database.php', 'config/Router.php', 
          'public/index.php', 'app/Helpers/Session.php', 'routes/web.php'];
foreach ($files as $f) {
    $full = $rootPath . '/' . $f;
    if (file_exists($full)) {
        echo "<li style='color:green'>✅ {$f} (" . filesize($full) . " bytes)</li>";
    } else {
        echo "<li style='color:red'>❌ {$f} NOT FOUND</li>";
    }
}
echo "</ul>";

// Check index.php content
$indexFile = $rootPath . '/public/index.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    echo "<h3>public/index.php Analysis</h3>";
    echo "<ul>";
    echo "<li>chr(92) autoloader: " . (strpos($content, 'chr(92)') !== false ? '✅ YES' : '❌ NO (uses broken backslash escaping)') . "</li>";
    echo "<li>Polyfills: " . (strpos($content, 'function_exists') !== false ? '✅ YES' : '❌ NO') . "</li>";
    echo "<li>Error logging: " . (strpos($content, 'error_log') !== false ? '✅ YES' : '❌ NO') . "</li>";
    echo "</ul>";
    
    // Show lines 48-68
    $lines = explode("\n", $content);
    echo "<h3>Lines 48-68:</h3><pre style='background:#f0f0f0;padding:10px;overflow-x:auto'>";
    for ($i = 47; $i < min(68, count($lines)); $i++) {
        echo ($i+1) . ": " . htmlspecialchars($lines[$i]) . "\n";
    }
    echo "</pre>";
}

// Test database
echo "<h3>Database Test</h3>";
if (file_exists($rootPath . '/.env')) {
    $envLines = file($rootPath . '/.env', FILE_IGNORE_NEW_LINES);
    $env = [];
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim(trim($v), '"\'');
        }
    }
    
    try {
        $dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        echo "<p style='color:green'>✅ Database connected!</p>";
        $admin = $pdo->query("SELECT id,username,status FROM users WHERE username='admin'")->fetch();
        echo $admin ? "<p>✅ Admin user: " . json_encode($admin) . "</p>" : "<p>❌ No admin user</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ DB Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>❌ .env not found</p>";
}
