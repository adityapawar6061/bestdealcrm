<?php
/**
 * Quick diagnostic — check what PHP features are available on the server
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Server PHP Diagnostic</h2>";
echo "<table border='1' cellpadding='6'>";

$checks = [
    'PHP Version' => phpversion(),
    'curl extension' => extension_loaded('curl') ? '✅ Yes' : '❌ No',
    'PDO extension' => extension_loaded('pdo') ? '✅ Yes' : '❌ No',
    'PDO MySQL' => extension_loaded('pdo_mysql') ? '✅ Yes' : '❌ No',
    'fileinfo extension' => extension_loaded('fileinfo') ? '✅ Yes' : '❌ No',
    'session extension' => extension_loaded('session') ? '✅ Yes' : '❌ No',
    'json extension' => extension_loaded('json') ? '✅ Yes' : '❌ No',
    'getallheaders()' => function_exists('getallheaders') ? '✅ Yes' : '❌ No',
    'stream_context_create' => function_exists('stream_context_create') ? '✅ Yes' : '❌ No',
    'file_get_contents' => function_exists('file_get_contents') ? '✅ Yes' : '❌ No',
];

foreach ($checks as $name => $val) {
    echo "<tr><td><strong>{$name}</strong></td><td>{$val}</td></tr>";
}
echo "</table>";

// Quick DB test
try {
    $pdo = new PDO("mysql:host=68.178.237.250;dbname=bestdealcrm;charset=utf8mb4", "sayali", "sayali@1234", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color:green'>✅ Database connection works</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ DB: " . htmlspecialchars($e->getMessage()) . "</p>";
}
