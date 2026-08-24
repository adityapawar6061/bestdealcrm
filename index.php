<?php
/**
 * BestDeal CRM - Front Controller
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/storage/logs/php_error.log');

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

define('ROOT_PATH', __DIR__);

// Autoloader
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

// Load components
if (file_exists(ROOT_PATH . '/config/config.php')) { require_once ROOT_PATH . '/config/config.php'; }
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) { require_once ROOT_PATH . '/app/Helpers/Session.php'; }
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) { require_once ROOT_PATH . '/app/Helpers/Helpers.php'; }
if (file_exists(ROOT_PATH . '/routes/web.php')) { require_once ROOT_PATH . '/routes/web.php'; }

// Strip base path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($requestUri, '/bestdealcrm') === 0) {
    $requestUri = substr($requestUri, strlen('/bestdealcrm'));
}
$_SERVER['REQUEST_URI'] = $requestUri ?: '/';

// Dispatch
if (isset($router) && $router instanceof Router) {
    $router->dispatch();
} else {
    http_response_code(500);
    echo 'Router not initialized';
}
