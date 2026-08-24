<?php
/**
 * BestDeal CRM - Database Installer
 * Standalone installer for cPanel deployment
 */

// Load environment config if available
$rootPath = dirname(__DIR__);
$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim(trim($value), '"\'');
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'bestdealcrm';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        // Read SQL file
        $sqlFile = __DIR__ . '/migration.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("migration.sql file not found at: {$sqlFile}");
        }
        $sql = file_get_contents($sqlFile);
        
        // Execute SQL statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            // Skip empty, SET, and USE statements
            $upper = strtoupper($statement);
            if (empty($statement)) continue;
            if (strpos($upper, 'SET ') === 0) continue;
            if (strpos($upper, 'USE ') === 0) continue;
            
            try {
                $pdo->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                // Skip duplicate entry errors (idempotent) and table exists errors
                $code = $e->getCode();
                if ($code != '23000' && strpos($e->getMessage(), '1062') === false 
                    && strpos($e->getMessage(), '1050') === false
                    && strpos($e->getMessage(), '1061') === false
                    && strpos($e->getMessage(), '1091') === false) {
                    $errors[] = substr($e->getMessage(), 0, 200);
                }
            }
        }
        
        $message = "Installation complete! {$successCount} SQL statements executed successfully.";
        if (!empty($errors)) {
            $message .= " Warnings: " . implode('; ', array_slice($errors, 0, 3));
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
                        <li>Ensure the database "bestdealcrm" exists</li>
                        <li>Check database credentials below</li>
                    </ul>
                </div>
                <div class="mb-3">
                    <strong>Database:</strong> <?= htmlspecialchars($dbName) ?> @ <?= htmlspecialchars($dbHost) ?>
                    <br><small class="text-muted">Credentials loaded from .env file</small>
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
