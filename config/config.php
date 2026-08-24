<?php
/**
 * Environment Configuration Loader
 * Parses .env file and populates $_ENV and getenv()
 */

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        // .env may not exist on server - fall back to defaults in database.php
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Skip empty lines
        if (empty(trim($line))) continue;
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove surrounding quotes
            if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
                $value = substr($value, 1, -1);
            } elseif (strlen($value) >= 2 && $value[0] === "'" && $value[strlen($value) - 1] === "'") {
                $value = substr($value, 1, -1);
            }
            
            // Convert boolean strings
            if (strtolower($value) === 'true') $value = true;
            if (strtolower($value) === 'false') $value = false;
            
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Get environment variable with optional default
 */
function env(string $key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}

// Load the .env file
$rootPath = dirname(__DIR__);
loadEnv($rootPath . '/.env');

// Define base paths (ROOT_PATH may already be defined by index.php)
if (!defined('ROOT_PATH')) define('ROOT_PATH', $rootPath);
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('ROUTES_PATH', ROOT_PATH . '/routes');
define('VIEWS_PATH', APP_PATH . '/Views');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_PATH', ROOT_PATH . '/' . env('UPLOAD_PATH', 'public/uploads'));
