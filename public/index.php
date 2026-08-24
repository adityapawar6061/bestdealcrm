<?php
/**
 * BestDeal CRM - Front Controller
 * All requests routed through this file via .htaccess.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Polyfills for PHP 7.x (str_contains, str_starts_with, str_ends_with)
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
    }
}
if (!function_exists('enum_exists')) {
    // Prevent errors from enum references if any
}

$rootPath = dirname(__DIR__);
define('ROOT_PATH', $rootPath);

// Simple autoloader: no namespace escaping issues
spl_autoload_register(function ($class) {
    // Non-namespaced singletons
    if ($class === 'Database') {
        require_once ROOT_PATH . '/config/database.php';
        return;
    }
    if ($class === 'Router') {
        require_once ROOT_PATH . '/config/Router.php';
        return;
    }

    // Namespaced classes: Controllers\User, Models\Lead, etc.
    // Convert namespace separator to directory separator
    $file = ROOT_PATH . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }

    // Middleware
    $file = ROOT_PATH . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

// Config & Environment
require_once ROOT_PATH . '/config/config.php';

// Helpers (session, CSRF, utilities)
require_once ROOT_PATH . '/app/Helpers/Session.php';
require_once ROOT_PATH . '/app/Helpers/Helpers.php';

// Routes
require_once ROOT_PATH . '/routes/web.php';

// Strip base path and dispatch
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/bestdealcrm';

if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$_SERVER['REQUEST_URI'] = $requestUri ?: '/';

$router->dispatch();
