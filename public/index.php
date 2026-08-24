<?php
/**
 * BestDeal CRM - Public Entry Point
 * All requests are routed through this file
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Define root path (one level up from public)
$rootPath = dirname(__DIR__);

// Simple config - no .env dependency
$dbHost = '68.178.237.250';
$dbName = 'bestdealcrm';
$dbUser = 'sayali';
$dbPass = 'sayali@1234';

// Database connection
try {
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    die("Database connection failed. Please check configuration.");
}

// Session
session_start();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper functions
function csrfToken() { return $_SESSION['csrf_token'] ?? ''; }
function csrfField() { return '<input type="hidden" name="_csrf_token" value="' . csrfToken() . '">'; }
function verifyCsrf() {
    $token = $_POST['_csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
function isAuthenticated() { return isset($_SESSION['user_id']); }
function currentUser() {
    if (!isAuthenticated()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role_name' => $_SESSION['role_name'] ?? '',
    ];
}
function setFlash($type, $msg) { $_SESSION['flash'][$type] = $msg; }
function getFlash($type) {
    $msg = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $msg;
}
function formatDate($date, $fmt = 'd M Y, h:i A') {
    if (!$date) return '';
    return (new DateTime($date))->format($fmt);
}
function statusBadge($status) {
    $map = [
        'LEAD_UPLOADED'=>'secondary','LEAD_ASSIGNED'=>'info','AGENT_DRAFT'=>'warning',
        'AGENT_SUBMITTED'=>'primary','ADMIN_REVIEW_1'=>'warning','LOGIN_AGENT_ASSIGNED'=>'info',
        'LOGIN_AGENT_DRAFT'=>'warning','ADMIN_REVIEW_2'=>'warning','LOGIN_APPROVED'=>'success',
        'POST_LOGIN'=>'info','UNDERWRITING'=>'primary','DISPATCH'=>'info',
        'COMPLETED'=>'success','REJECTED'=>'danger',
    ];
    $c = $map[$status] ?? 'secondary';
    $l = ucwords(str_replace('_', ' ', strtolower($status)));
    return '<span class="badge bg-' . $c . '">' . $l . '</span>';
}
function humanStatus($s) { return ucwords(str_replace('_', ' ', strtolower($s))); }

// Get request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/bestdealcrm';
if (strpos($requestUri, $base) === 0) {
    $path = substr($requestUri, strlen($base));
} else {
    $path = $requestUri;
}
$path = rtrim($path, '/') ?: '/';

// ========== ROUTES ==========

// Login page
if ($path === '/login' || $path === '/') {
    if (isAuthenticated()) {
        $role = $_SESSION['role_name'] ?? '';
        $dash = [
            'admin'=>'/admin/dashboard','agent'=>'/agent/dashboard',
            'login_agent'=>'/login-agent/dashboard',
        ];
        header('Location: ' . $base . ($dash[$role] ?? '/admin/dashboard'));
        exit;
    }
    require __DIR__ . '/../app/Views/auth/login.php';
    exit;
}

// Process login
if ($path === '/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handled by form POST to /login - see below
}

// Logout
if ($path === '/logout') {
    session_destroy();
    header('Location: ' . $base . '/login');
    exit;
}

// Admin routes
if (strpos($path, '/admin') === 0) {
    if (!isAuthenticated()) {
        header('Location: ' . $base . '/login');
        exit;
    }
    
    // Admin dashboard
    if ($path === '/admin/dashboard') {
        $user = currentUser();
        $stats = [
            'total_leads' => $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
            'unassigned' => $pdo->query("SELECT COUNT(*) FROM leads WHERE assigned_to IS NULL")->fetchColumn(),
            'assigned' => $pdo->query("SELECT COUNT(*) FROM leads WHERE assigned_to IS NOT NULL")->fetchColumn(),
            'agent_draft' => $pdo->query("SELECT COUNT(*) FROM leads WHERE workflow_stage='AGENT_DRAFT'")->fetchColumn(),
            'pending_review_1' => $pdo->query("SELECT COUNT(*) FROM leads WHERE workflow_stage='ADMIN_REVIEW_1'")->fetchColumn(),
            'login_pending' => $pdo->query("SELECT COUNT(*) FROM leads WHERE workflow_stage='LOGIN_AGENT_ASSIGNED'")->fetchColumn(),
            'approved' => $pdo->query("SELECT COUNT(*) FROM leads WHERE workflow_stage='LOGIN_APPROVED'")->fetchColumn(),
            'rejected' => $pdo->query("SELECT COUNT(*) FROM leads WHERE workflow_stage='REJECTED'")->fetchColumn(),
            'completed' => $pdo->query("SELECT COUNT(*) FROM leads WHERE workflow_stage='COMPLETED'")->fetchColumn(),
        ];
        $recentLeads = $pdo->query("SELECT l.*, u.name as assigned_to_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id ORDER BY l.created_at DESC LIMIT 10")->fetchAll();
        require __DIR__ . '/../app/Views/admin/dashboard.php';
        exit;
    }
    
    // Admin users
    if ($path === '/admin/users') {
        $users = $pdo->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id=r.id ORDER BY u.created_at DESC")->fetchAll();
        $roles = $pdo->query("SELECT * FROM roles ORDER BY name")->fetchAll();
        require __DIR__ . '/../app/Views/admin/users.php';
        exit;
    }
    
    // Admin leads
    if ($path === '/admin/leads') {
        $leads = $pdo->query("SELECT l.*, u.name as assigned_to_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id ORDER BY l.created_at DESC LIMIT 100")->fetchAll();
        $agents = $pdo->query("SELECT u.id, u.name FROM users u JOIN roles r ON u.role_id=r.id WHERE r.name='agent' AND u.status='active' ORDER BY u.name")->fetchAll();
        require __DIR__ . '/../app/Views/admin/leads.php';
        exit;
    }
    
    // Default admin page
    header('Location: ' . $base . '/admin/dashboard');
    exit;
}

// Agent routes
if (strpos($path, '/agent') === 0) {
    if (!isAuthenticated()) {
        header('Location: ' . $base . '/login');
        exit;
    }
    
    if ($path === '/agent/dashboard') {
        $user = currentUser();
        $stats = [
            'my_leads' => $pdo->query("SELECT COUNT(*) FROM leads WHERE assigned_to=" . $user['id'])->fetchColumn(),
            'drafts' => $pdo->query("SELECT COUNT(*) FROM leads WHERE assigned_to=" . $user['id'] . " AND workflow_stage='AGENT_DRAFT'")->fetchColumn(),
            'submitted' => $pdo->query("SELECT COUNT(*) FROM leads WHERE assigned_to=" . $user['id'] . " AND workflow_stage='ADMIN_REVIEW_1'")->fetchColumn(),
            'returned' => $pdo->query("SELECT COUNT(*) FROM leads WHERE assigned_to=" . $user['id'] . " AND workflow_stage='RETURNED_TO_AGENT'")->fetchColumn(),
        ];
        $recentLeads = $pdo->query("SELECT l.*, u.name as assigned_to_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id WHERE l.assigned_to=" . $user['id'] . " ORDER BY l.created_at DESC LIMIT 10")->fetchAll();
        require __DIR__ . '/../app/Views/agent/dashboard.php';
        exit;
    }
    
    if ($path === '/agent/leads') {
        $user = currentUser();
        $leads = $pdo->query("SELECT l.*, u.name as assigned_to_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id WHERE l.assigned_to=" . $user['id'] . " ORDER BY l.created_at DESC")->fetchAll();
        require __DIR__ . '/../app/Views/agent/leads.php';
        exit;
    }
    
    header('Location: ' . $base . '/agent/dashboard');
    exit;
}

// Login Agent routes
if (strpos($path, '/login-agent') === 0) {
    if (!isAuthenticated()) {
        header('Location: ' . $base . '/login');
        exit;
    }
    
    if ($path === '/login-agent/dashboard') {
        $user = currentUser();
        $stats = [
            'assigned_cases' => $pdo->query("SELECT COUNT(*) FROM leads WHERE assigned_to=" . $user['id'] . " AND workflow_stage='LOGIN_AGENT_ASSIGNED'")->fetchColumn(),
            'drafts' => $pdo->query("SELECT COUNT(*) FROM leads WHERE assigned_to=" . $user['id'] . " AND workflow_stage='LOGIN_AGENT_DRAFT'")->fetchColumn(),
            'approved' => $pdo->query("SELECT COUNT(*) FROM leads WHERE assigned_to=" . $user['id'] . " AND workflow_stage='LOGIN_APPROVED'")->fetchColumn(),
        ];
        $recentLeads = $pdo->query("SELECT l.*, u.name as assigned_to_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id WHERE l.assigned_to=" . $user['id'] . " ORDER BY l.created_at DESC LIMIT 10")->fetchAll();
        require __DIR__ . '/../app/Views/login_agent/dashboard.php';
        exit;
    }
    
    if ($path === '/login-agent/cases') {
        $user = currentUser();
        $leads = $pdo->query("SELECT l.*, u.name as assigned_to_name FROM leads l LEFT JOIN users u ON l.assigned_to=u.id WHERE l.assigned_to=" . $user['id'] . " ORDER BY l.created_at DESC")->fetchAll();
        require __DIR__ . '/../app/Views/login_agent/cases.php';
        exit;
    }
    
    header('Location: ' . $base . '/login-agent/dashboard');
    exit;
}

// Default - redirect to login
header('Location: ' . $base . '/login');
exit;
