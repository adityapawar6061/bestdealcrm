<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'BestDeal CRM') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/bestdealcrm/public/assets/css/app.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed: 60px;
            --sidebar-bg: #1e293b;
            --sidebar-active: #3b82f6;
            --topbar-height: 60px;
        }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; overflow-x: hidden; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-width);
            background: var(--sidebar-bg); color: #fff; overflow-y: auto; overflow-x: hidden;
            z-index: 1000; transition: width 0.3s ease;
        }
        .sidebar .brand {
            padding: 18px 16px; font-size: 1.1rem; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1);
            white-space: nowrap; display: flex; align-items: center; gap: 10px;
            transition: padding 0.3s ease;
        }
        .sidebar .brand .brand-text { transition: opacity 0.2s ease; }
        .sidebar .nav-link {
            color: #94a3b8; padding: 10px 16px; display: flex; align-items: center; gap: 10px;
            text-decoration: none; font-size: 0.85rem; transition: all 0.2s;
            white-space: nowrap; overflow: hidden;
        }
        .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .sidebar .nav-link.active { color: #fff; background: var(--sidebar-active); border-radius: 0 25px 25px 0; margin-right: 10px; }
        .sidebar .nav-link i { min-width: 20px; text-align: center; font-size: 1rem; flex-shrink: 0; }
        .sidebar .nav-link .nav-text { transition: opacity 0.2s ease; }
        .sidebar .nav-section {
            padding: 14px 16px 5px; font-size: 0.7rem; text-transform: uppercase;
            color: #64748b; letter-spacing: 1px;
            white-space: nowrap; overflow: hidden; transition: opacity 0.2s ease;
        }

        /* ===== COLLAPSED STATE ===== */
        body.sidebar-collapsed .sidebar { width: var(--sidebar-collapsed); }
        body.sidebar-collapsed .sidebar .brand { padding: 18px 0; justify-content: center; }
        body.sidebar-collapsed .sidebar .brand .brand-text,
        body.sidebar-collapsed .sidebar .nav-link .nav-text,
        body.sidebar-collapsed .sidebar .nav-section { opacity: 0; width: 0; overflow: hidden; padding: 0; margin: 0; height: 0; }
        body.sidebar-collapsed .sidebar .nav-link { justify-content: center; padding: 10px 0; }
        body.sidebar-collapsed .sidebar .nav-section { display: none; }
        body.sidebar-collapsed .main-content { margin-left: var(--sidebar-collapsed); }

        /* ===== HOVER EXPAND ===== */
        body.sidebar-collapsed .sidebar:hover,
        body.sidebar-hovered .sidebar { width: var(--sidebar-width); }
        body.sidebar-collapsed .sidebar:hover .brand,
        body.sidebar-hovered .sidebar .brand { padding: 18px 16px; }
        body.sidebar-collapsed .sidebar:hover .brand .brand-text,
        body.sidebar-collapsed .sidebar:hover .nav-link .nav-text,
        body.sidebar-collapsed .sidebar:hover .nav-section,
        body.sidebar-hovered .sidebar .brand .brand-text,
        body.sidebar-hovered .sidebar .nav-link .nav-text,
        body.sidebar-hovered .sidebar .nav-section { opacity: 1; width: auto; height: auto; padding: inherit; }
        body.sidebar-collapsed .sidebar:hover .nav-link,
        body.sidebar-hovered .sidebar .nav-link { justify-content: flex-start; padding: 10px 16px; }
        body.sidebar-collapsed .sidebar:hover .nav-section,
        body.sidebar-hovered .sidebar .nav-section { display: block; padding: 14px 16px 5px; }
        body.sidebar-hovered .main-content { margin-left: var(--sidebar-width); }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: var(--sidebar-width); min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .topbar {
            height: var(--topbar-height); background: #fff; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between; padding: 0 24px;
            position: sticky; top: 0; z-index: 999;
        }
        .sidebar-toggle {
            background: none; border: 1px solid #e2e8f0; border-radius: 6px;
            padding: 4px 8px; cursor: pointer; color: #64748b; transition: all 0.2s;
        }
        .sidebar-toggle:hover { background: #f1f5f9; color: #1e293b; }
        .content-area { padding: 24px; }
        .stat-card {
            background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .stat-number { font-size: 1.8rem; font-weight: 700; }
        .stat-card .stat-label { color: #64748b; font-size: 0.85rem; }
        .table-container { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
        .page-header { margin-bottom: 24px; }
        .page-header h4 { font-weight: 700; margin: 0; }
        .notification-badge { position: absolute; top: -5px; right: -5px; }

        /* ===== TOOLTIP for collapsed icons ===== */
        body.sidebar-collapsed .sidebar .nav-link { position: relative; }
        body.sidebar-collapsed .sidebar .nav-link:hover::after {
            content: attr(data-tip); position: absolute; left: calc(var(--sidebar-collapsed) + 6px);
            top: 50%; transform: translateY(-50%); background: #334155; color: #fff;
            padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; white-space: nowrap;
            z-index: 9999; pointer-events: none;
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); width: var(--sidebar-width) !important; }
            .sidebar.show { transform: translateX(0); }
            .sidebar.show .brand .brand-text,
            .sidebar.show .nav-link .nav-text,
            .sidebar.show .nav-section { opacity: 1; width: auto; height: auto; }
            .sidebar.show .nav-link { justify-content: flex-start; padding: 10px 16px; }
            .sidebar.show .nav-section { display: block; }
            .main-content { margin-left: 0 !important; }
        }
    </style>
</head>
<body>
    <?php $user = currentUser(); $role = $user['role_name'] ?? ''; ?>
    <?php 
    // Determine notification base URL and current URI for active nav detection
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    $notifBase = '/' . explode('/', trim($currentUri, '/'))[0] ?? '/bestdealcrm';
    ?>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-building"></i> BestDeal CRM
        </div>
        <nav class="mt-3">
            <?php if ($role === 'admin'): ?>
                <div class="nav-section">Main</div>
                <a href="/bestdealcrm/admin/dashboard" class="nav-link <?= ($currentUri === '/admin/dashboard' || $currentUri === '/bestdealcrm/admin/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                
                <div class="nav-section">User Management</div>
                <a href="/bestdealcrm/admin/users" class="nav-link <?= str_contains($currentUri, '/admin/users') && !str_contains($currentUri, '/create') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i> Users
                </a>
                <a href="/bestdealcrm/admin/roles" class="nav-link <?= str_contains($currentUri, '/admin/roles') ? 'active' : '' ?>">
                    <i class="bi bi-shield-lock"></i> Roles & Permissions
                </a>
                
                <div class="nav-section">Lead Management</div>
                <a href="/bestdealcrm/admin/leads/upload" class="nav-link <?= str_contains($currentUri, '/leads/upload') ? 'active' : '' ?>">
                    <i class="bi bi-cloud-upload"></i> Upload Leads
                </a>
                <a href="/bestdealcrm/admin/leads" class="nav-link <?= (str_contains($currentUri, '/admin/leads') && !str_contains($currentUri, '/upload') && !str_contains($currentUri, '/assign')) ? 'active' : '' ?>">
                    <i class="bi bi-list-ul"></i> All Leads
                </a>
                <a href="/bestdealcrm/admin/leads/assign" class="nav-link <?= str_contains($currentUri, '/leads/assign') ? 'active' : '' ?>">
                    <i class="bi bi-person-check"></i> Assign Leads
                </a>
                <a href="/bestdealcrm/admin/leads/reassign" class="nav-link <?= str_contains($currentUri, '/leads/reassign') ? 'active' : '' ?>">
                    <i class="bi bi-arrow-left-right"></i> Reassign Leads
                </a>
                
                <div class="nav-section">Workflow</div>
                <a href="/bestdealcrm/admin/review1" class="nav-link <?= str_contains($currentUri, '/admin/review1') ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-check"></i> Review (Stage 1)
                </a>
                <a href="/bestdealcrm/admin/review2" class="nav-link <?= str_contains($currentUri, '/admin/review2') ? 'active' : '' ?>">
                    <i class="bi bi-clipboard2-check"></i> Review (Stage 2)
                </a>
                <a href="/bestdealcrm/admin/review3" class="nav-link <?= str_contains($currentUri, '/admin/review3') ? 'active' : '' ?>">
                    <i class="bi bi-clipboard2-check"></i> Review (Stage 3)
                </a>
                <a href="/bestdealcrm/admin/review4" class="nav-link <?= str_contains($currentUri, '/admin/review4') ? 'active' : '' ?>">
                    <i class="bi bi-clipboard2-check"></i> Review (Stage 4)
                </a>
                <a href="/bestdealcrm/admin/workflow" class="nav-link <?= str_contains($currentUri, '/admin/workflow') ? 'active' : '' ?>">
                    <i class="bi bi-diagram-3"></i> Workflow Stages
                </a>
                
                <div class="nav-section">Builder</div>
                <a href="/bestdealcrm/admin/form-builder" class="nav-link <?= str_contains($currentUri, '/form-builder') ? 'active' : '' ?>">
                    <i class="bi bi-ui-checks-grid"></i> Form Builder
                </a>
                
                <div class="nav-section">System</div>
                <a href="/bestdealcrm/admin/reports" class="nav-link <?= str_contains($currentUri, '/admin/reports') ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-bar-graph"></i> Reports
                </a>
                <a href="/bestdealcrm/admin/notifications" class="nav-link <?= str_contains($currentUri, '/notifications') ? 'active' : '' ?>">
                    <i class="bi bi-bell"></i> Notifications
                </a>
                <a href="/bestdealcrm/admin/activity-logs" class="nav-link <?= str_contains($currentUri, '/activity-logs') ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i> Activity Logs
                </a>

                <div class="nav-section">Tools</div>
                <a href="/bestdealcrm/tools/calculator" class="nav-link <?= str_contains($currentUri, '/tools/calculator') ? 'active' : '' ?>">
                    <i class="bi bi-calculator"></i> EMI Calculator
                </a>
                <a href="/bestdealcrm/tools/eligibility" class="nav-link <?= str_contains($currentUri, '/tools/eligibility') ? 'active' : '' ?>">
                    <i class="bi bi-bank"></i> Eligibility Checker
                </a>

                <div class="nav-section">Services</div>
                <a href="/bestdealcrm/admin/pf-requests" class="nav-link <?= str_contains($currentUri, '/pf-requests') || str_contains($currentUri, '/pf-verify') ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i> PF Requests
                </a>
                <a href="/bestdealcrm/admin/cibil-requests" class="nav-link <?= str_contains($currentUri, '/cibil-requests') || str_contains($currentUri, '/cibil-verify') ? 'active' : '' ?>">
                    <i class="bi bi-credit-card"></i> CIBIL Requests
                </a>
                <a href="/bestdealcrm/admin/data-dashboard" class="nav-link <?= str_contains($currentUri, '/data-dashboard') || str_contains($currentUri, '/data-view') || str_contains($currentUri, '/data-add') ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-data"></i> Data Entry Admin
                </a>

            <?php elseif ($role === 'team_leader'): ?>
                <div class="nav-section">Main</div>
                <a href="/bestdealcrm/team-leader/dashboard" class="nav-link <?= str_contains($currentUri, '/team-leader/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="/bestdealcrm/team-leader/team" class="nav-link <?= str_contains($currentUri, '/team-leader/team') && !str_contains($currentUri, '/leads') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i> My Team
                </a>
                <a href="/bestdealcrm/team-leader/team/leads" class="nav-link <?= str_contains($currentUri, '/team/leads') ? 'active' : '' ?>">
                    <i class="bi bi-list-ul"></i> Team Leads
                </a>
                <a href="/bestdealcrm/team-leader/notifications" class="nav-link <?= str_contains($currentUri, '/notifications') ? 'active' : '' ?>">
                    <i class="bi bi-bell"></i> Notifications
                </a>
                <div class="nav-section">Tools</div>
                <a href="/bestdealcrm/tools/calculator" class="nav-link <?= str_contains($currentUri, '/tools/calculator') ? 'active' : '' ?>">
                    <i class="bi bi-calculator"></i> EMI Calculator
                </a>
                <a href="/bestdealcrm/tools/eligibility" class="nav-link <?= str_contains($currentUri, '/tools/eligibility') ? 'active' : '' ?>">
                    <i class="bi bi-bank"></i> Eligibility Checker
                </a>
                <div class="nav-section">Services</div>
                <a href="/bestdealcrm/services/pf" class="nav-link <?= str_contains($currentUri, '/services/pf') ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i> PF Request
                </a>
                <a href="/bestdealcrm/services/cibil" class="nav-link <?= str_contains($currentUri, '/services/cibil') ? 'active' : '' ?>">
                    <i class="bi bi-credit-card"></i> CIBIL Request
                </a>

            <?php elseif ($role === 'agent'): ?>
                <div class="nav-section">Main</div>
                <a href="/bestdealcrm/agent/dashboard" class="nav-link <?= str_contains($currentUri, '/agent/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="/bestdealcrm/agent/leads" class="nav-link <?= str_contains($currentUri, '/agent/leads') && !str_contains($currentUri, '/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-list-ul"></i> My Leads
                </a>
                <a href="/bestdealcrm/agent/notifications" class="nav-link <?= str_contains($currentUri, '/notifications') ? 'active' : '' ?>">
                    <i class="bi bi-bell"></i> Notifications
                </a>
                <div class="nav-section">Tools</div>
                <a href="/bestdealcrm/tools/calculator" class="nav-link <?= str_contains($currentUri, '/tools/calculator') ? 'active' : '' ?>">
                    <i class="bi bi-calculator"></i> EMI Calculator
                </a>
                <a href="/bestdealcrm/tools/eligibility" class="nav-link <?= str_contains($currentUri, '/tools/eligibility') ? 'active' : '' ?>">
                    <i class="bi bi-bank"></i> Eligibility Checker
                </a>

                <div class="nav-section">Services</div>
                <a href="/bestdealcrm/services/pf" class="nav-link <?= str_contains($currentUri, '/services/pf') ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i> PF Request
                </a>
                <a href="/bestdealcrm/services/cibil" class="nav-link <?= str_contains($currentUri, '/services/cibil') ? 'active' : '' ?>">
                    <i class="bi bi-credit-card"></i> CIBIL Request
                </a>
                <a href="/bestdealcrm/agent/data-entry" class="nav-link <?= str_contains($currentUri, '/data-entry') ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-data"></i> Data Entry
                </a>

            <?php elseif ($role === 'login_agent'): ?>
                <div class="nav-section">Main</div>
                <a href="/bestdealcrm/login-agent/dashboard" class="nav-link <?= str_contains($currentUri, '/login-agent/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="/bestdealcrm/login-agent/cases" class="nav-link <?= str_contains($currentUri, '/login-agent/cases') && !str_contains($currentUri, '/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-folder2-open"></i> Assigned Cases
                </a>
                <a href="/bestdealcrm/login-agent/notifications" class="nav-link <?= str_contains($currentUri, '/notifications') ? 'active' : '' ?>">
                    <i class="bi bi-bell"></i> Notifications
                </a>
                <div class="nav-section">Tools</div>
                <a href="/bestdealcrm/tools/calculator" class="nav-link <?= str_contains($currentUri, '/tools/calculator') ? 'active' : '' ?>">
                    <i class="bi bi-calculator"></i> EMI Calculator
                </a>
                <a href="/bestdealcrm/tools/eligibility" class="nav-link <?= str_contains($currentUri, '/tools/eligibility') ? 'active' : '' ?>">
                    <i class="bi bi-bank"></i> Eligibility Checker
                </a>
                <div class="nav-section">Services</div>
                <a href="/bestdealcrm/services/pf" class="nav-link <?= str_contains($currentUri, '/services/pf') ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i> PF Request
                </a>
                <a href="/bestdealcrm/services/cibil" class="nav-link <?= str_contains($currentUri, '/services/cibil') ? 'active' : '' ?>">
                    <i class="bi bi-credit-card"></i> CIBIL Request
                </a>

            <?php elseif ($role === 'underwriting'): ?>
                <div class="nav-section">Main</div>
                <a href="/bestdealcrm/underwriting/dashboard" class="nav-link <?= str_contains($currentUri, '/underwriting/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="/bestdealcrm/underwriting/cases" class="nav-link <?= str_contains($currentUri, '/underwriting/cases') && !str_contains($currentUri, '/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-folder2-open"></i> Cases
                </a>
                <a href="/bestdealcrm/underwriting/notifications" class="nav-link <?= str_contains($currentUri, '/notifications') ? 'active' : '' ?>">
                    <i class="bi bi-bell"></i> Notifications
                </a>
                <div class="nav-section">Tools</div>
                <a href="/bestdealcrm/tools/calculator" class="nav-link <?= str_contains($currentUri, '/tools/calculator') ? 'active' : '' ?>">
                    <i class="bi bi-calculator"></i> EMI Calculator
                </a>
                <a href="/bestdealcrm/tools/eligibility" class="nav-link <?= str_contains($currentUri, '/tools/eligibility') ? 'active' : '' ?>">
                    <i class="bi bi-bank"></i> Eligibility Checker
                </a>
                <div class="nav-section">Services</div>
                <a href="/bestdealcrm/services/pf" class="nav-link <?= str_contains($currentUri, '/services/pf') ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i> PF Request
                </a>
                <a href="/bestdealcrm/services/cibil" class="nav-link <?= str_contains($currentUri, '/services/cibil') ? 'active' : '' ?>">
                    <i class="bi bi-credit-card"></i> CIBIL Request
                </a>

            <?php elseif ($role === 'dispatch'): ?>
                <div class="nav-section">Main</div>
                <a href="/bestdealcrm/dispatch/dashboard" class="nav-link <?= str_contains($currentUri, '/dispatch/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="/bestdealcrm/dispatch/cases" class="nav-link <?= str_contains($currentUri, '/dispatch/cases') && !str_contains($currentUri, '/dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-folder2-open"></i> Cases
                </a>
                <a href="/bestdealcrm/dispatch/notifications" class="nav-link <?= str_contains($currentUri, '/notifications') ? 'active' : '' ?>">
                    <i class="bi bi-bell"></i> Notifications
                </a>
                <div class="nav-section">Tools</div>
                <a href="/bestdealcrm/tools/calculator" class="nav-link <?= str_contains($currentUri, '/tools/calculator') ? 'active' : '' ?>">
                    <i class="bi bi-calculator"></i> EMI Calculator
                </a>
                <a href="/bestdealcrm/tools/eligibility" class="nav-link <?= str_contains($currentUri, '/tools/eligibility') ? 'active' : '' ?>">
                    <i class="bi bi-bank"></i> Eligibility Checker
                </a>
                <div class="nav-section">Services</div>
                <a href="/bestdealcrm/services/pf" class="nav-link <?= str_contains($currentUri, '/services/pf') ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-text"></i> PF Request
                </a>
                <a href="/bestdealcrm/services/cibil" class="nav-link <?= str_contains($currentUri, '/services/cibil') ? 'active' : '' ?>">
                    <i class="bi bi-credit-card"></i> CIBIL Request
                </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm sidebar-toggle me-3" onclick="toggleSidebar()" title="Toggle sidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <span class="text-muted"><?= htmlspecialchars($title ?? '') ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php 
                $notifCount = unreadNotificationCount();
                switch ($role) {
                    case 'agent': $rolePrefix = 'agent'; break;
                    case 'login_agent': $rolePrefix = 'login-agent'; break;
                    case 'team_leader': $rolePrefix = 'team-leader'; break;
                    case 'underwriting': $rolePrefix = 'underwriting'; break;
                    case 'dispatch': $rolePrefix = 'dispatch'; break;
                    default: $rolePrefix = 'admin';
                }
                ?>
                <a href="/bestdealcrm/<?= $rolePrefix ?>/notifications" class="position-relative text-decoration-none">
                    <i class="bi bi-bell fs-5 text-muted"></i>
                    <?php if ($notifCount > 0): ?>
                        <span class="badge bg-danger notification-badge"><?= $notifCount > 99 ? '99+' : $notifCount ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:35px;height:35px">
                            <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="ms-2 d-none d-md-inline text-dark"><?= htmlspecialchars($user['name'] ?? '') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small">Signed in as <strong><?= htmlspecialchars($user['username'] ?? '') ?></strong></span></li>
                        <li><span class="dropdown-item-text text-muted small">Role: <?= ucfirst(str_replace('_', ' ', $role)) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="showChangePasswordModal()"><i class="bi bi-key me-2"></i>Change Password</a></li>
                        <li><a class="dropdown-item" href="/bestdealcrm/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="content-area">
            <!-- Flash Messages -->
            <?php $flash = getAllFlash(); foreach ($flash as $type => $message): ?>
                <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // CSRF token for AJAX
        const CSRF_TOKEN = '<?= csrfToken() ?>';
        const BASE_URL = '<?= defined('BASE_URL') ? BASE_URL : '/bestdealcrm' ?>';
        
        // Show toast notification
        function showToast(message, type) {
            type = type || 'info';
            var container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;max-width:400px;';
                document.body.appendChild(container);
            }
            var colors = { success: '#22c55e', danger: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
            var bg = colors[type] || colors.info;
            var toast = document.createElement('div');
            toast.style.cssText = 'background:' + bg + ';color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);font-size:14px;display:flex;justify-content:space-between;align-items:center;animation:fadeIn 0.3s ease;';
            toast.innerHTML = '<span>' + message + '</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:#fff;font-size:18px;cursor:pointer;margin-left:10px">&times;</button>';
            container.appendChild(toast);
            setTimeout(function() { toast.remove(); }, 5000);
        }
        
        // Generic AJAX helper with error handling
        async function ajaxPost(url, data) {
            var formData = data instanceof FormData ? data : (function() {
                var fd = new FormData();
                for (var key in data) {
                    if (Array.isArray(data[key])) {
                        data[key].forEach(function(v) { fd.append(key + '[]', v); });
                    } else {
                        fd.append(key, data[key]);
                    }
                }
                return fd;
            })();
            formData.append('_csrf_token', CSRF_TOKEN);
            try {
                var response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var text = await response.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error('Non-JSON response from', url, ':', text.substring(0, 500));
                    showToast('Server error. Check console for details.', 'danger');
                    return { error: 'Invalid server response' };
                }
            } catch(err) {
                console.error('AJAX error:', url, err);
                showToast('Network error: ' + err.message, 'danger');
                return { error: err.message };
            }
        }
        
        // Generic AJAX GET helper
        async function ajaxGet(url) {
            try {
                var response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var text = await response.text();
                try {
                    return JSON.parse(text);
                } catch(e) {
                    console.error('Non-JSON response from', url, ':', text.substring(0, 500));
                    return { error: 'Invalid server response' };
                }
            } catch(err) {
                console.error('AJAX GET error:', url, err);
                return { error: err.message };
            }
        }

        // Sidebar toggle
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar_collapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
        }

        // Restore sidebar state on load
        if (localStorage.getItem('sidebar_collapsed') === '1') {
            document.body.classList.add('sidebar-collapsed');
        }

        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(e) {
            var sidebar = document.getElementById('sidebar');
            if (sidebar && sidebar.classList.contains('show') && !sidebar.contains(e.target) && !e.target.closest('button')) {
                sidebar.classList.remove('show');
            }
        });

        // Add tooltip text to nav links for collapsed mode
        document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
            var text = link.textContent.trim();
            link.setAttribute('data-tip', text);
        });

        // Sidebar hover expand/collapse
        var sidebarEl = document.getElementById('sidebar');
        var hoverTimeout = null;
        if (sidebarEl) {
            sidebarEl.addEventListener('mouseenter', function() {
                if (document.body.classList.contains('sidebar-collapsed')) {
                    clearTimeout(hoverTimeout);
                    document.body.classList.add('sidebar-hovered');
                }
            });
            sidebarEl.addEventListener('mouseleave', function() {
                if (document.body.classList.contains('sidebar-collapsed')) {
                    hoverTimeout = setTimeout(function() {
                        document.body.classList.remove('sidebar-hovered');
                    }, 200);
                }
            });
        }
    </script>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-key me-2"></i>Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                        <input type="password" id="cpOldPassword" class="form-control" placeholder="Enter current password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                        <input type="password" id="cpNewPassword" class="form-control" placeholder="Enter new password (min 6 chars)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" id="cpConfirmPassword" class="form-control" placeholder="Re-enter new password" required>
                    </div>
                    <div id="cpError" class="alert alert-danger d-none" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="cpSubmitBtn" onclick="submitChangePassword()">
                        <i class="bi bi-check me-1"></i> Change Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showChangePasswordModal() {
        document.getElementById('cpOldPassword').value = '';
        document.getElementById('cpNewPassword').value = '';
        document.getElementById('cpConfirmPassword').value = '';
        document.getElementById('cpError').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
    }

    async function submitChangePassword() {
        var oldPwd = document.getElementById('cpOldPassword').value;
        var newPwd = document.getElementById('cpNewPassword').value;
        var confirmPwd = document.getElementById('cpConfirmPassword').value;
        var errorDiv = document.getElementById('cpError');
        var btn = document.getElementById('cpSubmitBtn');

        errorDiv.classList.add('d-none');

        if (!oldPwd || !newPwd || !confirmPwd) {
            errorDiv.textContent = 'All fields are required.';
            errorDiv.classList.remove('d-none');
            return;
        }
        if (newPwd !== confirmPwd) {
            errorDiv.textContent = 'New password and confirmation do not match.';
            errorDiv.classList.remove('d-none');
            return;
        }
        if (newPwd.length < 6) {
            errorDiv.textContent = 'New password must be at least 6 characters.';
            errorDiv.classList.remove('d-none');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Changing...';

        var formData = new FormData();
        formData.append('old_password', oldPwd);
        formData.append('new_password', newPwd);
        formData.append('confirm_password', confirmPwd);

        try {
            var result = await ajaxPost(BASE_URL + '/change-password', formData);
            if (result && result.success) {
                showToast(result.message || 'Password changed successfully.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
            } else {
                errorDiv.textContent = result.error || 'Failed to change password.';
                errorDiv.classList.remove('d-none');
            }
        } catch (err) {
            errorDiv.textContent = 'Server error: ' + err.message;
            errorDiv.classList.remove('d-none');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check me-1"></i> Change Password';
    }
    </script>

    <script src="/bestdealcrm/public/assets/js/app.js"></script>
</body>
</html>
