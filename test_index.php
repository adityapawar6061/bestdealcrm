<?php
// Test if PHP executes at root level
echo "<h2>PHP Execution Test</h2>";
echo "<p>PHP " . phpversion() . " OK</p>";

$rootPath = __DIR__;
echo "<p>ROOT_PATH: {$rootPath}</p>";

// Test 1: polyfills
echo "<p>Test 1: Polyfills... ";
if (!function_exists('str_contains')) {
    function str_contains($h, $n) { return $n === '' || strpos($h, $n) !== false; }
}
echo "OK</p>";

// Test 2: define ROOT_PATH
echo "<p>Test 2: ROOT_PATH constant... ";
define('ROOT_PATH', $rootPath);
echo ROOT_PATH . "</p>";

// Test 3: Autoloader
echo "<p>Test 3: Autoloader... ";
spl_autoload_register(function ($class) {
    $map = array(
        'Database' => ROOT_PATH . '/config/database.php',
        'Router'   => ROOT_PATH . '/config/Router.php',
    );
    if (isset($map[$class])) {
        if (file_exists($map[$class])) { require_once $map[$class]; }
        return;
    }
    $parts = explode(chr(92), $class);
    if (count($parts) < 2) return;
    $relativePath = implode(DIRECTORY_SEPARATOR, $parts);
    $filePath = ROOT_PATH . '/app/' . $relativePath . '.php';
    if (file_exists($filePath)) { require_once $filePath; }
});
echo "OK</p>";

// Test 4: Config
echo "<p>Test 4: Config... ";
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
    echo "OK (DB_HOST=" . getenv('DB_HOST') . ")</p>";
} else {
    echo "NOT FOUND!</p>";
}

// Test 5: Session
echo "<p>Test 5: Session.php... ";
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) {
    require_once ROOT_PATH . '/app/Helpers/Session.php';
    echo "OK</p>";
} else {
    echo "NOT FOUND!</p>";
}

// Test 6: Helpers
echo "<p>Test 6: Helpers.php... ";
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) {
    require_once ROOT_PATH . '/app/Helpers/Helpers.php';
    echo "OK</p>";
} else {
    echo "NOT FOUND!</p>";
}

// Test 7: Routes
echo "<p>Test 7: Routes... ";
if (file_exists(ROOT_PATH . '/routes/web.php')) {
    require_once ROOT_PATH . '/routes/web.php';
    echo "OK (router is " . (isset($router) ? get_class($router) : 'NOT SET') . ")</p>";
} else {
    echo "NOT FOUND!</p>";
}

// Test 8: Router dispatch
echo "<p>Test 8: Router dispatch... ";
if (isset($router) && $router instanceof Router) {
    echo "Ready to dispatch (will redirect to login)</p>";
    // Don't actually dispatch in test - just show it works
    echo "<p style='color:green'>ALL TESTS PASSED! Router is ready.</p>";
} else {
    echo "Router NOT available</p>";
}

echo "<hr><p><a href='/bestdealcrm/'>Go to Login</a></p>";
