<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-credit-card me-2"></i>CIBIL Requests</h4>
    <span class="badge bg-danger"><?= number_format($total) ?> total</span>
</div>

<div class="table-container mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, PAN, mobile, agent..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="replied" <?= $status === 'replied' ? 'selected' : '' ?>>Replied</option>
            </select>
        </div>
        <div class="col-md-1"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i></button></div>
        <?php if ($search || $status): ?>
            <div class="col-md-1"><a href="<?= BASE_URL ?>/admin/cibil-requests" class="btn btn-sm btn-outline-secondary w-100">Clear</a></div>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0" style="min-width:1200px">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Agent</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Loan Type</th>
                    <th>Monthly Salary</th>
                    <th>Loan Requirement</th>
                    <th>Actual CIBIL</th>
                    <th>Main Status</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="12" class="text-center text-muted py-4">No CIBIL requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><small class="text-muted"><?= date('d-m-Y', strtotime($r['created_at'])) ?></small></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($r['agent_name'] ?? 'Admin') ?></span></td>
                            <td><strong><?= htmlspecialchars($r['name_as_pan']) ?></strong></td>
                            <td><?= htmlspecialchars($r['mobile']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($r['loan_type']) ?></span></td>
                            <td>₹<?= number_format((int)str_replace(['₹',','], '', $r['monthly_salary'])) ?></td>
                            <td>₹<?= number_format((int)str_replace(['₹',','], '', $r['loan_requirement'])) ?></td>
                            <td>
                                <?php if ($r['cibil_score_actual']): ?>
                                    <span class="badge bg-<?= $r['cibil_score_actual'] >= 750 ? 'success' : ($r['cibil_score_actual'] >= 650 ? 'warning' : 'danger') ?>">
                                        <?= $r['cibil_score_actual'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($r['main_status'] ?? 'N/A') ?></small></td>
                            <td>
                                <span class="badge bg-<?= $r['status'] === 'replied' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/cibil-verify/<?= $r['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> Verify
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
    $totalPages = ceil($total / $perPage);
    if ($totalPages > 1):
    ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= number_format($total) ?></small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
