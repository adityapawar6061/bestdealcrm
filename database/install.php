<?php
/**
 * BestDeal CRM - Database Installer
 * Run this file once to initialize the database
 * Access: http://localhost/bestdealcrm/database/install.php
 */

require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        
        // Read SQL file
        $sqlFile = __DIR__ . '/migration.sql';
        $sql = file_get_contents($sqlFile);
        
        // Execute SQL statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (!empty($statement) && $statement !== 'SET FOREIGN_KEY_CHECKS = 0' && $statement !== 'SET FOREIGN_KEY_CHECKS = 1') {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (PDOException $e) {
                    // Skip duplicate entry errors (idempotent)
                    if (strpos($e->getMessage(), '1062') === false) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
        }
        
        $message = "Installation complete! {$successCount} SQL statements executed successfully.";
        if (!empty($errors)) {
            $message .= " Some warnings: " . implode('; ', array_slice($errors, 0, 5));
        }
        
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BestDeal CRM - Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="card shadow" style="max-width:500px;width:100%">
        <div class="card-body p-5">
            <h3 class="text-center mb-4">BestDeal CRM</h3>
            <p class="text-muted text-center">Database Installer</p>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <div class="text-center">
                    <a href="/bestdealcrm/login" class="btn btn-primary">Go to Login</a>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <div class="text-center">
                    <a href="/bestdealcrm/database/install.php" class="btn btn-outline-primary">Try Again</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <strong>Before installing:</strong>
                    <ul class="mb-0 mt-1 small">
                        <li>Ensure MySQL is running</li>
                        <li>Database "bestdealcrm" must exist</li>
                        <li>Check credentials in config/database.php</li>
                    </ul>
                </div>
                <div class="mb-3">
                    <strong>Database:</strong> <?= env('DB_NAME', 'bestdealcrm') ?> @ <?= env('DB_HOST', 'localhost') ?>
                </div>
                <div class="mb-3">
                    <strong>Default Admin Login:</strong>
                    <br><small class="text-muted">Username: <code>admin</code> | Password: <code>admin123</code></small>
                </div>
                <form method="POST">
                    <button type="submit" class="btn btn-success w-100" onclick="return confirm('Install database tables and seed data?')">
                        <i class="bi bi-database me-1"></i> Install Database
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
