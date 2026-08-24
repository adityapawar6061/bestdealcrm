<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!function_exists('str_contains')) {
    function str_contains($h, $n) { return $n === '' || strpos($h, $n) !== false; }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($h, $n) { return $n === '' || strpos($h, $n) === 0; }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($h, $n) { return $n === '' || substr($h, -strlen($n)) === $n; }
}

define('ROOT_PATH', __DIR__);

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

if (file_exists(ROOT_PATH . '/config/config.php')) { require_once ROOT_PATH . '/config/config.php'; }
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) { require_once ROOT_PATH . '/app/Helpers/Session.php'; }
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) { require_once ROOT_PATH . '/app/Helpers/Helpers.php'; }
if (file_exists(ROOT_PATH . '/routes/web.php')) { require_once ROOT_PATH . '/routes/web.php'; }

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($requestUri, '/bestdealcrm') === 0) {
    $requestUri = substr($requestUri, strlen('/bestdealcrm'));
}
$_SERVER['REQUEST_URI'] = $requestUri ?: '/';

if (isset($router) && $router instanceof Router) {
    try {
        $router->dispatch();
    } catch (Throwable $e) {
        http_response_code(500);
        echo '<h1>Error</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
} else {
    http_response_code(500);
    echo 'Router not initialized';
}
