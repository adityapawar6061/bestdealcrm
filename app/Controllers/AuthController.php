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
            unset($_SESSION['flash']);
            $this->redirectBasedOnRole();
            return;
        }
        
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

        // Check DB connection
        if (!$this->db || !$this->db->getConnection()) {
            setFlash('error', 'Database connection failed. Please check configuration.');
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

            if (!$user) {
                $_SESSION['login_attempts'] = $attempts + 1;
                $_SESSION['last_login_attempt'] = time();
                setFlash('error', 'User not found. Check your username or email.');
                $this->redirect('/login');
                return;
            }

            if (!password_verify($password, $user['password_hash'])) {
                $_SESSION['login_attempts'] = $attempts + 1;
                $_SESSION['last_login_attempt'] = time();
                setFlash('error', 'Incorrect password. Please try again.');
                $this->redirect('/login');
                return;
            }

            if ($user['status'] !== 'active') {
                setFlash('error', 'Your account has been deactivated. Contact admin.');
                $this->redirect('/login');
                return;
            }

            // Successful login - clear attempts and flash
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_login_attempt']);
            unset($_SESSION['flash']);

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
            setFlash('error', 'Error: ' . $e->getMessage());
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
     * Change password (any authenticated user)
     */
    public function changePassword(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request.'], 405);
                return;
            }

            $user = currentUser();
            if (!$user) {
                $this->json(['error' => 'Not authenticated.'], 401);
                return;
            }

            $oldPassword = $_POST['old_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
                $this->json(['error' => 'All fields are required.'], 400);
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $this->json(['error' => 'New password and confirmation do not match.'], 400);
                return;
            }

            if (strlen($newPassword) < 6) {
                $this->json(['error' => 'New password must be at least 6 characters.'], 400);
                return;
            }

            // Verify old password
            $dbUser = $this->db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
            if (!$dbUser || !password_verify($oldPassword, $dbUser['password_hash'])) {
                $this->json(['error' => 'Current password is incorrect.'], 400);
                return;
            }

            $this->db->update('users', [
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'updated_at'    => date('Y-m-d H:i:s'),
            ], 'id = ?', [$user['id']]);

            logActivity($user['id'], 'password_changed', 'user', $user['id']);

            $this->json(['success' => true, 'message' => 'Password changed successfully.']);
        } catch (\Throwable $e) {
            error_log('changePassword error: ' . $e->getMessage());
            $this->json(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
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
