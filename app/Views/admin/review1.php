<div class="page-header">
    <h4><i class="bi bi-clipboard-check me-2"></i>Admin Review (Stage 1)</h4>
    <p class="text-muted small">Leads submitted by agents for your review</p>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Bank</th>
                    <th>Agent</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No leads pending review.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></small></td>
                        <td><small><?= htmlspecialchars($lead['assigned_to_name'] ?? '-') ?></small></td>
                        <td><small class="text-muted"><?= formatDate($lead['updated_at'], 'd M, h:i A (IST)') ?></small></td>
                        <td>
                            <a href="/bestdealcrm/admin/review1/<?= $lead['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye me-1"></i>Review
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
