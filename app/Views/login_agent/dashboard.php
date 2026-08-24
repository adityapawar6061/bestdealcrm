<div class="page-header">
    <h4><i class="bi bi-speedometer2 me-2"></i>Login Agent Dashboard</h4>
    <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars(currentUser()['name']) ?>!</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="stat-card border-start border-info border-4">
            <div class="stat-number text-info"><?= $stats['assigned_cases'] ?></div>
            <div class="stat-label">Assigned Cases</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card border-start border-warning border-4">
            <div class="stat-number text-warning"><?= $stats['drafts'] ?></div>
            <div class="stat-label">Drafts</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card border-start border-primary border-4">
            <div class="stat-number text-primary"><?= $stats['submitted'] ?></div>
            <div class="stat-label">Submitted</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card border-start border-success border-4">
            <div class="stat-number text-success"><?= $stats['approved'] ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card border-start border-purple border-4">
            <div class="stat-number" style="color:#8b5cf6"><?= $stats['post_login'] ?></div>
            <div class="stat-label">Post Login</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card border-start border-danger border-4">
            <div class="stat-number text-danger"><?= $stats['returned'] ?></div>
            <div class="stat-label">Returned</div>
        </div>
    </div>
</div>

<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 fw-bold">Recent Cases</h6>
        <a href="/bestdealcrm/login-agent/cases" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Stage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLeads['data'])): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No cases assigned yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentLeads['data'] as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td>
                            <?php if ($lead['workflow_stage'] === 'LOGIN_AGENT_ASSIGNED' || $lead['workflow_stage'] === 'LOGIN_AGENT_DRAFT'): ?>
                                <a href="/bestdealcrm/login-agent/cases/<?= $lead['id'] ?>/pre-login" class="btn btn-sm btn-primary">
                                    <i class="bi bi-clipboard-check me-1"></i>Pre-Login
                                </a>
                            <?php elseif ($lead['workflow_stage'] === 'LOGIN_APPROVED'): ?>
                                <a href="/bestdealcrm/login-agent/cases/<?= $lead['id'] ?>/post-login" class="btn btn-sm btn-success">
                                    <i class="bi bi-clipboard-data me-1"></i>Post-Login
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
