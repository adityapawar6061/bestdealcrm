<?php
/**
 * BestDeal CRM - Front Controller
 * All requests routed through this file via .htaccess.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_error.log');

// Set timezone to IST (Asia/Kolkata, UTC+5:30)
date_default_timezone_set('Asia/Kolkata');

// PHP 7.x polyfills
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}

// __DIR__ is bestdealcrm/public, so dirname gets bestdealcrm/
// But if server resolves paths differently, we detect the real repo root
$rootPath = dirname(__DIR__);
// Verify: if .env is not at this rootPath, try going up one more level
if (!file_exists($rootPath . '/.env') && !file_exists($rootPath . '/config/config.php')) {
    $rootPath = dirname($rootPath);
}
define('ROOT_PATH', $rootPath);

// Step-by-step logging to find exactly where the 500 happens
function _log($msg) {
    $ist = (new \DateTime('now', new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s');
    @file_put_contents(
        dirname(__DIR__) . '/storage/logs/app.log',
        $ist . ' | ' . $msg . "\n",
        FILE_APPEND
    );
}
_log('--- REQUEST START ---');
_log('REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
_log('ROOT_PATH: ' . $rootPath);

// Shutdown error handler to capture fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $ist = (new \DateTime('now', new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s');
        @file_put_contents(
            dirname(__DIR__) . '/storage/logs/shutdown_error.log',
            $ist . ' | ' . $error['message'] . ' | ' . $error['file'] . ':' . $error['line'] . "\n",
            FILE_APPEND
        );
    }
});

// Autoloader - uses directory scanning, zero backslash escaping
spl_autoload_register(function ($class) {
    // Map known non-namespaced classes
    $map = array(
        'Database' => ROOT_PATH . '/config/database.php',
        'Router'   => ROOT_PATH . '/config/Router.php',
    );
    if (isset($map[$class])) {
        if (file_exists($map[$class])) {
            require_once $map[$class];
        }
        return;
    }

    // Namespaced classes: split on backslash and build file path
    // e.g. "Controllers\AuthController" => "app/Controllers/AuthController.php"
    $parts = explode(chr(92), $class); // chr(92) = backslash - avoids any escaping issues
    if (count($parts) < 2) return;

    $relativePath = implode(DIRECTORY_SEPARATOR, $parts);
    $filePath = ROOT_PATH . '/app/' . $relativePath . '.php';

    if (file_exists($filePath)) {
        require_once $filePath;
        return;
    }
});

// Load config & environment
_log('Loading config...');
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
    _log('Config loaded. DB_HOST=' . getenv('DB_HOST'));
} else {
    _log('ERROR: config.php NOT FOUND at ' . ROOT_PATH . '/config/config.php');
}

// Load helpers
_log('Loading Session.php...');
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) {
    require_once ROOT_PATH . '/app/Helpers/Session.php';
    _log('Session loaded OK');
} else {
    _log('ERROR: Session.php NOT FOUND');
}

_log('Loading Helpers.php...');
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) {
    require_once ROOT_PATH . '/app/Helpers/Helpers.php';
    _log('Helpers loaded OK');
} else {
    _log('ERROR: Helpers.php NOT FOUND');
}

// Load routes
_log('Loading routes...');
if (file_exists(ROOT_PATH . '/routes/web.php')) {
    require_once ROOT_PATH . '/routes/web.php';
    _log('Routes loaded OK');
} else {
    _log('ERROR: routes/web.php NOT FOUND');
}

// Strip base path from URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/bestdealcrm';

if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$_SERVER['REQUEST_URI'] = $requestUri ?: '/';
_log('Final URI for router: ' . $_SERVER['REQUEST_URI']);

// Dispatch via Router
if (isset($router) && $router instanceof Router) {
    _log('Router found, dispatching...');
    $router->dispatch();
} else {
    _log('ERROR: Router not set! $router=' . var_export($router, true));
    http_response_code(500);
    echo 'Router not initialized. Check routes/web.php';
}
_log('--- REQUEST END ---');
