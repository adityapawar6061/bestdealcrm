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

<!-- View Tabs -->
<ul class="nav nav-tabs mb-4" id="builderTabs">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabBuilder" type="button">
            <i class="bi bi-pencil-square me-1"></i> Builder
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPreview" type="button" onclick="loadPreview()">
            <i class="bi bi-eye me-1"></i> Preview & Layout
        </button>
    </li>
</ul>

<div class="tab-content">
<div class="tab-pane fade show active" id="tabBuilder">

<!-- Sections -->
<div id="sections">
    <?php foreach ($form['sections'] as $section): ?>
    <div class="table-container mb-4" id="section-<?= $section['id'] ?>" data-column-layout="<?= $section['column_layout'] ?? 1 ?>">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-primary mb-0">
                <i class="bi bi-card-list me-1"></i> <?= htmlspecialchars($section['name']) ?>
            </h6>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteSection(<?= $section['id'] ?>, '<?= htmlspecialchars(addslashes($section['name'])) ?>')" title="Delete this section and all its fields">
                <i class="bi bi-trash me-1"></i> Delete Section
            </button>
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
            <h6 class="small fw-bold text-muted mb-2"><i class="bi bi-plus-circle me-1"></i> Add New Item</h6>
            <div class="row g-2 align-items-end">
                <input type="hidden" name="section_id" value="<?= $section['id'] ?>">
                <!-- Dropdown to select: New Field, Heading, or Sub-heading -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Type</label>
                    <select name="item_type" class="form-select form-select-sm" id="itemType_<?= $section['id'] ?>" onchange="toggleAddFieldType(<?= $section['id'] ?>)">
                        <option value="field">📝 New Field</option>
                        <option value="heading">📌 Heading</option>
                        <option value="subheading">➖ Sub-heading</option>
                    </select>
                </div>
                <!-- Field type (only for "New Field") -->
                <div class="col-md-2 fieldTypeCol" id="fieldTypeCol_<?= $section['id'] ?>">
                    <label class="form-label small fw-semibold mb-1">Field Type</label>
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
                    </select>
                </div>
                <!-- Field name -->
                <div class="col-md-2" id="fieldNameCol_<?= $section['id'] ?>">
                    <label class="form-label small fw-semibold mb-1">Field Name</label>
                    <input type="text" name="field_name" class="form-control form-control-sm" placeholder="field_name" pattern="[a-zA-Z0-9_]+">
                </div>
                <!-- Label -->
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Label</label>
                    <input type="text" name="label" class="form-control form-control-sm" placeholder="Label" required>
                </div>
                <!-- Placeholder (only for fields) -->
                <div class="col-md-1 fieldTypeCol" id="placeholderCol_<?= $section['id'] ?>">
                    <label class="form-label small fw-semibold mb-1">Placeholder</label>
                    <input type="text" name="placeholder" class="form-control form-control-sm" placeholder="Ph.">
                </div>
                <!-- Required (only for fields) -->
                <div class="col-md-1 req-col fieldTypeCol" id="reqCol_<?= $section['id'] ?>">
                    <label class="form-label small fw-semibold mb-1">&nbsp;</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="required" value="1" id="req_<?= $section['id'] ?>">
                        <label class="form-check-label small" for="req_<?= $section['id'] ?>">Req</label>
                    </div>
                </div>
                <!-- Add button -->
                <div class="col-md-1">
                    <label class="form-label small fw-semibold mb-1">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-success w-100" onclick="addField(this, <?= $section['id'] ?>)">
                        <i class="bi bi-plus"></i> Add
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

</div><!-- /tabBuilder -->

<!-- ===== PREVIEW & LAYOUT TAB ===== -->
<div class="tab-pane fade" id="tabPreview">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-eye me-1"></i> Form Preview — Drag sections to reorder, change column layout</h6>
        <div>
            <button class="btn btn-sm btn-outline-secondary" onclick="loadPreview()"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</button>
            <button class="btn btn-sm btn-primary ms-2" onclick="saveAllPreview()" id="saveAllBtn"><i class="bi bi-check-lg me-1"></i> Save All</button>
            <span class="badge bg-success ms-2" id="previewSavedBadge" style="display:none">✓ Saved</span>
        </div>
    </div>
    <p class="text-muted small mb-3">Drag sections up/down using the ⋮⋮ handle. Drag fields within sections. Click column buttons to change layout. Click <strong>Save All</strong> to save changes.</p>
    <div id="previewContainer" class="bg-light border rounded p-3">
        <p class="text-muted text-center py-4">Click the Preview tab to load...</p>
    </div>
</div>

</div><!-- /tab-content -->

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

