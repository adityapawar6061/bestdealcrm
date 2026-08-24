<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-list-ul me-2"></i>Team Leads</h4>
    <a href="/bestdealcrm/team-leader/dashboard" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Customer</th><th>Mobile</th><th>Bank</th><th>Assigned To</th><th>Stage</th><th>Created</th></tr>
            </thead>
            <tbody>
                <?php if (empty($leads['data'])): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No team leads found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></small></td>
                        <td><small><?= htmlspecialchars($lead['assigned_to_name'] ?? '-') ?></small></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td><small class="text-muted"><?= formatDate($lead['created_at']) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Pagination -->
    <?php if ($leads['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= $leads['from'] ?? 0 ?>-<?= $leads['to'] ?? 0 ?> of <?= number_format($leads['total']) ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $leads['total_pages']; $i++): ?>
                <li class="page-item <?= $i == $leads['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
