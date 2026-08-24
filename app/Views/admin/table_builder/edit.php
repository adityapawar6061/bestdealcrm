<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-table me-2"></i>Edit Table: <?= htmlspecialchars($table['display_name']) ?></h4>
        <small class="text-muted">Internal name: <?= htmlspecialchars($table['name']) ?></small>
    </div>
    <a href="/bestdealcrm/admin/table-builder" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Existing Columns -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3">Columns</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order</th>
                    <th>Field Name</th>
                    <th>Label</th>
                    <th>Data Type</th>
                    <th>Required</th>
                    <th>Unique</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($columns)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">No columns added yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($columns as $col): ?>
                    <tr>
                        <td><?= $col['display_order'] ?></td>
                        <td><code class="small"><?= htmlspecialchars($col['field_name']) ?></code></td>
                        <td><?= htmlspecialchars($col['label']) ?></td>
                        <td><span class="badge bg-secondary"><?= $col['data_type'] ?></span></td>
                        <td><?= $col['required'] ? '<i class="bi bi-check text-success"></i>' : '' ?></td>
                        <td><?= $col['unique'] ? '<i class="bi bi-check text-info"></i>' : '' ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteColumn(<?= $col['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Column Form -->
<div class="table-container">
    <h6 class="fw-bold mb-3">Add New Column</h6>
    <form id="addColumnForm">
        <?= csrfField() ?>
        <input type="hidden" name="table_id" value="<?= $table['id'] ?>">
        <div class="row g-2">
            <div class="col-md-2">
                <input type="text" name="field_name" class="form-control form-control-sm" placeholder="field_name" required pattern="[a-zA-Z0-9_]+">
            </div>
            <div class="col-md-2">
                <input type="text" name="label" class="form-control form-control-sm" placeholder="Label" required>
            </div>
            <div class="col-md-2">
                <select name="data_type" class="form-select form-select-sm">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="number">Number</option>
                    <option value="decimal">Decimal</option>
                    <option value="date">Date</option>
                    <option value="datetime">DateTime</option>
                    <option value="email">Email</option>
                    <option value="mobile">Mobile</option>
                    <option value="dropdown">Dropdown</option>
                    <option value="boolean">Boolean</option>
                    <option value="url">URL</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="default_value" class="form-control form-control-sm" placeholder="Default">
            </div>
            <div class="col-md-1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="required" value="1" id="colReq">
                    <label class="form-check-label small" for="colReq">Req</label>
                </div>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-success btn-sm w-100" onclick="addColumn()">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
async function addColumn() {
    const form = document.getElementById('addColumnForm');
    const result = await ajaxPost('/bestdealcrm/admin/table-builder/add-column', new FormData(form));
    if (result.success) location.reload();
    else if (result.error) alert(result.error);
}

async function deleteColumn(colId) {
    if (!confirm('Delete this column?')) return;
    const result = await ajaxPost('/bestdealcrm/admin/table-builder/column/' + colId + '/delete', {});
    if (result.success) location.reload();
}
</script>
