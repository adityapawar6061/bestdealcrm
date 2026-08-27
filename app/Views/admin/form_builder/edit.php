<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-ui-checks-grid me-2"></i>Edit: <?= htmlspecialchars($form['name']) ?></h4>
        <small class="text-muted">Code: <?= htmlspecialchars($form['code']) ?> | Stage: <?= humanStatus($form['workflow_stage'] ?? '') ?></small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-warning btn-sm" onclick="showHiddenFields(<?= $form['id'] ?>)">
            <i class="bi bi-eye-slash me-1"></i> Hidden Fields <?= $hiddenFieldCount > 0 ? '<span class="badge bg-warning text-dark">' . $hiddenFieldCount . '</span>' : '' ?>
        </button>
        <button class="btn btn-danger btn-sm" onclick="promptDeleteForm(<?= $form['id'] ?>)">
            <i class="bi bi-trash me-1"></i> Delete Form
        </button>
        <a href="/bestdealcrm/admin/form-builder" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
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
                    <?php foreach (['AGENT_DRAFT','LOGIN_AGENT_DRAFT','POST_LOGIN','ADMIN_REVIEW_3','UNDERWRITING','ADMIN_REVIEW_4','DISPATCH'] as $stage): ?>
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
                        <th>Options</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($section['fields'] as $field): ?>
                    <?php if (!empty($field['is_hidden'])) continue; ?>
                    <tr id="field-row-<?= $field['id'] ?>">
                        <td><?= $field['display_order'] ?></td>
                        <td>
                            <?php if (($field['field_type'] ?? 'field') === 'heading'): ?>
                                <span class="badge bg-info"><i class="bi bi-type-h1"></i> Heading</span>
                            <?php elseif (($field['field_type'] ?? 'field') === 'subheading'): ?>
                                <span class="badge bg-secondary"><i class="bi bi-type"></i> Sub-heading</span>
                            <?php else: ?>
                                <code class="small"><?= htmlspecialchars($field['field_name']) ?></code>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($field['field_type'] ?? 'field') === 'heading'): ?>
                                <strong class="text-primary" style="font-size:1.1em"><?= htmlspecialchars($field['label']) ?></strong>
                            <?php elseif (($field['field_type'] ?? 'field') === 'subheading'): ?>
                                <strong style="font-size:0.95em;color:#555"><?= htmlspecialchars($field['label']) ?></strong>
                            <?php else: ?>
                                <?= htmlspecialchars($field['label']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($field['field_type'] ?? 'field') === 'field'): ?>
                                <span class="badge bg-secondary"><?= $field['type'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $field['required'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x text-muted"></i>' ?></td>
                        <td>
                            <?php if (in_array($field['type'], ['dropdown', 'multi-select', 'radio'])): ?>
                                <?php if (!empty($field['options'])): ?>
                                    <small class="text-muted"><?= count($field['options']) ?> options</small>
                                    <button class="btn btn-sm btn-outline-primary ms-1" onclick="editFieldOptions(<?= $field['id'] ?>, '<?= htmlspecialchars(addslashes($field['label'])) ?>', '<?= $field['type'] ?>')" title="Edit Options">
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-success" onclick="editFieldOptions(<?= $field['id'] ?>, '<?= htmlspecialchars(addslashes($field['label'])) ?>', '<?= $field['type'] ?>')">
                                        <i class="bi bi-plus"></i> Add Options
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick='editField(<?= json_encode($field) ?>)' title="Edit Field">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="softDeleteField(<?= $field['id'] ?>)" title="Hide Field (Soft Delete)">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Field Form -->
        <div class="bg-light rounded p-3">
            <h6 class="small fw-bold text-muted mb-2"><i class="bi bi-plus-circle me-1"></i> Add New Field / Heading</h6>
            <div class="row g-2">
                <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                <div class="col-md-2">
                    <input type="text" name="field_name" class="form-control form-control-sm" placeholder="field_name" pattern="[a-zA-Z0-9_]+">
                </div>
                <div class="col-md-2">
                    <input type="text" name="label" class="form-control form-control-sm" placeholder="Label" required>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select form-select-sm" onchange="toggleOptionsRow(this); toggleFieldRequired(this)">
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
                        <option value="readonly">Read-only</option>
                        <option value="heading" class="bg-info text-white">── Heading ──</option>
                        <option value="subheading" class="bg-secondary text-white">── Sub-heading ──</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="placeholder" class="form-control form-control-sm" placeholder="Placeholder">
                </div>
                <div class="col-md-1 req-col">
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
            <!-- Options input for dropdown/radio (hidden by default) -->
            <div class="row g-2 mt-2 options-row" style="display:none" id="options-row-<?= $section['id'] ?>">
                <div class="col-md-10">
                    <input type="text" name="options_input" class="form-control form-control-sm" placeholder="Options separated by comma: Option1, Option2, Option3">
                </div>
                <div class="col-md-2">
                    <small class="text-muted">Comma-separated</small>
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

<!-- ===== EDIT FIELD MODAL ===== -->
<div class="modal fade" id="editFieldModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Field</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editFieldId">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Field Name</label>
                    <input type="text" id="editFieldName" class="form-control form-control-sm" readonly>
                    <small class="text-muted">Field names cannot be changed.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Label <span class="text-danger">*</span></label>
                    <input type="text" id="editFieldLabel" class="form-control form-control-sm" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                    <select id="editFieldType" class="form-select form-select-sm">
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
                        <option value="subheading">Sub-heading</option>
                        <option value="readonly">Read-only</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Placeholder</label>
                    <input type="text" id="editFieldPlaceholder" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Default Value</label>
                    <input type="text" id="editFieldDefault" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="editFieldRequired">
                        <label class="form-check-label small fw-semibold" for="editFieldRequired">Required</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="saveFieldEdit()">
                    <i class="bi bi-check me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== OPTIONS EDITOR MODAL ===== -->
<div class="modal fade" id="optionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="optionsModalTitle">Edit Options</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="optionsList"></div>
                <div class="input-group mt-2">
                    <input type="text" id="newOptionLabel" class="form-control form-control-sm" placeholder="New option label">
                    <input type="text" id="newOptionValue" class="form-control form-control-sm" placeholder="Value (auto from label)">
                    <button class="btn btn-success btn-sm" onclick="addOptionRow()"><i class="bi bi-plus"></i></button>
                </div>
                <small class="text-muted">Value is auto-generated from label if left empty.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveFieldOptions()">Save Options</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== HIDDEN FIELDS (Hard Delete) MODAL ===== -->
<div class="modal fade" id="hiddenFieldsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="bi bi-eye-slash me-2"></i>Hidden Fields (Soft Deleted)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">These fields are hidden from the form but their data is preserved. Permanently delete them here.</p>
                <div id="hiddenFieldsList"><p class="text-muted">Loading...</p></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== PASSWORD DELETE FORM MODAL ===== -->
<div class="modal fade" id="deleteFormModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Delete Form</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold text-danger">This will permanently delete this form, all sections, fields, and options.</p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Enter password to confirm:</label>
                    <input type="password" id="deleteConfirmPassword" class="form-control" placeholder="Password">
                </div>
                <input type="hidden" id="deleteFormId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteForm()">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== PASSWORD HARD DELETE MODAL ===== -->
<div class="modal fade" id="hardDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Permanently Delete Field</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold text-danger">This will permanently delete the field and all its submitted data.</p>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Enter password to confirm:</label>
                    <input type="password" id="hardDeletePassword" class="form-control" placeholder="Password">
                </div>
                <input type="hidden" id="hardDeleteFieldId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmHardDelete()">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<script>
var currentFieldId = null;

function toggleOptionsRow(select) {
    var sectionId = select.closest('.row').querySelector('input[name="section_id"]').value;
    var row = document.getElementById('options-row-' + sectionId);
    if (['dropdown', 'multi-select', 'radio'].includes(select.value)) {
        row.style.display = 'flex';
    } else {
        row.style.display = 'none';
    }
}

function toggleFieldRequired(select) {
    var reqCol = select.closest('.row').querySelector('.req-col');
    if (['heading', 'subheading'].includes(select.value)) {
        reqCol.style.visibility = 'hidden';
    } else {
        reqCol.style.visibility = 'visible';
    }
}

async function updateFormInfo() {
    var form = document.getElementById('updateFormInfo');
    var result = await ajaxPost('/bestdealcrm/admin/form-builder/<?= $form['id'] ?>/update', new FormData(form));
    if (result.success) { showToast(result.message, 'success'); }
    else if (result.errors) { showToast(Object.values(result.errors).join('\n'), 'danger'); }
    else { showToast(result.error || 'Update failed.', 'danger'); }
}

async function addSection() {
    var name = document.getElementById('newSectionName').value.trim();
    if (!name) { showToast('Enter a section name.', 'warning'); return; }
    var formData = new FormData();
    formData.append('form_id', <?= $form['id'] ?>);
    formData.append('name', name);
    var result = await ajaxPost('/bestdealcrm/admin/form-builder/add-section', formData);
    if (result.success) location.reload();
    else showToast(result.error || 'Failed.', 'danger');
}

async function addField(btn, sectionId) {
    var row = btn.closest('.row');
    var formData = new FormData();
    formData.append('section_id', sectionId);

    row.querySelectorAll('input, select').forEach(function(el) {
        if (el.type === 'checkbox') {
            if (el.checked) formData.append(el.name, el.value);
        } else if (el.name && el.name !== 'options_input') {
            formData.append(el.name, el.value);
        }
    });

    var typeSelect = row.querySelector('select[name="type"]');
    if (['dropdown', 'multi-select', 'radio'].includes(typeSelect.value)) {
        var optionsRow = document.getElementById('options-row-' + sectionId);
        var optionsInput = optionsRow.querySelector('input[name="options_input"]');
        if (optionsInput && optionsInput.value.trim()) {
            var options = optionsInput.value.split(',').map(function(o) { return o.trim(); }).filter(function(o) { return o; });
            options.forEach(function(opt) {
                formData.append('options[]', opt);
            });
        }
    }

    var result = await ajaxPost('/bestdealcrm/admin/form-builder/add-field', formData);
    if (result.success) location.reload();
    else showToast(result.error || 'Failed to add field.', 'danger');
}

// Soft delete (hide field)
function softDeleteField(fieldId) {
    if (!confirm('Hide this field? It will be moved to Hidden Fields where you can restore or permanently delete it.')) return;
    ajaxPost('/bestdealcrm/admin/form-builder/field/' + fieldId + '/delete', {}).then(function(result) {
        if (result.success) {
            showToast(result.message, 'success');
            location.reload();
        } else {
            showToast(result.error || 'Failed.', 'danger');
        }
    });
}

// Show hidden fields
async function showHiddenFields(formId) {
    var modal = new bootstrap.Modal(document.getElementById('hiddenFieldsModal'));
    document.getElementById('hiddenFieldsList').innerHTML = '<p class="text-muted">Loading...</p>';
    modal.show();

    var result = await ajaxGet('/bestdealcrm/admin/form-builder/' + formId + '/hidden-fields');
    var container = document.getElementById('hiddenFieldsList');

    if (!result || !result.success || result.fields.length === 0) {
        container.innerHTML = '<div class="text-center py-3"><i class="bi bi-check-circle text-success me-2"></i>No hidden fields.</div>';
        return;
    }

    var html = '<table class="table table-sm table-bordered"><thead class="table-light"><tr>';
    html += '<th>Section</th><th>Field Name</th><th>Label</th><th>Type</th><th>Actions</th>';
    html += '</tr></thead><tbody>';
    result.fields.forEach(function(f) {
        html += '<tr>';
        html += '<td>' + escapeHtml(f.section) + '</td>';
        html += '<td><code>' + escapeHtml(f.field_name) + '</code></td>';
        html += '<td>' + escapeHtml(f.label) + '</td>';
        html += '<td><span class="badge bg-secondary">' + f.type + '</span></td>';
        html += '<td>';
        html += '<button class="btn btn-sm btn-outline-success me-1" onclick="restoreField(' + f.id + ')" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button> ';
        html += '<button class="btn btn-sm btn-outline-danger" onclick="promptHardDelete(' + f.id + ')" title="Delete Permanently"><i class="bi bi-trash"></i></button>';
        html += '</td></tr>';
    });
    html += '</tbody></table>';
    container.innerHTML = html;
}

async function restoreField(fieldId) {
    var result = await ajaxPost('/bestdealcrm/admin/form-builder/field/' + fieldId + '/restore', {});
    if (result.success) {
        showToast(result.message, 'success');
        location.reload();
    } else {
        showToast(result.error || 'Failed.', 'danger');
    }
}

function promptHardDelete(fieldId) {
    document.getElementById('hardDeleteFieldId').value = fieldId;
    document.getElementById('hardDeletePassword').value = '';
    bootstrap.Modal.getInstance(document.getElementById('hiddenFieldsModal')).hide();
    new bootstrap.Modal(document.getElementById('hardDeleteModal')).show();
}

async function confirmHardDelete() {
    var fieldId = document.getElementById('hardDeleteFieldId').value;
    var password = document.getElementById('hardDeletePassword').value;
    if (!password) { showToast('Enter the password.', 'warning'); return; }

    var formData = new FormData();
    formData.append('password', password);

    var result = await ajaxPost('/bestdealcrm/admin/form-builder/field/' + fieldId + '/hard-delete', formData);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('hardDeleteModal')).hide();
        location.reload();
    } else {
        showToast(result.error || 'Failed.', 'danger');
    }
}

