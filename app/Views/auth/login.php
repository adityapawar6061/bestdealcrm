<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BestDeal CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; max-width: 420px; width: 100%; }
        .login-header { background: linear-gradient(135deg, #3b82f6, #1d4ed8); padding: 40px 30px; text-align: center; color: #fff; }
        .login-header h2 { font-weight: 700; margin-bottom: 5px; }
        .login-header p { opacity: 0.8; font-size: 0.9rem; }
        .login-body { padding: 30px; }
        .form-control { border-radius: 8px; padding: 12px 15px; border: 1px solid #e2e8f0; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn-login { border-radius: 8px; padding: 12px; font-weight: 600; background: #3b82f6; border: none; }
        .btn-login:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2>BestDeal CRM</h2>
            <p>Loan Processing Management System</p>
        </div>
        <div class="login-body">
            <?php $error = getFlash('error'); $success = getFlash('success'); ?>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><small><?= htmlspecialchars($error) ?></small></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success py-2"><small><?= htmlspecialchars($success) ?></small></div>
            <?php endif; ?>
            
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <?php
                // Process login
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if ($username && $password) {
                    $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id=r.id WHERE (u.username=? OR u.email=?) AND u.status='active'");
                    $stmt->execute([$username, $username]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password_hash'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role_id'] = $user['role_id'];
                        $_SESSION['role_name'] = $user['role_name'];
                        
                        // Update last login
                        $pdo->prepare("UPDATE users SET last_login_at=NOW() WHERE id=?")->execute([$user['id']]);
                        
                        // Log login
                        $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)")
                            ->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '']);
                        
                        // Redirect based on role
                        $base = '/bestdealcrm';
                        $dash = [
                            'admin' => '/admin/dashboard',
                            'agent' => '/agent/dashboard',
                            'login_agent' => '/login-agent/dashboard',
                        ];
                        header('Location: ' . $base . ($dash[$user['role_name']] ?? '/admin/dashboard'));
                        exit;
                    } else {
                        setFlash('error', 'Invalid username or password.');
                        header('Location: /bestdealcrm/login');
                        exit;
                    }
                } else {
                    setFlash('error', 'Please enter username and password.');
                    header('Location: /bestdealcrm/login');
                    exit;
                }
                ?>
            <?php endif; ?>
            
            <form method="POST" action="/bestdealcrm/login">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label text-muted small">Username or Email</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-login btn-primary w-100">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
