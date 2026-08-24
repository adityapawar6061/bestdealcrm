<?php
/**
 * BestDeal CRM - Front Controller (Public Entry Point)
 * 
 * ALL requests are routed through this file via .htaccess.
 * This file bootstraps the MVC application:
 *   1. Load config and environment
 *   2. Set up autoloader
 *   3. Load helpers (session, CSRF, utilities)
 *   4. Load routes
 *   5. Dispatch request via Router
 */

// Error reporting (controlled by environment)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Define root path (one level up from public/)
$rootPath = dirname(__DIR__);
define('ROOT_PATH', $rootPath);

// ============================================================
// 1. AUTLOADER
// ============================================================
spl_autoload_register(function (string $class): void {
    // Handle non-namespaced classes (Helpers, Session, Database, Router)
    $simpleClasses = [
        'Database' => ROOT_PATH . '/config/database.php',
        'Router'   => ROOT_PATH . '/config/Router.php',
    ];
    
    if (isset($simpleClasses[$class])) {
        require_once $simpleClasses[$class];
        return;
    }
    
    // PSR-4 style: Controllers, Models, Middleware, Services
    // e.g. Controllers\AdminController -> app/Controllers/AdminController.php
    $prefixes = [
        'Controllers\\'  => ROOT_PATH . '/app/Controllers/',
        'Models\\'       => ROOT_PATH . '/app/Models/',
        'Middleware\\'    => ROOT_PATH . '/app/Middleware/',
        'Services\\'     => ROOT_PATH . '/app/Services/',
    ];
    
    foreach ($prefixes as $prefix => $baseDir) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// ============================================================
// 2. CONFIG & ENVIRONMENT
// ============================================================
require_once ROOT_PATH . '/config/config.php';

// ============================================================
// 3. HELPER FILES (session, CSRF, utilities)
// ============================================================
// Session.php handles session_start(), CSRF, flash messages
// It defines: isAuthenticated(), currentUser(), setAuthUser(), etc.
require_once ROOT_PATH . '/app/Helpers/Session.php';
// Helpers.php defines: logActivity(), createNotification(), hasPermission(), etc.
require_once ROOT_PATH . '/app/Helpers/Helpers.php';

// ============================================================
// 4. ROUTES
// ============================================================
require_once ROOT_PATH . '/routes/web.php';

// ============================================================
// 5. STRIP BASE PATH AND DISPATCH
// ============================================================
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/bestdealcrm';

// Strip the application base path
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Set the stripped path on the server for the Router to use
// Router uses REQUEST_URI to match routes, so we override it
$_SERVER['REQUEST_URI'] = $requestUri ?: '/';

// Dispatch the request
$router->dispatch();
