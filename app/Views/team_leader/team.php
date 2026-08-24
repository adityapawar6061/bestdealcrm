<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-people me-2"></i>My Team</h4>
    <a href="/bestdealcrm/team-leader/dashboard" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Name</th><th>Email</th><th>Username</th><th>Leads</th><th>Status</th><th>Last Login</th></tr>
            </thead>
            <tbody>
                <?php if (empty($teamMembers)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No team members.</td></tr>
                <?php else: ?>
                    <?php foreach ($teamMembers as $m): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars($m['username']) ?></td>
                        <td><span class="badge bg-primary"><?= $m['lead_count'] ?? 0 ?></span></td>
                        <td><span class="badge bg-<?= $m['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($m['status']) ?></span></td>
                        <td><small class="text-muted"><?= $m['last_login_at'] ? formatDate($m['last_login_at']) : 'Never' ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
