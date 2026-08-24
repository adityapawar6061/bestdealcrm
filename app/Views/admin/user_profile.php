<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($profileUser['name'] ?? 'User') ?></h4>
        <small class="text-muted"><?= htmlspecialchars($profileUser['email'] ?? '') ?> | <?= ucfirst(str_replace('_', ' ', $profileUser['role_name'] ?? '')) ?></small>
    </div>
    <a href="/bestdealcrm/admin/users" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="table-container">
            <h6 class="fw-bold mb-3">Profile Info</h6>
            <div class="row g-2">
                <div class="col-6">
                    <small class="text-muted d-block">Name</small>
                    <strong><?= htmlspecialchars($profileUser['name'] ?? '') ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Username</small>
                    <strong><?= htmlspecialchars($profileUser['username'] ?? '') ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Email</small>
                    <strong><?= htmlspecialchars($profileUser['email'] ?? '') ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Mobile</small>
                    <strong><?= htmlspecialchars($profileUser['mobile'] ?? '-') ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Role</small>
                    <span class="badge bg-primary"><?= ucfirst(str_replace('_', ' ', $profileUser['role_name'] ?? '')) ?></span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Status</small>
                    <span class="badge bg-<?= $profileUser['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($profileUser['status'] ?? '') ?></span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Created</small>
                    <strong><?= formatDate($profileUser['created_at'] ?? '', 'd M Y') ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Last Login</small>
                    <strong><?= $profileUser['last_login_at'] ? formatDate($profileUser['last_login_at'], 'd M Y, h:i A') : 'Never' ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-container">
            <h6 class="fw-bold mb-3">Login History</h6>
            <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>IP Address</th><th>Login Time</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($loginHistory)): ?>
                            <tr><td colspan="2" class="text-center text-muted py-3">No login history.</td></tr>
                        <?php else: ?>
                            <?php foreach ($loginHistory as $log): ?>
                            <tr>
                                <td><small><?= htmlspecialchars($log['ip_address'] ?? '') ?></small></td>
                                <td><small class="text-muted"><?= formatDate($log['login_at'], 'd M Y, h:i A') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