// ===== EDIT FIELD =====
function editField(field) {
    document.getElementById('editFieldId').value = field.id;
    document.getElementById('editFieldName').value = field.field_name;
    document.getElementById('editFieldLabel').value = field.label;
    document.getElementById('editFieldType').value = field.type;
    document.getElementById('editFieldPlaceholder').value = field.placeholder || '';
    document.getElementById('editFieldDefault').value = field.default_value || '';
    document.getElementById('editFieldRequired').checked = field.required == 1;
    new bootstrap.Modal(document.getElementById('editFieldModal')).show();
}

async function saveFieldEdit() {
    var fieldId = document.getElementById('editFieldId').value;
    var formData = new FormData();
    formData.append('label', document.getElementById('editFieldLabel').value.trim());
    formData.append('type', document.getElementById('editFieldType').value);
    formData.append('placeholder', document.getElementById('editFieldPlaceholder').value.trim());
    formData.append('default_value', document.getElementById('editFieldDefault').value.trim());
    formData.append('required', document.getElementById('editFieldRequired').checked ? '1' : '0');

    if (!formData.get('label')) {
        showToast('Label is required.', 'warning');
        return;
    }

    var result = await ajaxPost('/bestdealcrm/admin/form-builder/field/' + fieldId + '/update', formData);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('editFieldModal')).hide();
        location.reload();
    } else {
        showToast(result.error || 'Failed to update field.', 'danger');
    }
}

