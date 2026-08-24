<?php
namespace Middleware;

class RoleMiddleware
{
    /**
     * Check if the current user has one of the allowed roles
     * Usage in routes: middleware(['Role:admin,team_leader'], callback)
     */
    public static function check(string $allowedRoles): bool
    {
        $user = currentUser();
        if (!$user) {
            return false;
        }
        
        $roles = array_map('trim', explode(',', $allowedRoles));
        return in_array($user['role_name'], $roles);
    }

    /**
     * Require specific roles, redirect if unauthorized
     */
    public static function requireRole(string ...$roles): void
    {
        $user = currentUser();
        if (!$user || !in_array($user['role_name'], $roles)) {
            setFlash('error', 'You do not have permission to access this page.');
            header('Location: /dashboard');
            exit;
        }
    }
}
