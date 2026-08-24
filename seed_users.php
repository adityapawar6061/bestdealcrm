#!/usr/bin/env php
<?php
/**
 * Seed users for all roles
 * Run once: php seed_users.php
 */

// Minimal bootstrap
define('ROOT_PATH', __DIR__);

if (!function_exists('str_contains')) {
    function str_contains($h, $n) { return $n === '' || strpos($h, $n) !== false; }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with($h, $n) { return $n === '' || strpos($h, $n) === 0; }
}

spl_autoload_register(function ($class) {
    $map = array('Database' => ROOT_PATH . '/config/database.php', 'Router' => ROOT_PATH . '/config/Router.php');
    if (isset($map[$class])) { if (file_exists($map[$class])) { require_once $map[$class]; } return; }
    $parts = explode(chr(92), $class); if (count($parts) < 2) return;
    $fp = ROOT_PATH . '/app/' . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
    if (file_exists($fp)) { require_once $fp; }
});

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/app/Helpers/Session.php';

$db = \Database::getInstance()->getConnection();

if (!$db) {
    die("ERROR: Database connection failed.\n");
}

echo "Connected to database.\n";

// Default password for all seed users
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$users = [
    ['Super Admin', 'admin@bestdealcrm.com', 'admin', 'admin', 'active'],
    ['Rahul Sharma', 'rahul@bestdealcrm.com', 'rahul', 'agent', 'active'],
    ['Priya Patel', 'priya@bestdealcrm.com', 'priya', 'agent', 'active'],
    ['Amit Kumar', 'amit@bestdealcrm.com', 'amit', 'agent', 'active'],
    ['Sneha Reddy', 'sneha@bestdealcrm.com', 'sneha', 'login_agent', 'active'],
    ['Vikram Singh', 'vikram@bestdealcrm.com', 'vikram', 'login_agent', 'active'],
    ['Deepa Nair', 'deepa@bestdealcrm.com', 'deepa', 'team_leader', 'active'],
    ['Ravi Gupta', 'ravi@bestdealcrm.com', 'ravi', 'underwriting', 'active'],
    ['Kavita Joshi', 'kavita@bestdealcrm.com', 'kavita', 'dispatch', 'active'],
];

foreach ($users as [$name, $email, $username, $roleName, $status]) {
    // Get role ID
    $stmt = $db->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$roleName]);
    $role = $stmt->fetch();
    
    if (!$role) {
        echo "SKIP: Role '$roleName' not found for user '$username'\n";
        continue;
    }
    
    // Check if user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        echo "SKIP: User '$username' already exists\n";
        continue;
    }
    
    // Insert user
    $stmt = $db->prepare("INSERT INTO users (name, email, username, password_hash, role_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $email, $username, $hash, $role['id'], $status]);
    echo "CREATED: $username ($roleName) - ID: " . $db->lastInsertId() . "\n";
}

echo "\nDone! All users use password: admin123\n";
echo "Login at: https://bdfsloans.com/bestdealcrm/login\n";
