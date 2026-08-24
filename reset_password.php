<?php
/**
 * BestDeal CRM - Password Reset Tool
 * Access: https://bdfsloans.com/bestdealcrm/reset_password.php
 */

$dbHost = '68.178.237.250';
$dbName = 'bestdealcrm';
$dbUser = 'sayali';
$dbPass = 'sayali@1234';

$message = '';
$error = '';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'fix_admin') {
        // Create proper password hash for admin123
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        // Check if admin exists
        $check = $pdo->query("SELECT id, password_hash FROM users WHERE username='admin'")->fetch();
        
        if ($check) {
            // Update password
            $pdo->prepare("UPDATE users SET password_hash=? WHERE username='admin'")->execute([$hash]);
            $message = "Admin password updated to: admin123<br>Hash: " . htmlspecialchars($hash);
        } else {
            // Create admin user - first ensure admin role exists
            $roleId = $pdo->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
            if (!$roleId) {
                $pdo->exec("INSERT INTO roles (name, display_name, description) VALUES ('admin','Admin','Full access')");
                $roleId = $pdo->lastInsertId();
            }
            $pdo->prepare("INSERT INTO users (name, email, username, password_hash, role_id, status) VALUES (?, 'admin@bestdealcrm.com', 'admin', ?, ?, 'active')")
                ->execute(['Super Admin', $hash, $roleId]);
            $message = "Admin user created with password: admin123";
        }
    }
    
    if ($action === 'list_users') {
        $users = $pdo->query("SELECT id, name, username, email, role_id, status FROM users ORDER BY id")->fetchAll();
        $message = "<strong>Users in database:</strong><br>";
        if (empty($users)) {
            $message .= "NO USERS FOUND! Run install.php first.";
        } else {
            foreach ($users as $u) {
                $message .= "ID:{$u['id']} | {$u['name']} | {$u['username']} | {$u['email']} | Role:{$u['role_id']} | {$u['status']}<br>";
            }
        }
    }
    
    if ($action === 'fix_hash') {
        // Fix: generate hash and show it, then update
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash=? WHERE username='admin'")->execute([$hash]);
        
        // Verify it works
        $user = $pdo->query("SELECT password_hash FROM users WHERE username='admin'")->fetch();
        $verify = password_verify('admin123', $user['password_hash']);
        
        $message = "New hash: " . htmlspecialchars($user['password_hash']) . "<br>";
        $message .= "Verify with 'admin123': " . ($verify ? "✅ SUCCESS" : "❌ FAILED");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BestDeal CRM - Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
    <div class="card shadow" style="max-width:500px;width:100%">
        <div class="card-body p-5">
            <h4 class="text-center mb-4">Password Reset Tool</h4>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="d-grid gap-2">
                <button type="submit" name="action" value="list_users" class="btn btn-info">
                    1. List All Users (Check if admin exists)
                </button>
                <button type="submit" name="action" value="fix_admin" class="btn btn-warning">
                    2. Create/Reset Admin (password: admin123)
                </button>
                <button type="submit" name="action" value="fix_hash" class="btn btn-success">
                    3. Fix Password Hash & Verify
                </button>
            </form>
            
            <hr>
            <div class="text-center">
                <a href="/bestdealcrm/public/index.php" class="btn btn-primary">Go to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
