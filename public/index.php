<?php
/**
 * BestDeal CRM - Public Entry Point
 * All requests are routed through this file
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', env('APP_DEBUG', false) ? '1' : '0');

// Load configuration
require_once ROOT_PATH . '/config/database.php';

// Load middleware
require_once APP_PATH . '/Middleware/AuthMiddleware.php';
require_once APP_PATH . '/Middleware/CsrfMiddleware.php';
require_once APP_PATH . '/Middleware/RoleMiddleware.php';

// Load base controller
require_once APP_PATH . '/Controllers/BaseController.php';

// Load models
$models = glob(APP_PATH . '/Models/*.php');
foreach ($models as $model) {
    require_once $model;
}

// Load services
$services = glob(APP_PATH . '/Services/*.php');
foreach ($services as $service) {
    require_once $service;
}

// Load helper functions
require_once APP_PATH . '/Helpers/Helpers.php';

// Load routes
require_once ROUTES_PATH . '/web.php';

// Dispatch request
$router->dispatch();
