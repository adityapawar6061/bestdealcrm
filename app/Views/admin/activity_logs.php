<div class="page-header">
    <h4><i class="bi bi-clock-history me-2"></i>Activity Logs</h4>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Details</th>
                    <th>IP</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No activity logs.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><strong><?= htmlspecialchars($log['user_name'] ?? 'System') ?></strong></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars(str_replace('_', ' ', $log['action'])) ?></span></td>
                        <td><small><?= htmlspecialchars($log['entity_type'] ?? '') ?> <?= $log['entity_id'] ? '#' . $log['entity_id'] : '' ?></small></td>
                        <td>
                            <?php if ($log['old_value'] || $log['new_value']): ?>
                                <small class="text-muted" title="Old: <?= htmlspecialchars($log['old_value'] ?? '') ?> → New: <?= htmlspecialchars($log['new_value'] ?? '') ?>">
                                    <?= truncate($log['new_value'] ?? $log['old_value'] ?? '', 50) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($log['ip_address'] ?? '') ?></small></td>
                        <td><small class="text-muted"><?= formatDate($log['created_at'], 'd M, h:i A') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