// ===== OPTIONS EDITOR =====
async function editFieldOptions(fieldId, label, type) {
    currentFieldId = fieldId;
    document.getElementById('optionsModalTitle').textContent = 'Edit Options: ' + label;
    document.getElementById('optionsList').innerHTML = '<p class="text-muted">Loading...</p>';
    new bootstrap.Modal(document.getElementById('optionsModal')).show();

    var result = await ajaxPost('/bestdealcrm/admin/form-builder/field/' + fieldId + '/options', {});
    var container = document.getElementById('optionsList');

    if (result.success && result.options.length > 0) {
        renderOptions(result.options);
    } else {
        container.innerHTML = '<p class="text-muted small">No options yet. Add some below.</p>';
    }
}

function renderOptions(options) {
    var html = '';
    options.forEach(function(opt, i) {
        html += '<div class="input-group mb-2 option-row">';
        html += '<span class="input-group-text bg-light small">' + (i + 1) + '</span>';
        html += '<input type="text" class="form-control form-control-sm opt-label" value="' + escapeHtml(opt.label) + '" placeholder="Label">';
        html += '<input type="text" class="form-control form-control-sm opt-value" value="' + escapeHtml(opt.value) + '" placeholder="Value" style="max-width:150px">';
        html += '<button class="btn btn-outline-danger btn-sm" onclick="this.closest(\'.option-row\').remove()"><i class="bi bi-x"></i></button>';
        html += '</div>';
    });
    document.getElementById('optionsList').innerHTML = html;
}

