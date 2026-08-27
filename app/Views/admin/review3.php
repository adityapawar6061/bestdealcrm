<div class="page-header">
    <h4><i class="bi bi-clipboard2-check me-2"></i>Review 3 - Post Login Decision</h4>
    <small class="text-muted">Cases that completed post-login and need admin decision: send to Underwriting or Reject</small>
</div>

<div class="table-container">
    <form class="row g-2 align-items-end mb-3" method="GET">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, mobile, lead ID..." value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Search</button>
        </div>
        <div class="col-md-2">
            <small class="text-muted"><?= number_format($total ?? 0) ?> leads found</small>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Bank</th>
                    <th>Assigned Agent</th>
                    <th>Stage</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No cases pending review 3.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '') ?></td>
                        <td><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars($lead['assigned_to_name'] ?? 'Unassigned') ?></small></td>
                        <td><span class="badge bg-warning text-dark"><?= humanStatus($lead['workflow_stage']) ?></span></td>
                        <td><small class="text-muted"><?= formatDate($lead['updated_at'] ?? '') ?></small></td>
                        <td><a href="/bestdealcrm/admin/review3/<?= $lead['id'] ?>" class="btn btn-sm btn-primary">Review</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (($totalPages ?? 1) > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Page <?= $page ?> of <?= $totalPages ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
