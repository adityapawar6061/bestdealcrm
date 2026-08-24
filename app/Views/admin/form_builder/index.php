<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-ui-checks-grid me-2"></i>Form Builder</h4>
    <a href="/bestdealcrm/admin/form-builder/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Create Form
    </a>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Form Name</th>
                    <th>Code</th>
                    <th>Workflow Stage</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($forms)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No forms created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($forms as $form): ?>
                    <tr>
                        <td><?= $form['id'] ?></td>
                        <td><strong><?= htmlspecialchars($form['name']) ?></strong></td>
                        <td><code><?= htmlspecialchars($form['code']) ?></code></td>
                        <td><small><?= humanStatus($form['workflow_stage'] ?? '') ?></small></td>
                        <td><span class="badge bg-<?= $form['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($form['status']) ?></span></td>
                        <td><small class="text-muted"><?= formatDate($form['created_at'], 'd M Y') ?></small></td>
                        <td>
                            <a href="/bestdealcrm/admin/form-builder/<?= $form['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
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
