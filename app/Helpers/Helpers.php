<?php
/**
 * General Helper Functions
 */

/**
 * Generate a URL-friendly slug
 */
function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text);
}

/**
 * Format date for display
 */
function formatDate(string $date, string $format = 'd M Y, h:i A'): string
{
    if (empty($date) || $date === '0000-00-00 00:00:00' || $date === '0000-00-00') return '';
    try {
        $dt = new \DateTime($date, new \DateTimeZone('Asia/Kolkata'));
        return $dt->format($format);
    } catch (\Exception $e) {
        return $date;
    }
}

/**
 * Format currency
 */
function formatCurrency(float $amount, string $symbol = '₹'): string
{
    return $symbol . number_format($amount, 2);
}

/**
 * Generate a unique random string
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 100): string
{
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

/**
 * Log activity to audit log
 */
function logActivity(int $userId, string $action, string $entityType = '', ?int $entityId = null, ?string $oldValue = null, ?string $newValue = null): void
{
    try {
        Database::getInstance()->insert('activity_logs', [
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_value'   => $oldValue,
            'new_value'   => $newValue,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    } catch (\Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Create a notification
 */
function createNotification(int $userId, string $title, string $message, string $type = 'info', ?int $leadId = null): void
{
    try {
        Database::getInstance()->insert('notifications', [
            'user_id'        => $userId,
            'title'          => $title,
            'message'        => $message,
            'type'           => $type,
            'related_lead_id'=> $leadId,
            'is_read'        => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    } catch (\Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
    }
}

/**
 * Get unread notification count
 */
function unreadNotificationCount(): int
{
    $user = currentUser();
    if (!$user) return 0;
    
    return Database::getInstance()->count('notifications', 'user_id = ? AND is_read = 0', [$user['id']]);
}

/**
 * Get role ID by name
 */
function getRoleId(string $roleName): ?string
{
    $role = Database::getInstance()->fetchOne("SELECT id FROM roles WHERE name = ?", [$roleName]);
    return $role['id'] ?? null;
}

/**
 * Format status for display with badge class
 */
function statusBadge(string $status): string
{
    $badges = [
        'LEAD_UPLOADED'          => 'badge-secondary',
        'LEAD_ASSIGNED'          => 'badge-info',
        'AGENT_DRAFT'            => 'badge-warning',
        'AGENT_SUBMITTED'        => 'badge-primary',
        'ADMIN_REVIEW_1'         => 'badge-warning',
        'LOGIN_AGENT_ASSIGNED'   => 'badge-info',
        'LOGIN_AGENT_DRAFT'      => 'badge-warning',
        'LOGIN_AGENT_SUBMITTED'  => 'badge-primary',
        'RETURNED_TO_AGENT'      => 'badge-danger',
        'ADMIN_REVIEW_2'         => 'badge-warning',
        'LOGIN_APPROVED'         => 'badge-success',
        'POST_LOGIN'             => 'badge-info',
        'UNDERWRITING'           => 'badge-primary',
        'UNDERWRITING_APPROVED'  => 'badge-success',
        'UNDERWRITING_REJECTED'  => 'badge-danger',
        'DISPATCH'               => 'badge-info',
        'COMPLETED'              => 'badge-success',
        'REJECTED'               => 'badge-danger',
    ];
    
    $class = $badges[$status] ?? 'badge-secondary';
    $label = ucwords(str_replace('_', ' ', strtolower($status)));
    
    return '<span class="badge ' . $class . '">' . htmlspecialchars($label) . '</span>';
}

/**
 * Human-readable status name
 */
function humanStatus(string $status): string
{
    return ucwords(str_replace('_', ' ', strtolower($status)));
}

/**
 * Check if user has a specific permission
 */
function hasPermission(string $permission): bool
{
    $user = currentUser();
    if (!$user) return false;
    
    // Admin has all permissions
    if ($user['role_name'] === 'admin') return true;
    
    $result = Database::getInstance()->fetchOne(
        "SELECT 1 FROM role_permissions rp 
         JOIN permissions p ON rp.permission_id = p.id 
         WHERE rp.role_id = ? AND p.name = ?",
        [$user['role_id'], $permission]
    );
    
    return !empty($result);
}

/**
 * Get user's role name
 */
function userRole(): string
{
    return $_SESSION['role_name'] ?? '';
}
