<?php
echo "PHP is working! Version: " . phpversion() . "<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";
echo "Server: " . php_uname() . "<br>";

// Test if autoloader works
$rootPath = dirname(__DIR__);
echo "Root path: {$rootPath}<br>";

// Test file exists
$indexFile = $rootPath . '/public/index.php';
echo "public/index.php exists: " . (file_exists($indexFile) ? 'YES (' . filesize($indexFile) . ' bytes)' : 'NO') . "<br>";

// Test .env
$envFile = $rootPath . '/.env';
echo ".env exists: " . (file_exists($envFile) ? 'YES' : 'NO') . "<br>";

// Test config
$configFile = $rootPath . '/config/config.php';
echo "config.php exists: " . (file_exists($configFile) . "<br>";

// Test if we can parse PHP
echo "Testing syntax check...<br>";
$output = [];
$returnCode = 0;
if (function_exists('shell_exec')) {
    $result = shell_exec("php -l {$indexFile} 2>&1");
    echo "PHP lint result: " . htmlspecialchars($result) . "<br>";
} else {
    echo "shell_exec not available<br>";
}
