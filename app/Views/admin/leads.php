<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-list-ul me-2"></i>All Leads</h4>
    <a href="/bestdealcrm/admin/leads/upload" class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload me-1"></i> Upload Leads</a>
</div>

<!-- Filters -->
<div class="table-container mb-3">
    <form class="row g-2 align-items-end" method="GET">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, mobile, PAN..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="workflow_stage" class="form-select form-select-sm">
                <option value="">All Stages</option>
                <?php $stages = ['LEAD_UPLOADED','LEAD_ASSIGNED','AGENT_DRAFT','ADMIN_REVIEW_1','LOGIN_AGENT_ASSIGNED','LOGIN_APPROVED','UNDERWRITING','DISPATCH','COMPLETED','REJECTED'];
                foreach ($stages as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['workflow_stage'] ?? '') === $s ? 'selected' : '' ?>><?= humanStatus($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="assigned_to" class="form-select form-select-sm">
                <option value="">All Agents</option>
                <?php foreach ($agents as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= ($filters['assigned_to'] ?? '') == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Customer</th><th>Mobile</th><th>Location</th><th>Bank</th><th>Assigned To</th><th>Stage</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($leads['data'])): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No leads found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars($lead['location'] ?? '-') ?></small></td>
                        <td><small><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></small></td>
                        <td><small><?= htmlspecialchars($lead['assigned_to_name'] ?? 'Unassigned') ?></small></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td><small class="text-muted"><?= formatDate($lead['created_at']) ?></small></td>
                        <td><a href="/bestdealcrm/admin/leads/<?= $lead['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($leads['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= $leads['from'] ?? 0 ?>-<?= $leads['to'] ?? 0 ?> of <?= number_format($leads['total']) ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $leads['total_pages']; $i++): ?>
                <li class="page-item <?= $i == $leads['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&workflow_stage=<?= urlencode($filters['workflow_stage'] ?? '') ?>&assigned_to=<?= urlencode($filters['assigned_to'] ?? '') ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
