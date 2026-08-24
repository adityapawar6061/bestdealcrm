<?php
/**
 * Session Management Helper
 * Handles session initialization, CSRF, flash messages
 */

session_start();

// Regenerate session ID periodically for security
if (!isset($_SESSION['_last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['_last_regeneration'] = time();
} elseif (time() - $_SESSION['_last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_last_regeneration'] = time();
}

/**
 * Check if user is authenticated
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user data from session
 */
function currentUser(): ?array
{
    if (!isAuthenticated()) return null;
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'name'      => $_SESSION['user_name'] ?? '',
        'email'     => $_SESSION['user_email'] ?? '',
        'role_id'   => $_SESSION['role_id'] ?? null,
        'role_name' => $_SESSION['role_name'] ?? '',
        'username'  => $_SESSION['username'] ?? '',
    ];
}

/**
 * Set user data in session after login
 */
function setAuthUser(array $user, string $roleName): void
{
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['role_id']    = $user['role_id'];
    $_SESSION['role_name']  = $roleName;
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Clear session / logout
 */
function clearAuthSession(): void
{
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Generate CSRF token
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate hidden CSRF input field
 */
function csrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . csrfToken() . '">';
}

/**
 * Verify CSRF token
 */
function verifyCsrf(): bool
{
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get and clear flash message
 */
function getFlash(string $type): ?string
{
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

/**
 * Check if any flash messages exist
 */
function hasFlash(): bool
{
    return !empty($_SESSION['flash']);
}

/**
 * Get all flash messages
 */
function getAllFlash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}
