<?php
// Test if BaseController can be loaded through the autoloader
error_reporting(E_ALL);
ini_set('display_errors', '1');

$rootPath = __DIR__;
define('ROOT_PATH', $rootPath);

echo "<h2>Autoloader Deep Test</h2>";

// Register the same autoloader as index.php
spl_autoload_register(function ($class) {
    echo "<p>Autoloader called for: <strong>{$class}</strong></p>";
    
    $map = array(
        'Database' => ROOT_PATH . '/config/database.php',
        'Router'   => ROOT_PATH . '/config/Router.php',
    );
    if (isset($map[$class])) {
        echo "<p> -> Found in simple map: {$map[$class]}</p>";
        if (file_exists($map[$class])) { 
            require_once $map[$class]; 
            echo "<p> -> Loaded OK</p>";
        }
        return;
    }
    $parts = explode(chr(92), $class);
    if (count($parts) < 2) { echo "<p> -> Not namespaced, skipping</p>"; return; }
    $relativePath = implode(DIRECTORY_SEPARATOR, $parts);
    $filePath = ROOT_PATH . '/app/' . $relativePath . '.php';
    echo "<p> -> Looking for: {$filePath}</p>";
    echo "<p> -> File exists: " . (file_exists($filePath) ? 'YES' : 'NO') . "</p>";
    if (file_exists($filePath)) { 
        require_once $filePath; 
        echo "<p> -> Loaded OK</p>";
    }
});

// Test loading config first (like index.php does)
echo "<hr><h3>Loading config...</h3>";
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
    echo "<p>Config loaded OK</p>";
}

echo "<h3>Loading Session...</h3>";
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) {
    require_once ROOT_PATH . '/app/Helpers/Session.php';
    echo "<p>Session loaded OK</p>";
}

echo "<h3>Loading Helpers...</h3>";
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) {
    require_once ROOT_PATH . '/app/Helpers/Helpers.php';
    echo "<p>Helpers loaded OK</p>";
}

// Now test autoloading BaseController
echo "<hr><h3>Testing BaseController autoload...</h3>";
echo "<p>class_exists('Controllers\BaseController'): ";
try {
    $result = class_exists('Controllers\BaseController');
    echo $result ? "TRUE" : "FALSE";
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
}
echo "</p>";

// Test AuthController
echo "<p>class_exists('Controllers\AuthController'): ";
try {
    $result = class_exists('Controllers\AuthController');
    echo $result ? "TRUE" : "FALSE";
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
}
echo "</p>";
