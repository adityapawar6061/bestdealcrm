<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!function_exists('str_contains')) {
    function str_contains($h, $n) { return $n === '' || strpos($h, $n) !== false; }
}

define('ROOT_PATH', __DIR__);

// Load config
if (file_exists(ROOT_PATH . '/config/config.php')) { require_once ROOT_PATH . '/config/config.php'; }

// Load Session
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) { require_once ROOT_PATH . '/app/Helpers/Session.php'; }

// Load Helpers  
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) { require_once ROOT_PATH . '/app/Helpers/Helpers.php'; }

// Test: Can we read BaseController.php content?
$bcFile = ROOT_PATH . '/app/Controllers/BaseController.php';
$bcContent = file_get_contents($bcFile);

// Check if file has BOM or weird characters
$first3 = substr($bcContent, 0, 3);
$hasBOM = ($first3 === "\xEF\xBB\xBF");

// Check if class definition exists
$hasClass = (strpos($bcContent, 'class BaseController') !== false);

// Show diagnostic
echo "<h1>BaseController Diagnostic</h1>";
echo "<p>File size: " . strlen($bcContent) . " bytes</p>";
echo "<p>Has BOM: " . ($hasBOM ? 'YES (BAD!)' : 'NO') . "</p>";
echo "<p>Has class definition: " . ($hasClass ? 'YES' : 'NO') . "</p>";
echo "<p>First 200 chars (hex dump):</p>";
echo "<pre>" . htmlspecialchars(substr(bin2hex(substr($bcContent, 0, 200)), 0, 200)) . "</pre>";
echo "<p>First 200 chars (text):</p>";
echo "<pre>" . htmlspecialchars(substr($bcContent, 0, 200)) . "</pre>";

// Try eval to check syntax
echo "<h2>Testing file with eval...</h2>";
echo "<p>This tests if PHP can parse the file content</p>";
echo "<hr>";

// Try direct include with error capture
echo "<h2>Testing direct include with error capture...</h2>";
$oldLevel = error_reporting(E_ALL);
ob_start();
@include $bcFile;
$output = ob_get_clean();
error_reporting($oldLevel);

echo "<p>Include output: <pre>" . htmlspecialchars($output) . "</pre></p>";
echo "<p>class_exists after include: " . (class_exists('BaseController') ? 'YES' : 'NO') . "</p>";

// If class not defined, try manual class definition
if (!class_exists('BaseController')) {
    echo "<h2>Class NOT defined. Trying manual include with pre-loading...</h2>";
    
    // Try loading Session first explicitly
    echo "<p>Loading Session.php first...</p>";
    ob_start();
    @require ROOT_PATH . '/app/Helpers/Session.php';
    $sOut = ob_get_clean();
    echo "<p>Session output: " . htmlspecialchars($sOut) . "</p>";
    
    // Now try BaseController
    echo "<p>Loading BaseController.php...</p>";
    ob_start();
    @require $bcFile;
    $bcOut = ob_get_clean();
    echo "<p>BaseController output: " . htmlspecialchars($bcOut) . "</p>";
    echo "<p>class_exists now: " . (class_exists('BaseController') ? 'YES' : 'NO') . "</p>";
}