function addOptionRow() {
    var label = document.getElementById('newOptionLabel').value.trim();
    var value = document.getElementById('newOptionValue').value.trim() || label.toLowerCase().replace(/[^a-z0-9]/g, '_');
    if (!label) { showToast('Enter an option label.', 'warning'); return; }

    var count = document.querySelectorAll('#optionsList .option-row').length + 1;
    var html = '<div class="input-group mb-2 option-row">';
    html += '<span class="input-group-text bg-light small">' + count + '</span>';
    html += '<input type="text" class="form-control form-control-sm opt-label" value="' + escapeHtml(label) + '" placeholder="Label">';
    html += '<input type="text" class="form-control form-control-sm opt-value" value="' + escapeHtml(value) + '" placeholder="Value" style="max-width:150px">';
    html += '<button class="btn btn-outline-danger btn-sm" onclick="this.closest(\'.option-row\').remove()"><i class="bi bi-x"></i></button>';
    html += '</div>';
    document.getElementById('optionsList').insertAdjacentHTML('beforeend', html);
    document.getElementById('newOptionLabel').value = '';
    document.getElementById('newOptionValue').value = '';
}

async function saveFieldOptions() {
    var rows = document.querySelectorAll('.option-row');
    var options = [];
    rows.forEach(function(row) {
        var label = row.querySelector('.opt-label').value.trim();
        var value = row.querySelector('.opt-value').value.trim();
        if (label) options.push({ label: label, value: value || label });
    });

    var formData = new FormData();
    options.forEach(function(opt, i) {
        formData.append('options[' + i + '][label]', opt.label);
        formData.append('options[' + i + '][value]', opt.value);
    });

    var result = await ajaxPost('/bestdealcrm/admin/form-builder/field/' + currentFieldId + '/options/save', formData);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('optionsModal')).hide();
        location.reload();
    } else {
        showToast(result.error || 'Failed to save options.', 'danger');
    }
}

// ===== PASSWORD-PROTECTED DELETE =====
function promptDeleteForm(formId) {
    document.getElementById('deleteFormId').value = formId;
    document.getElementById('deleteConfirmPassword').value = '';
    new bootstrap.Modal(document.getElementById('deleteFormModal')).show();
}

async function confirmDeleteForm() {
    var formId = document.getElementById('deleteFormId').value;
    var password = document.getElementById('deleteConfirmPassword').value;
    if (!password) { showToast('Enter the password.', 'warning'); return; }

    var formData = new FormData();
    formData.append('form_id', formId);
    formData.append('confirm_password', password);

    var result = await ajaxPost('/bestdealcrm/admin/form-builder/delete-with-password', formData);
    if (result.success) {
        showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('deleteFormModal')).hide();
        setTimeout(function() { window.location.href = '/bestdealcrm/admin/form-builder'; }, 1000);
    } else {
        showToast(result.error || 'Failed.', 'danger');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
