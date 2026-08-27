<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-table me-2"></i>Table Builder</h4>
    <a href="/bestdealcrm/admin/table-builder/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Create Table
    </a>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Table Name</th>
                    <th>Display Name</th>
                    <th>Description</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tables)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No dynamic tables created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($tables as $table): ?>
                    <tr>
                        <td><?= $table['id'] ?></td>
                        <td><code><?= htmlspecialchars($table['name']) ?></code></td>
                        <td><strong><?= htmlspecialchars($table['display_name']) ?></strong></td>
                        <td><small class="text-muted"><?= htmlspecialchars(truncate($table['description'] ?? '', 50)) ?></small></td>
                        <td><small class="text-muted"><?= formatDate($table['created_at'], 'd M Y') ?></small></td>
                        <td>
                            <a href="/bestdealcrm/admin/table-builder/<?= $table['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
