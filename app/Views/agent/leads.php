<div class="page-header">
    <h4><i class="bi bi-list-ul me-2"></i>My Leads</h4>
</div>

<!-- Filters -->
<div class="table-container mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by ID, name, mobile..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="workflow_stage" class="form-select form-select-sm">
                <option value="">All Stages</option>
                <?php
                $stages = ['LEAD_ASSIGNED','AGENT_DRAFT','ADMIN_REVIEW_1','RETURNED_TO_AGENT','LOGIN_APPROVED','COMPLETED','REJECTED'];
                foreach ($stages as $stage):
                ?>
                    <option value="<?= $stage ?>" <?= ($filters['workflow_stage'] ?? '') === $stage ? 'selected' : '' ?>>
                        <?= humanStatus($stage) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i> Filter</button>
        </div>
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
                    <th>Location</th>
                    <th>Bank</th>
                    <th>Salary</th>
                    <th>Stage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads['data'])): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No leads found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars($lead['location'] ?? '-') ?></small></td>
                        <td><small><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></small></td>
                        <td><small><?= $lead['salary'] ? '₹' . number_format($lead['salary']) : '-' ?></small></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td>
                            <?php if (in_array($lead['workflow_stage'], ['LEAD_ASSIGNED', 'AGENT_DRAFT', 'RETURNED_TO_AGENT'])): ?>
                                <a href="/bestdealcrm/agent/leads/<?= $lead['id'] ?>/fill-form" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil-square"></i> Fill
                                </a>
                            <?php endif; ?>
                            <a href="/bestdealcrm/agent/leads/<?= $lead['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($leads['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= $leads['from'] ?>-<?= $leads['to'] ?> of <?= number_format($leads['total']) ?></small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= min($leads['total_pages'], 10); $i++): ?>
                    <li class="page-item <?= $i == $leads['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
