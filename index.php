#!/usr/bin/env php
<?php
/**
 * BestDeal CRM - Root Front Controller
 * All requests routed through this file.
 * 
 * This file is at the project ROOT level where PHP execution works.
 * It bootstraps the entire MVC application.
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

// This file IS the project root
define('ROOT_PATH', __DIR__);

// Step-by-step logging
function _log($msg) {
    @file_put_contents(
        ROOT_PATH . '/storage/logs/app.log',
        date('Y-m-d H:i:s') . ' | ' . $msg . "\n",
        FILE_APPEND
    );
}
_log('--- REQUEST START ---');
_log('REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
_log('ROOT_PATH: ' . ROOT_PATH);

// Autoloader - zero backslash escaping issues
spl_autoload_register(function ($class) {
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

    // Namespaced: Controllers\AuthController => app/Controllers/AuthController.php
    $parts = explode(chr(92), $class);
    if (count($parts) < 2) return;

    $relativePath = implode(DIRECTORY_SEPARATOR, $parts);
    $filePath = ROOT_PATH . '/app/' . $relativePath . '.php';

    if (file_exists($filePath)) {
        require_once $filePath;
        return;
    }
});

// Shutdown error handler
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

// Load config
_log('Loading config...');
if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
    _log('Config loaded');
} else {
    _log('ERROR: config.php NOT FOUND');
}

// Load helpers
_log('Loading Session...');
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) {
    require_once ROOT_PATH . '/app/Helpers/Session.php';
    _log('Session loaded');
}

_log('Loading Helpers...');
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) {
    require_once ROOT_PATH . '/app/Helpers/Helpers.php';
    _log('Helpers loaded');
}

// Load routes
_log('Loading routes...');
if (file_exists(ROOT_PATH . '/routes/web.php')) {
    require_once ROOT_PATH . '/routes/web.php';
    _log('Routes loaded');
}

// Strip base path from URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/bestdealcrm';

if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

$_SERVER['REQUEST_URI'] = $requestUri ?: '/';
_log('Final URI: ' . $_SERVER['REQUEST_URI']);

// Dispatch
if (isset($router) && $router instanceof Router) {
    _log('Dispatching...');
    $router->dispatch();
} else {
    _log('ERROR: Router not initialized');
    http_response_code(500);
    echo 'Router not initialized. Check routes/web.php';
}

_log('--- REQUEST END ---');
