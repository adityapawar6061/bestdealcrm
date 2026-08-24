<div class="page-header"><h4><i class="bi bi-truck me-2"></i>Dispatch Cases</h4></div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Customer</th><th>Mobile</th><th>Bank</th><th>Stage</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($leads['data'])): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No cases found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></small></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td>
                            <?php if ($lead['workflow_stage'] === 'DISPATCH'): ?>
                                <a href="/bestdealcrm/dispatch/cases/<?= $lead['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-check-circle me-1"></i>Complete</a>
                            <?php else: ?>
                                <a href="/bestdealcrm/dispatch/cases/<?= $lead['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
