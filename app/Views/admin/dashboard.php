<?php
/**
 * Admin Dashboard - Self-contained with all CSS inline
 */
$user = currentUser();
$role = $user['role_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BestDeal CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; }
        .sidebar { position: fixed; top: 0; left: 0; bottom: 0; width: 260px; background: #1e293b; color: #fff; overflow-y: auto; z-index: 1000; }
        .sidebar .brand { padding: 20px; font-size: 1.2rem; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .nav-link { color: #94a3b8; padding: 10px 20px; display: flex; align-items: center; gap: 10px; text-decoration: none; font-size: 0.9rem; }
        .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); }
        .sidebar .nav-link.active { color: #fff; background: #3b82f6; border-radius: 0 25px 25px 0; margin-right: 10px; }
        .sidebar .nav-section { padding: 15px 20px 5px; font-size: 0.75rem; text-transform: uppercase; color: #64748b; letter-spacing: 1px; }
        .main-content { margin-left: 260px; min-height: 100vh; }
        .topbar { height: 60px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 999; }
        .content-area { padding: 24px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .stat-card .stat-number { font-size: 1.8rem; font-weight: 700; }
        .stat-card .stat-label { color: #64748b; font-size: 0.85rem; }
        .table-container { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="brand"><i class="bi bi-building"></i> BestDeal CRM</div>
        <nav class="mt-3">
            <div class="nav-section">Main</div>
            <a href="/bestdealcrm/admin/dashboard" class="nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <div class="nav-section">User Management</div>
            <a href="/bestdealcrm/admin/users" class="nav-link"><i class="bi bi-people"></i> Users</a>
            <div class="nav-section">Lead Management</div>
            <a href="/bestdealcrm/admin/leads" class="nav-link"><i class="bi bi-list-ul"></i> All Leads</a>
            <a href="/bestdealcrm/admin/leads/upload" class="nav-link"><i class="bi bi-cloud-upload"></i> Upload Leads</a>
            <a href="/bestdealcrm/admin/leads/assign" class="nav-link"><i class="bi bi-person-check"></i> Assign Leads</a>
            <div class="nav-section">Workflow</div>
            <a href="/bestdealcrm/admin/review1" class="nav-link"><i class="bi bi-clipboard-check"></i> Review Stage 1</a>
            <a href="/bestdealcrm/admin/review2" class="nav-link"><i class="bi bi-clipboard2-check"></i> Review Stage 2</a>
            <div class="nav-section">System</div>
            <a href="/bestdealcrm/admin/notifications" class="nav-link"><i class="bi bi-bell"></i> Notifications</a>
            <a href="/bestdealcrm/admin/activity-logs" class="nav-link"><i class="bi bi-clock-history"></i> Activity Logs</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <span class="text-muted">Admin Dashboard</span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-dark"><?= htmlspecialchars($user['name'] ?? '') ?></span>
                <a href="/bestdealcrm/logout" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </header>

        <div class="content-area">
            <h4 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h4>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card border-start border-primary border-4">
                        <div class="stat-number text-primary"><?= number_format($stats['total_leads']) ?></div>
                        <div class="stat-label">Total Leads</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-warning border-4">
                        <div class="stat-number text-warning"><?= number_format($stats['unassigned']) ?></div>
                        <div class="stat-label">Unassigned Leads</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-info border-4">
                        <div class="stat-number text-info"><?= number_format($stats['assigned']) ?></div>
                        <div class="stat-label">Assigned Leads</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card border-start border-success border-4">
                        <div class="stat-number text-success"><?= number_format($stats['completed']) ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-warning" style="font-size:1.4rem"><?= $stats['agent_draft'] ?></div>
                        <div class="stat-label">Agent Drafts</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-primary" style="font-size:1.4rem"><?= $stats['pending_review_1'] ?></div>
                        <div class="stat-label">Pending Review 1</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-info" style="font-size:1.4rem"><?= $stats['login_pending'] ?></div>
                        <div class="stat-label">Login Pending</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-number text-danger" style="font-size:1.4rem"><?= $stats['rejected'] ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <h6 class="fw-bold mb-3">Recent Leads</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>#</th><th>Customer</th><th>Mobile</th><th>Stage</th><th>Created</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentLeads)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No leads yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentLeads as $lead): ?>
                                <tr>
                                    <td><?= $lead['id'] ?></td>
                                    <td><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                                    <td><span class="badge bg-secondary"><?= humanStatus($lead['workflow_stage']) ?></span></td>
                                    <td><small class="text-muted"><?= formatDate($lead['created_at']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