// Toggle field-type-specific columns based on item type dropdown
function toggleAddFieldType(sectionId) {
    var sel = document.getElementById('itemType_' + sectionId);
    var val = sel.value;
    var fieldTypeCol = document.getElementById('fieldTypeCol_' + sectionId);
    var fieldNameCol = document.getElementById('fieldNameCol_' + sectionId);
    var placeholderCol = document.getElementById('placeholderCol_' + sectionId);
    var reqCol = document.getElementById('reqCol_' + sectionId);
    if (val === 'field') {
        fieldTypeCol.style.display = '';
        fieldNameCol.style.display = '';
        placeholderCol.style.display = '';
        reqCol.style.display = '';
    } else {
        fieldTypeCol.style.display = 'none';
        fieldNameCol.style.display = 'none';
        placeholderCol.style.display = 'none';
        reqCol.style.display = 'none';
    }
}

// Delete section with password confirmation
function deleteSection(sectionId, sectionName) {
    if (!confirm('Delete section "' + sectionName + '" and ALL its fields? This cannot be undone.')) return;
    var password = prompt('Enter password to delete this section:');
    if (!password) return;
    var formData = new FormData();
    formData.append('section_id', sectionId);
    formData.append('password', password);
    ajaxPost('/bestdealcrm/admin/form-builder/delete-section', formData).then(function(result) {
        if (result && result.success) {
            showToast(result.message, 'success');
            location.reload();
        } else {
            showToast(result.error || 'Delete failed.', 'danger');
        }
    }).catch(function(err) {
        showToast('Server error: ' + err.message, 'danger');
    });
}

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
    var row = btn.closest('.bg-light');
    var formData = new FormData();
    formData.append('section_id', sectionId);

    // Get item type from the new dropdown
    var itemTypeSelect = document.getElementById('itemType_' + sectionId);
    var itemType = itemTypeSelect ? itemTypeSelect.value : 'field';

    // If heading or subheading, set type to that and use label as field_name
    if (itemType === 'heading' || itemType === 'subheading') {
        var labelInput = row.querySelector('input[name="label"]');
        formData.append('type', itemType);
        formData.append('field_type', itemType);
        formData.append('label', labelInput.value.trim());
        if (!formData.get('field_name')) {
            formData.append('field_name', 'heading_' + Math.random().toString(36).substr(2, 8));
        }
    } else {
        // Normal field - collect all inputs
        row.querySelectorAll('input, select').forEach(function(el) {
            if (el.type === 'checkbox') {
                if (el.checked) formData.append(el.name, el.value);
            } else if (el.name && el.name !== 'options_input' && el.name !== 'item_type') {
                formData.append(el.name, el.value);
            }
        });
    }

    // Add options for dropdown/radio
    var typeVal = formData.get('type') || '';
    if (['dropdown', 'multi-select', 'radio'].includes(typeVal)) {
        var optionsRow = document.getElementById('options-row-' + sectionId);
        var optionsInput = optionsRow.querySelector('input[name="options_input"]');
        if (optionsInput && optionsInput.value.trim()) {
            var options = optionsInput.value.split(',').map(function(o) { return o.trim(); }).filter(function(o) { return o; });
            options.forEach(function(opt) {
                formData.append('options[]', opt);
            });
        }
    }

    if (!formData.get('label')) {
        showToast('Label is required.', 'warning');
        return;
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

// Hidden fields data embedded from server (no AJAX needed)
var hiddenFieldsData = <?= json_encode($hiddenFieldsData ?? []) ?>;

// Show hidden fields
function showHiddenFields(formId) {
    var modal = new bootstrap.Modal(document.getElementById('hiddenFieldsModal'));
    var container = document.getElementById('hiddenFieldsList');

    if (!hiddenFieldsData || hiddenFieldsData.length === 0) {
        container.innerHTML = '<div class="text-center py-3"><i class="bi bi-check-circle text-success me-2"></i>No hidden fields.</div>';
        modal.show();
        return;
    }

    var html = '<table class="table table-sm table-bordered"><thead class="table-light"><tr>';
    html += '<th>Section</th><th>Field Name</th><th>Label</th><th>Type</th><th>Actions</th>';
    html += '</tr></thead><tbody>';
    hiddenFieldsData.forEach(function(f) {
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
    modal.show();
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

// ===== PREVIEW & LAYOUT =====
var previewSections = [];

function loadPreview() {
    var container = document.getElementById('previewContainer');
    container.innerHTML = '<p class="text-center text-muted py-3"><i class="bi bi-hourglass-split"></i> Loading preview...</p>';

    // Build preview data from the DOM sections
    previewSections = [];
    document.querySelectorAll('#sections > .table-container').forEach(function(secEl) {
        var secId = parseInt(secEl.id.replace('section-', ''));
        var secName = secEl.querySelector('h6.fw-bold')?.textContent?.trim() || 'Section';
        var fields = [];
        secEl.querySelectorAll('tbody tr').forEach(function(row) {
            var id = parseInt(row.id.replace('field-row-', ''));
            if (!id) return;
            var cells = row.querySelectorAll('td');
            var fieldType = cells[2]?.textContent?.trim() || '';
            var fieldName = cells[1]?.querySelector('code')?.textContent?.trim() || '';
            var label = cells[2]?.textContent?.trim() || '';
            var type = cells[3]?.querySelector('.badge')?.textContent?.trim() || '';
            var required = cells[4]?.querySelector('.bi-check-circle-fill') ? true : false;
            var optionsText = cells[5]?.textContent?.trim() || '';
            fields.push({
                id: id,
                field_name: fieldName,
                label: label,
                type: type,
                required: required,
                optionsText: optionsText,
            });
        });
        // Try to read column_layout from section data attribute
        var layout = parseInt(secEl.getAttribute('data-column-layout')) || 1;
        previewSections.push({
            id: secId,
            name: secName,
            layout: layout,
            fields: fields,
        });
    });
    renderPreview();
}

function renderPreview() {
    var container = document.getElementById('previewContainer');
    var html = '';
    previewSections.forEach(function(sec, idx) {
        var layout = sec.layout || 1;
        var colClass = layout === 3 ? 'col-md-4' : (layout === 2 ? 'col-md-6' : 'col-md-12');
        html += '<div class="card mb-3 preview-section" data-sec-idx="' + idx + '" draggable="true" ondragstart="dragSection(event,' + idx + ')" ondragover="event.preventDefault()" ondrop="dropSection(event,' + idx + ')">';
        html += '<div class="card-header bg-white d-flex justify-content-between align-items-center" style="cursor:move;border-left:4px solid #0d6efd">';
        html += '<div><span class="me-2 text-muted" title="Drag to reorder">⋮⋮</span><strong class="small">' + escapeHtml(sec.name) + '</strong>';
        html += ' <span class="badge bg-secondary ms-1">' + sec.fields.length + ' fields</span></div>';
        html += '<div class="btn-group btn-group-sm">';
        for (var c = 1; c <= 3; c++) {
            html += '<button class="btn btn-outline-' + (layout === c ? 'primary' : 'secondary') + '" onclick="setSectionLayout(' + idx + ',' + c + ')" title="' + c + ' column' + (c > 1 ? 's' : '') + '">' + c + ' Col</button>';
        }
        html += '</div>';
        html += '</div>';
        html += '<div class="card-body">';
        if (sec.fields.length === 0) {
            html += '<p class="text-muted small text-center mb-0">No visible fields</p>';
        } else {
            html += '<div class="row g-3">';
            sec.fields.forEach(function(f, fIdx) {
                var fieldHtml = '';
                if (!f.field_name && f.label && (f.label === 'heading' || f.label === '')) {
                    // Heading
                    fieldHtml = '<div class="col-12"><h6 class="text-primary fw-bold border-bottom pb-1 mt-2">' + escapeHtml(f.label) + '</h6></div>';
                } else if (!f.type) {
                    fieldHtml = '<div class="col-12"><h6 class="fw-bold mt-2" style="font-size:0.9rem;color:#555">' + escapeHtml(f.label) + '</h6></div>';
                } else {
                    var reqStar = f.required ? ' <span class="text-danger">*</span>' : '';
                    fieldHtml = '<div class="' + colClass + '" draggable="true" ondragstart="dragField(event,' + idx + ',' + fIdx + ')" ondragover="event.preventDefault()" ondrop="dropField(event,' + idx + ',' + fIdx + ')" style="cursor:grab">';
                    fieldHtml += '<label class="form-label small fw-semibold mb-1">' + escapeHtml(f.label) + reqStar + '</label>';
                    if (f.type === 'text' || f.type === 'email' || f.type === 'mobile' || f.type === 'url') {
                        fieldHtml += '<input type="text" class="form-control form-control-sm" placeholder="' + escapeHtml(f.label) + '" disabled>';
                    } else if (f.type === 'number' || f.type === 'decimal') {
                        fieldHtml += '<input type="number" class="form-control form-control-sm" placeholder="0" disabled>';
                    } else if (f.type === 'date') {
                        fieldHtml += '<input type="date" class="form-control form-control-sm" disabled>';
                    } else if (f.type === 'textarea') {
                        fieldHtml += '<textarea class="form-control form-control-sm" rows="2" placeholder="' + escapeHtml(f.label) + '" disabled></textarea>';
                    } else if (f.type === 'dropdown') {
                        fieldHtml += '<select class="form-select form-select-sm" disabled><option>Select...</option></select>';
                    } else if (f.type === 'radio') {
                        fieldHtml += '<div class="d-flex gap-3"><div class="form-check"><input class="form-check-input" type="radio" disabled><label class="form-check-label small">Option 1</label></div><div class="form-check"><input class="form-check-input" type="radio" disabled><label class="form-check-label small">Option 2</label></div></div>';
                    } else if (f.type === 'checkbox') {
                        fieldHtml += '<div class="form-check"><input class="form-check-input" type="checkbox" disabled><label class="form-check-label small">Check this</label></div>';
                    } else if (f.type === 'file' || f.type === 'image') {
                        fieldHtml += '<input type="file" class="form-control form-control-sm" disabled>';
                    } else if (f.type === 'readonly') {
                        fieldHtml += '<input type="text" class="form-control form-control-sm bg-light" value="(auto-filled)" readonly>';
                    } else {
                        fieldHtml += '<input type="text" class="form-control form-control-sm" placeholder="' + escapeHtml(f.label) + '" disabled>';
                    }
                    fieldHtml += '</div>';
                }
                html += fieldHtml;
            });
            html += '</div>';
        }
        html += '</div></div>';
    });
    container.innerHTML = html;
}

// Section drag-drop
var dragSecIdx = null;
function dragSection(e, idx) {
    dragSecIdx = idx;
    e.dataTransfer.effectAllowed = 'move';
    e.target.closest('.preview-section').style.opacity = '0.4';
}
function dropSection(e, idx) {
    e.preventDefault();
    if (dragSecIdx === null || dragSecIdx === idx) return;
    var item = previewSections.splice(dragSecIdx, 1)[0];
    previewSections.splice(idx, 0, item);
    dragSecIdx = null;
    renderPreview();
    showUnsavedBadge();
}
function saveSectionOrder() {
    var ids = previewSections.map(function(s) { return s.id; });
    var formData = new FormData();
    ids.forEach(function(id) { formData.append('section_ids[]', id); });
    return ajaxPost('/bestdealcrm/admin/form-builder/save-section-order', formData);
}

// Save all: section order + all layouts
function saveAllPreview() {
    var btn = document.getElementById('saveAllBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';

    // Save section order first
    saveSectionOrder().then(function() {
        // Then save all layouts
        var promises = previewSections.map(function(sec) {
            var fd = new FormData();
            fd.append('section_id', sec.id);
            fd.append('column_layout', sec.layout || 1);
            return ajaxPost('/bestdealcrm/admin/form-builder/save-section-layout', fd);
        });
        return Promise.all(promises);
    }).then(function() {
        // Then save all field orders
        var fieldPromises = previewSections.map(function(sec) {
            var ids = sec.fields.map(function(f) { return f.id; });
            if (ids.length === 0) return Promise.resolve();
            var fd = new FormData();
            ids.forEach(function(id) { fd.append('field_ids[]', id); });
            return ajaxPost('/bestdealcrm/admin/form-builder/save-field-order', fd);
        });
        return Promise.all(fieldPromises);
    }).then(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save All';
        var badge = document.getElementById('previewSavedBadge');
        badge.style.display = 'inline';
        setTimeout(function() { badge.style.display = 'none'; }, 3000);
        showToast('All changes saved!', 'success');
    }).catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save All';
        showToast('Save failed: ' + err.message, 'danger');
    });
}

// Field drag-drop within section
var dragFieldData = null;
function dragField(e, secIdx, fieldIdx) {
    dragFieldData = { secIdx: secIdx, fieldIdx: fieldIdx };
    e.dataTransfer.effectAllowed = 'move';
}
function dropField(e, secIdx, fieldIdx) {
    e.preventDefault();
    e.stopPropagation();
    if (!dragFieldData || dragFieldData.secIdx !== secIdx) return;
    var fields = previewSections[secIdx].fields;
    var item = fields.splice(dragFieldData.fieldIdx, 1)[0];
    fields.splice(fieldIdx, 0, item);
    dragFieldData = null;
    renderPreview();
    showUnsavedBadge();
}
function saveFieldOrder(secIdx) {
    var sec = previewSections[secIdx];
    var ids = sec.fields.map(function(f) { return f.id; });
    var formData = new FormData();
    ids.forEach(function(id) { formData.append('field_ids[]', id); });
    ajaxPost('/bestdealcrm/admin/form-builder/save-field-order', formData).then(function(r) {
        if (r && r.success) {
            var badge = document.getElementById('previewSavedBadge');
            badge.style.display = 'inline';
            setTimeout(function() { badge.style.display = 'none'; }, 2000);
        }
    });
}

// Column layout
function setSectionLayout(secIdx, cols) {
    previewSections[secIdx].layout = cols;
    renderPreview();
    showUnsavedBadge();
}

function showUnsavedBadge() {
    var badge = document.getElementById('previewSavedBadge');
    badge.textContent = 'Unsaved changes';
    badge.className = 'badge bg-warning text-dark ms-2';
    badge.style.display = 'inline';
}
</script>
