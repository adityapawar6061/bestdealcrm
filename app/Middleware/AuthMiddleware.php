<?php
namespace Middleware;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!isAuthenticated()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthenticated', 'redirect' => '/login']);
                exit;
            }
            $loginUrl = defined('BASE_URL') ? BASE_URL . '/login' : '/login';
            header('Location: ' . $loginUrl);
            exit;
        }

        // IP Restriction check for non-admin users
        $this->checkIpRestriction();

        return true;
    }

    /**
     * Check if the current user is IP-restricted and whether their IP is whitelisted.
     * Admin users are always exempt.
     */
    private function checkIpRestriction(): void
    {
        $user = currentUser();
        if (!$user) return;

        // Admin is always exempt
        if (($user['role_name'] ?? '') === 'admin') return;

        try {
            $db = \Database::getInstance();

            // Check if ip_restricted column exists (graceful for old DBs)
            $colCheck = $db->fetchOne("SHOW COLUMNS FROM users LIKE 'ip_restricted'");
            if (!$colCheck) return;

            $userData = $db->fetchOne(
                "SELECT ip_restricted FROM users WHERE id = ?",
                [$user['id']]
            );

            if (!$userData || !$userData['ip_restricted']) return;

            // Check if ip_whitelist table exists
            $tableCheck = $db->fetchOne("SHOW TABLES LIKE 'ip_whitelist'");
            if (!$tableCheck) return;

            // If whitelist is empty, don't block anyone
            $ipCount = $db->count('ip_whitelist', 'is_active = 1');
            if ($ipCount === 0) return;

            // Get current IP
            $currentIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            if (strpos($currentIp, ',') !== false) {
                $currentIp = trim(explode(',', $currentIp)[0]);
            }

            // Check if IP is whitelisted
            $allowed = $db->fetchOne(
                "SELECT id FROM ip_whitelist WHERE ip_address = ? AND is_active = 1",
                [$currentIp]
            );

            if (!$allowed) {
                // IP not whitelisted — block access
                clearAuthSession();
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    http_response_code(403);
                    echo json_encode(['error' => 'Access denied. Your IP (' . htmlspecialchars($currentIp) . ') is not authorized. Contact admin.', 'redirect' => '/login']);
                    exit;
                }
                setFlash('error', 'Access denied. Your IP (' . $currentIp . ') is not authorized. Please contact your administrator.');
                $loginUrl = defined('BASE_URL') ? BASE_URL . '/login' : '/login';
                header('Location: ' . $loginUrl);
                exit;
            }
        } catch (\Exception $e) {
            // Don't block on errors — fail open for availability
            error_log('IP restriction check error: ' . $e->getMessage());
        }
    }
}
