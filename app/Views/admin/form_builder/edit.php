<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-ui-checks-grid me-2"></i>Edit: <?= htmlspecialchars($form['name']) ?></h4>
        <small class="text-muted">Code: <?= htmlspecialchars($form['code']) ?> | Stage: <?= humanStatus($form['workflow_stage'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/admin/form-builder" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Form Info -->
<div class="table-container mb-4">
    <form id="updateFormInfo">
        <?= csrfField() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Form Name</label>
                <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($form['name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Workflow Stage</label>
                <select name="workflow_stage" class="form-select form-select-sm">
                    <option value="">None</option>
                    <?php foreach (['AGENT_DRAFT','LOGIN_AGENT_DRAFT','POST_LOGIN','UNDERWRITING','DISPATCH'] as $stage): ?>
                        <option value="<?= $stage ?>" <?= ($form['workflow_stage'] ?? '') === $stage ? 'selected' : '' ?>><?= humanStatus($stage) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-primary mt-3" onclick="updateFormInfo()">Update Form Info</button>
    </form>
</div>

<!-- Sections -->
<div id="sections">
    <?php foreach ($form['sections'] as $section): ?>
    <div class="table-container mb-4" id="section-<?= $section['id'] ?>">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-primary mb-0">
                <i class="bi bi-card-list me-1"></i> <?= htmlspecialchars($section['name']) ?>
            </h6>
        </div>

        <!-- Existing Fields -->
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-3">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Field Name</th>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Required</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($section['fields'] as $field): ?>
                    <tr>
                        <td><?= $field['display_order'] ?></td>
                        <td><code class="small"><?= htmlspecialchars($field['field_name']) ?></code></td>
                        <td><?= htmlspecialchars($field['label']) ?></td>
                        <td><span class="badge bg-secondary"><?= $field['type'] ?></span></td>
                        <td><?= $field['required'] ? '<i class="bi bi-check text-success"></i>' : '<i class="bi bi-x text-muted"></i>' ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteField(<?= $field['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Field Form -->
        <div class="bg-light rounded p-3">
            <h6 class="small fw-bold text-muted mb-2">Add New Field</h6>
            <div class="row g-2">
                <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                <div class="col-md-2">
                    <input type="text" name="field_name" class="form-control form-control-sm" placeholder="field_name" required pattern="[a-zA-Z0-9_]+">
                </div>
                <div class="col-md-2">
                    <input type="text" name="label" class="form-control form-control-sm" placeholder="Label" required>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select form-select-sm">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="number">Number</option>
                        <option value="decimal">Decimal</option>
                        <option value="date">Date</option>
                        <option value="email">Email</option>
                        <option value="mobile">Mobile</option>
                        <option value="dropdown">Dropdown</option>
                        <option value="multi-select">Multi-select</option>
                        <option value="radio">Radio</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="file">File Upload</option>
                        <option value="image">Image Upload</option>
                        <option value="url">URL</option>
                        <option value="heading">Heading</option>
                        <option value="readonly">Read-only</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="placeholder" class="form-control form-control-sm" placeholder="Placeholder">
                </div>
                <div class="col-md-1">
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="required" value="1" id="req_<?= $section['id'] ?>">
                        <label class="form-check-label small" for="req_<?= $section['id'] ?>">Req</label>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-success w-100" onclick="addField(this, <?= $section['id'] ?>)">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Add Section -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3">Add New Section</h6>
    <div class="row g-2">
        <div class="col-md-6">
            <input type="text" id="newSectionName" class="form-control form-control-sm" placeholder="Section name (e.g., Personal Information)">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-primary btn-sm w-100" onclick="addSection()">
                <i class="bi bi-plus me-1"></i> Add Section
            </button>
        </div>
    </div>
</div>

<script>
async function updateFormInfo() {
    const form = document.getElementById('updateFormInfo');
    const result = await ajaxPost('/bestdealcrm/admin/form-builder/<?= $form['id'] ?>/update', new FormData(form));
    if (result.success) alert(result.message);
    else if (result.errors) alert(Object.values(result.errors).join('\n'));
}

async function addSection() {
    const name = document.getElementById('newSectionName').value.trim();
    if (!name) return alert('Enter a section name.');
    
    const formData = new FormData();
    formData.append('form_id', <?= $form['id'] ?>);
    formData.append('name', name);
    
    const result = await ajaxPost('/bestdealcrm/admin/form-builder/add-section', formData);
    if (result.success) location.reload();
}

async function addField(btn, sectionId) {
    const row = btn.closest('.row');
    const formData = new FormData();
    formData.append('section_id', sectionId);
    
    row.querySelectorAll('input, select').forEach(el => {
        if (el.type === 'checkbox') {
            if (el.checked) formData.append(el.name, el.value);
        } else if (el.name) {
            formData.append(el.name, el.value);
        }
    });
    
    const result = await ajaxPost('/bestdealcrm/admin/form-builder/add-field', formData);
    if (result.success) location.reload();
    else if (result.error) alert(result.error);
}

async function deleteField(fieldId) {
    if (!confirm('Delete this field?')) return;
    const result = await ajaxPost('/bestdealcrm/admin/form-builder/field/' + fieldId + '/delete', {});
    if (result.success) location.reload();
}
</script>
