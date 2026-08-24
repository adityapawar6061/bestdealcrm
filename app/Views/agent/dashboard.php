<div class="page-header">
    <h4><i class="bi bi-speedometer2 me-2"></i>Agent Dashboard</h4>
    <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars(currentUser()['name']) ?>!</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card border-start border-primary border-4">
            <div class="stat-number text-primary"><?= $stats['my_leads'] ?></div>
            <div class="stat-label">My Leads</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-warning border-4">
            <div class="stat-number text-warning"><?= $stats['drafts'] ?></div>
            <div class="stat-label">Drafts</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-info border-4">
            <div class="stat-number text-info"><?= $stats['submitted'] ?></div>
            <div class="stat-label">Submitted</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-danger border-4">
            <div class="stat-number text-danger"><?= $stats['returned'] ?></div>
            <div class="stat-label">Returned</div>
        </div>
    </div>
</div>

<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 fw-bold">Recent Leads</h6>
        <a href="/bestdealcrm/agent/leads" class="btn btn-sm btn-outline-primary">View All</a>
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
                    <tr><td colspan="5" class="text-center text-muted py-3">No leads assigned yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentLeads['data'] as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td>
                            <?php if (in_array($lead['workflow_stage'], ['LEAD_ASSIGNED', 'AGENT_DRAFT', 'RETURNED_TO_AGENT'])): ?>
                                <a href="/bestdealcrm/agent/leads/<?= $lead['id'] ?>/fill-form" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil-square me-1"></i>Fill Form
                                </a>
                            <?php else: ?>
                                <a href="/bestdealcrm/agent/leads/<?= $lead['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>View
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
