<?php
/**
 * BestDeal CRM - Front Controller
 * All requests routed through this file via .htaccess.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_error.log');

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

// Shutdown error handler to capture fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        @file_put_contents(
            ROOT_PATH . '/storage/logs/shutdown_error.log',
            date('Y-m-d H:i:s') . ' | ' . $error['message'] . ' | ' . $error['file'] . ':' . $error['line'] . "\n",
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
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
}

// Load helpers
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) {
    require_once ROOT_PATH . '/app/Helpers/Session.php';
}
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) {
    require_once ROOT_PATH . '/app/Helpers/Helpers.php';
}

// Load routes
if (file_exists(ROOT_PATH . '/routes/web.php')) {
    require_once ROOT_PATH . '/routes/web.php';
}

// Strip base path from URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/bestdealcrm';

if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$_SERVER['REQUEST_URI'] = $requestUri ?: '/';

// Dispatch via Router
if (isset($router) && $router instanceof Router) {
    $router->dispatch();
} else {
    // Fallback: show error
    http_response_code(500);
    echo 'Router not initialized. Check routes/web.php';
}
