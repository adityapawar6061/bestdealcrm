<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-list-ul me-2"></i>My Leads</h4>
    <span class="badge bg-primary"><?= number_format($leads['total']) ?> leads</span>
</div>

<!-- Filters -->
<div class="table-container mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="ID, name, mobile..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Stage</label>
            <select name="workflow_stage" class="form-select form-select-sm">
                <option value="">All Stages</option>
                <?php
                $stages = ['LEAD_ASSIGNED','AGENT_DRAFT','AGENT_SUBMITTED','ADMIN_REVIEW_1','RETURNED_TO_AGENT','LOGIN_APPROVED','COMPLETED','REJECTED'];
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
                    <th style="width:40px">#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Location</th>
                    <th>State</th>
                    <th>Existing LA</th>
                    <th class="text-end">Salary</th>
                    <th class="text-end">Actual Salary</th>
                    <th>Data Type</th>
                    <th>Bank Name</th>
                    <th>Response Date</th>
                    <th>Status</th>
                    <th>Disposition</th>
                    <th>Agent Remarks</th>
                    <th style="width:120px">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads['data'])): ?>
                    <tr><td colspan="15" class="text-center py-4 text-muted">No leads found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr>
                        <td class="text-muted"><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '—') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '—') ?></td>
                        <td><small><?= htmlspecialchars($lead['location'] ?? '—') ?></small></td>
                        <td><small><?= htmlspecialchars($lead['state'] ?? '—') ?></small></td>
                        <td><small><?= htmlspecialchars($lead['existing_la'] ?? '—') ?></small></td>
                        <td class="text-end"><small><?= $lead['salary'] ? '₹' . number_format($lead['salary']) : '—' ?></small></td>
                        <td class="text-end"><small><?= $lead['actual_salary'] ? '₹' . number_format($lead['actual_salary']) : '—' ?></small></td>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($lead['data_type'] ?? '—') ?></span></td>
                        <td><small><?= htmlspecialchars($lead['bank_name'] ?? '—') ?></small></td>
                        <td><small class="text-muted"><?= htmlspecialchars($lead['response_date'] ?? '—') ?></small></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td><small><?= htmlspecialchars($lead['agent_disposition'] ?? '—') ?></small></td>
                        <td><small class="text-muted"><?= htmlspecialchars($lead['agent_remark'] ?? '—') ?></small></td>
                        <td>
                            <?php if (in_array($lead['workflow_stage'], ['LEAD_ASSIGNED', 'AGENT_DRAFT', 'RETURNED_TO_AGENT'])): ?>
                                <a href="<?= BASE_URL ?>/agent/leads/<?= $lead['id'] ?>/fill-form" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil-square"></i> Fill Form
                                </a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/agent/leads/<?= $lead['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($leads['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= $leads['from'] ?>–<?= $leads['to'] ?> of <?= number_format($leads['total']) ?></small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($leads['current_page'] > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $leads['current_page'] - 1 ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['workflow_stage']) ? '&workflow_stage=' . $filters['workflow_stage'] : '' ?>">«</a></li>
                <?php endif; ?>
                <?php
                $startPage = max(1, $leads['current_page'] - 3);
                $endPage = min($leads['total_pages'], $leads['current_page'] + 3);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <li class="page-item <?= $i == $leads['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['workflow_stage']) ? '&workflow_stage=' . $filters['workflow_stage'] : '' ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($leads['current_page'] < $leads['total_pages']): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $leads['current_page'] + 1 ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['workflow_stage']) ? '&workflow_stage=' . $filters['workflow_stage'] : '' ?>">»</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
