<?php
namespace Controllers;

class AuthController extends BaseController
{
    /**
     * Show login form
     */
    public function showLogin(): void
    {
        if (isAuthenticated()) {
            $this->redirectBasedOnRole();
        }
        
        $error = getFlash('error');
        $success = getFlash('success');
        
        require VIEWS_PATH . '/auth/login.php';
    }

    /**
     * Process login
     */
    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            setFlash('error', 'Please enter username and password.');
            $this->redirect('/login');
            return;
        }

        // Rate limiting check (simple session-based)
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_login_attempt'] ?? 0;
        
        if ($attempts >= 5 && (time() - $lastAttempt) < 300) {
            setFlash('error', 'Too many login attempts. Please wait 5 minutes.');
            $this->redirect('/login');
            return;
        }

        try {
            // Check by username or email
            $user = $this->db->fetchOne(
                "SELECT u.*, r.name as role_name 
                 FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE (u.username = ? OR u.email = ?) AND u.status = 'active'",
                [$username, $username]
            );

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $_SESSION['login_attempts'] = $attempts + 1;
                $_SESSION['last_login_attempt'] = time();
                
                setFlash('error', 'Invalid credentials.');
                $this->redirect('/login');
                return;
            }

            // Successful login - clear attempts
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_login_attempt']);

            // Regenerate session ID for security
            session_regenerate_id(true);

            // Set session data
            setAuthUser($user, $user['role_name']);

            // Update last login
            $this->db->update('users', [
                'last_login_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$user['id']]);

            // Log the login
            logActivity($user['id'], 'login', 'user', $user['id']);

            // Log in login_logs
            $this->db->insert('login_logs', [
                'user_id'    => $user['id'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'login_at'   => date('Y-m-d H:i:s'),
            ]);

            $this->redirectBasedOnRole();

        } catch (\Exception $e) {
            error_log("Login error: " . $e->getMessage());
            setFlash('error', 'An error occurred. Please try again.');
            $this->redirect('/login');
        }
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            logActivity($userId, 'logout', 'user', $userId);
        }

        clearAuthSession();
        $this->redirect('/login');
    }

    /**
     * Redirect user based on their role
     */
    private function redirectBasedOnRole(): void
    {
        $user = currentUser();
        if (!$user) {
            $this->redirect('/login');
            return;
        }

        $roleDashboards = [
            'admin'          => '/admin/dashboard',
            'team_leader'    => '/team-leader/dashboard',
            'agent'          => '/agent/dashboard',
            'login_agent'    => '/login-agent/dashboard',
            'underwriting'   => '/underwriting/dashboard',
            'dispatch'       => '/dispatch/dashboard',
        ];

        $dashboard = $roleDashboards[$user['role_name']] ?? '/dashboard';
        $this->redirect($dashboard);
    }
}
