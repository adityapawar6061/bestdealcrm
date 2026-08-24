<div class="page-header">
    <h4><i class="bi bi-speedometer2 me-2"></i>Team Leader Dashboard</h4>
    <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars(currentUser()['name']) ?>!</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card border-start border-primary border-4">
            <div class="stat-number text-primary"><?= $stats['team_size'] ?></div>
            <div class="stat-label">Team Members</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-info border-4">
            <div class="stat-number text-info"><?= number_format($stats['total_leads']) ?></div>
            <div class="stat-label">Total Team Leads</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-warning border-4">
            <div class="stat-number text-warning"><?= $stats['agent_draft'] + $stats['pending_review'] ?></div>
            <div class="stat-label">Pending Actions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card border-start border-success border-4">
            <div class="stat-number text-success"><?= $stats['completed'] ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number text-warning" style="font-size:1.3rem"><?= $stats['agent_draft'] ?></div>
            <div class="stat-label">Agent Drafts</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number text-primary" style="font-size:1.3rem"><?= $stats['pending_review'] ?></div>
            <div class="stat-label">Pending Review</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number text-info" style="font-size:1.3rem"><?= $stats['submitted'] ?></div>
            <div class="stat-label">Submitted</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number text-danger" style="font-size:1.3rem"><?= $stats['returned'] ?></div>
            <div class="stat-label">Returned</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number text-success" style="font-size:1.3rem"><?= $stats['approved'] ?></div>
            <div class="stat-label">Approved</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stat-card text-center">
            <div class="stat-number text-success" style="font-size:1.3rem"><?= $stats['completed'] ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>
</div>

<!-- Team Members -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="table-container">
            <h6 class="fw-bold mb-3"><i class="bi bi-people me-1"></i> My Team</h6>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Leads</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamMembers as $m): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                            <td><?= $m['lead_count'] ?? 0 ?></td>
                            <td><span class="badge bg-<?= $m['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($m['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="table-container">
            <h6 class="fw-bold mb-3"><i class="bi bi-list-ul me-1"></i> Recent Team Leads</h6>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Customer</th><th>Stage</th><th>Assigned To</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentLeads)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No leads.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentLeads as $lead): ?>
                            <tr>
                                <td><?= $lead['id'] ?></td>
                                <td><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></td>
                                <td><?= statusBadge($lead['workflow_stage']) ?></td>
                                <td><small><?= htmlspecialchars($lead['assigned_to_name'] ?? '-') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
