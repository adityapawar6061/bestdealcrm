<div class="page-header">
    <h4><i class="bi bi-folder2-open me-2"></i>Assigned Cases</h4>
</div>

<div class="table-container mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="workflow_stage" class="form-select form-select-sm">
                <option value="">All Stages</option>
                <?php foreach (['LOGIN_AGENT_ASSIGNED','LOGIN_AGENT_DRAFT','ADMIN_REVIEW_2','LOGIN_APPROVED','POST_LOGIN','RETURNED_TO_AGENT'] as $stage): ?>
                    <option value="<?= $stage ?>" <?= ($filters['workflow_stage'] ?? '') === $stage ? 'selected' : '' ?>><?= humanStatus($stage) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i> Filter</button></div>
    </form>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
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
                <?php if (empty($leads['data'])): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No cases found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td>
                            <?php if (in_array($lead['workflow_stage'], ['LOGIN_AGENT_ASSIGNED', 'LOGIN_AGENT_DRAFT'])): ?>
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
