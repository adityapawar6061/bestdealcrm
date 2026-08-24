<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BestDeal CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            padding: 40px 30px;
            text-align: center;
            color: #fff;
        }
        .login-header h2 { font-weight: 700; margin-bottom: 5px; font-size: 1.5rem; }
        .login-header p { opacity: 0.8; font-size: 0.9rem; margin: 0; }
        .login-body { padding: 30px; }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .btn-login {
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            background: #3b82f6;
            border: none;
            font-size: 1rem;
        }
        .btn-login:hover { background: #2563eb; }
        .form-label { font-weight: 500; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2>BestDeal CRM</h2>
            <p>Loan Processing Management System</p>
        </div>
        <div class="login-body">
            <?php 
            // Flash messages
            $error = getFlash('error'); 
            $success = getFlash('success'); 
            ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 mb-3">
                    <small><?= htmlspecialchars($error) ?></small>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success py-2 mb-3">
                    <small><?= htmlspecialchars($success) ?></small>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= defined('BASE_URL') ? BASE_URL : '' ?>/login">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label text-muted small">Username or Email</label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Enter username or email" required autofocus
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small">Password</label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-login btn-primary w-100">
                    Sign In
                </button>
            </form>
            
            <div class="text-center mt-3">
                <small class="text-muted">Default: admin / admin123</small>
            </div>
        </div>
    </div>
</body>
</html>
