<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-table me-2"></i>All Data Entries</h4>
    <span class="badge bg-primary"><?= number_format($total) ?> total</span>
</div>

<div class="table-container mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search mobile, name, city..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
            <select name="disposition" class="form-select form-select-sm">
                <option value="">All Dispositions</option>
                <?php foreach (['RNR','Disconnected','Not Interested','Call Back','Follow Up','Not Eligible','Self Employed','Lead','DNC'] as $d): ?>
                    <option value="<?= $d ?>" <?= $dispositionFilter === $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="user_id" class="form-select form-select-sm">
                <option value="">All Users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $userFilter == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i></button></div>
        <?php if ($search || $dispositionFilter || $userFilter): ?>
            <div class="col-md-1"><a href="<?= BASE_URL ?>/admin/data-view" class="btn btn-sm btn-outline-secondary w-100">Clear</a></div>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Created</th>
                    <th>User</th>
                    <th>Mobile No</th>
                    <th>Customer Name</th>
                    <th>City</th>
                    <th>Salary</th>
                    <th>Loan Amount</th>
                    <th>Disposition</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No entries found.</td></tr>
                <?php else: ?>
                    <?php foreach ($entries as $e): ?>
                        <tr>
                            <td><small class="text-muted"><?= date('d M Y, h:i A', strtotime($e['created_at'])) ?></small></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($e['user_name'] ?? 'N/A') ?></span></td>
                            <td><?= htmlspecialchars($e['mobile_no']) ?></td>
                            <td><strong><?= htmlspecialchars($e['customer_name']) ?></strong></td>
                            <td><small><?= htmlspecialchars($e['city']) ?></small></td>
                            <td>₹<?= number_format((int)str_replace(['₹',','], '', $e['salary'])) ?></td>
                            <td>₹<?= number_format((int)str_replace(['₹',','], '', $e['loan_amount'])) ?></td>
                            <td>
                                <?php
                                $badgeClass = match($e['disposition']) {
                                    'Follow Up' => 'bg-primary',
                                    'Not Eligible' => 'bg-danger',
                                    'Disconnected' => 'bg-secondary',
                                    'Not Interested' => 'bg-warning text-dark',
                                    'Call Back' => 'bg-info text-dark',
                                    'Lead' => 'bg-success',
                                    'Self Employed' => 'bg-dark',
                                    default => 'bg-light text-dark',
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($e['disposition']) ?></span>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($e['remarks'] ?? '') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $totalPages = ceil($total / $perPage); if ($totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= number_format($total) ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&disposition=<?= urlencode($dispositionFilter) ?>&user_id=<?= urlencode($userFilter) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
