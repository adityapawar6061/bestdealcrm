<div class="page-header">
    <h4><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</h4>
</div>

<!-- Stats Cards Row 1 -->
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

<!-- Stats Cards Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number" style="font-size:1.4rem;color:#f59e0b"><?= $stats['agent_draft'] ?></div>
            <div class="stat-label">Agent Drafts</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number" style="font-size:1.4rem;color:#f97316"><?= $stats['pending_review_1'] ?></div>
            <div class="stat-label">Pending Review 1</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number" style="font-size:1.4rem;color:#06b6d4"><?= $stats['login_pending'] + $stats['login_draft'] ?></div>
            <div class="stat-label">Login Pending</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number" style="font-size:1.4rem;color:#f97316"><?= $stats['pending_review_2'] ?></div>
            <div class="stat-label">Pending Review 2</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number" style="font-size:1.4rem;color:#8b5cf6"><?= $stats['underwriting'] ?></div>
            <div class="stat-label">Underwriting</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number" style="font-size:1.4rem;color:#ef4444"><?= $stats['rejected'] ?></div>
            <div class="stat-label">Rejected</div>
        </div>
    </div>
</div>

<!-- Recent Leads & Activity -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold">Recent Leads</h6>
                <a href="/bestdealcrm/admin/leads" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Stage</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentLeads)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No leads yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentLeads as $lead): ?>
                            <tr>
                                <td><a href="/bestdealcrm/admin/leads/<?= $lead['id'] ?>"><?= $lead['id'] ?></a></td>
                                <td><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                                <td><?= statusBadge($lead['workflow_stage']) ?></td>
                                <td><small class="text-muted"><?= formatDate($lead['created_at'], 'd M, h:i A') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="table-container">
            <h6 class="mb-3 fw-bold">Recent Activity</h6>
            <div class="activity-list" style="max-height:400px;overflow-y:auto">
                <?php if (empty($recentActivity)): ?>
                    <p class="text-muted text-center py-3">No recent activity.</p>
                <?php else: ?>
                    <?php foreach ($recentActivity as $log): ?>
                    <div class="d-flex gap-2 mb-3 pb-3 border-bottom">
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px">
                            <i class="bi bi-activity text-primary small"></i>
                        </div>
                        <div>
                            <div class="small">
                                <strong><?= htmlspecialchars($log['user_name'] ?? 'System') ?></strong>
                                <span class="text-muted"><?= htmlspecialchars(str_replace('_', ' ', $log['action'])) ?></span>
                            </div>
                            <div class="text-muted" style="font-size:0.75rem"><?= formatDate($log['created_at'], 'd M, h:i A') ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
