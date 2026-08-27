<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-file-earmark-bar-graph me-2"></i>Reports & Export</h4>
        <small class="text-muted">Create templates and export lead data to Excel</small>
    </div>
    <a href="/bestdealcrm/admin/reports/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Template
    </a>
</div>

<div class="table-container">
    <?php if (empty($templates)): ?>
        <div class="text-center py-5">
            <i class="bi bi-file-earmark-bar-graph display-3 text-muted"></i>
            <h5 class="mt-3 text-muted">No Report Templates</h5>
            <p class="text-muted">Create your first template to start exporting lead data.</p>
            <a href="/bestdealcrm/admin/reports/create" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i> Create Template
            </a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($templates as $tpl): ?>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="card-title fw-bold mb-0">
                                <i class="bi bi-file-earmark-excel text-success me-1"></i>
                                <?= htmlspecialchars($tpl['name']) ?>
                            </h6>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/bestdealcrm/admin/reports/<?= $tpl['id'] ?>/edit"><i class="bi bi-pencil me-1"></i> Edit</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteTemplate(<?= $tpl['id'] ?>)"><i class="bi bi-trash me-1"></i> Delete</a></li>
                                </ul>
                            </div>
                        </div>
                        <?php if (!empty($tpl['description'])): ?>
                            <p class="card-text small text-muted"><?= htmlspecialchars($tpl['description']) ?></p>
                        <?php endif; ?>
                        <?php
                        $cols = json_decode($tpl['columns_config'], true) ?? [];
                        $colLabels = array_column($cols, 'label');
                        ?>
                        <div class="mb-2">
                            <small class="text-muted"><?= count($cols) ?> columns:</small>
                            <div class="mt-1">
                                <?php foreach (array_slice($colLabels, 0, 5) as $cl): ?>
                                    <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars($cl) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($colLabels) > 5): ?>
                                    <span class="badge bg-secondary mb-1">+<?= count($colLabels) - 5 ?> more</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <small class="text-muted">
                            Created by <?= htmlspecialchars($tpl['created_by_name'] ?? 'Admin') ?>
                            · <?= formatDate($tpl['created_at']) ?>
                        </small>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="/bestdealcrm/admin/reports/<?= $tpl['id'] ?>/generate" class="btn btn-success btn-sm w-100">
                            <i class="bi bi-download me-1"></i> Generate Report
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteTemplate(id) {
    if (!confirm('Delete this template? This cannot be undone.')) return;
    var formData = new FormData();
    formData.append('template_id', id);
    ajaxPost('/bestdealcrm/admin/reports/delete', formData).then(function(result) {
        if (result && result.success) {
            showToast('Template deleted.', 'success');
            setTimeout(function() { location.reload(); }, 500);
        } else {
            showToast(result.error || 'Error deleting.', 'danger');
        }
    });
}
</script>
