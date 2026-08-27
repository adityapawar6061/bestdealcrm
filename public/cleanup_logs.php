<?php
/**
 * One-time cleanup: Delete all old activity logs and notifications
 * DELETE THIS FILE AFTER USE
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$rootPath = dirname(__DIR__);
if (!file_exists($rootPath . '/.env') && !file_exists($rootPath . '/config/config.php')) {
    $rootPath = dirname($rootPath);
}

date_default_timezone_set('Asia/Kolkata');

spl_autoload_register(function ($class) {
    $map = ['Database' => ROOT_PATH . '/config/database.php', 'Router' => ROOT_PATH . '/config/Router.php'];
    if (isset($map[$class])) { require_once $map[$class]; return; }
    $parts = explode(chr(92), $class);
    if (count($parts) < 2) return;
    $filePath = ROOT_PATH . '/app/' . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
    if (file_exists($filePath)) require_once $filePath;
});

if (file_exists($rootPath . '/config/config.php')) require_once $rootPath . '/config/config.php';
if (file_exists($rootPath . '/app/Helpers/Session.php')) require_once $rootPath . '/app/Helpers/Session.php';
if (file_exists($rootPath . '/app/Helpers/Helpers.php')) require_once $rootPath . '/app/Helpers/Helpers.php';

$db = Database::getInstance();

// Check if already ran
if (isset($_GET['action']) && $_GET['action'] === 'cleanup') {
    $results = [];
    
    // Delete all activity_logs
    $db->query("TRUNCATE TABLE activity_logs");
    $results[] = "✅ activity_logs cleared (TRUNCATE)";
    
    // Delete all notifications  
    $db->query("TRUNCATE TABLE notifications");
    $results[] = "✅ notifications cleared (TRUNCATE)";
    
    // Delete all workflow history
    $db->query("TRUNCATE TABLE workflow_history");
    $results[] = "✅ workflow_history cleared (TRUNCATE)";
    
    // Delete all remarks
    $db->query("TRUNCATE TABLE remarks");
    $results[] = "✅ remarks cleared (TRUNCATE)";
    
    echo "<h2>Cleanup Complete!</h2>";
    echo "<ul>";
    foreach ($results as $r) echo "<li>$r</li>";
    echo "</ul>";
    echo "<p><strong>Now perform any action (login, logout, etc.) and check the activity logs — times should be correct IST.</strong></p>";
    echo "<p><a href='/bestdealcrm/admin/activity-logs'>→ Go to Activity Logs</a></p>";
    echo "<p><a href='/bestdealcrm/admin/dashboard'>→ Go to Dashboard</a></p>";
    echo "<hr>";
    echo "<p style='color:red'><strong>⚠️ DELETE THIS FILE: public/cleanup_logs.php</strong></p>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Cleanup Old Data</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-danger text-white">
            <h4 class="mb-0">⚠️ Delete Old Activity Data</h4>
        </div>
        <div class="card-body">
            <p>This will <strong>delete ALL</strong> from these tables:</p>
            <ul>
                <li>Activity Logs</li>
                <li>Notifications</li>
                <li>Workflow History</li>
                <li>Remarks</li>
            </ul>
            <p class="text-muted">Old entries had wrong timezone. After cleanup, new entries will show correct IST time.</p>
            <p><strong>Your lead data, forms, users, and submissions will NOT be affected.</strong></p>
            <a href="?action=cleanup" class="btn btn-danger btn-lg" onclick="return confirm('Delete ALL old activity data? This cannot be undone.')">
                🗑️ Delete All Old Data
            </a>
            <hr>
            <p style='color:red'><strong>⚠️ DELETE THIS FILE after use: public/cleanup_logs.php</strong></p>
        </div>
    </div>
</div>
</body>
</html>
