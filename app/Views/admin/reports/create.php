<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-file-earmark-plus me-2"></i><?= ($editMode ?? false) ? 'Edit Report Template' : 'Create Report Template' ?></h4>
        <small class="text-muted">Select columns from Lead Data and Form Builder fields to include in the export</small>
    </div>
    <a href="/bestdealcrm/admin/reports" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="row g-4">
    <div class="col-md-9">
        <div class="table-container">
            <form id="templateForm">
                <?= csrfField() ?>
                <?php if ($editMode ?? false): ?>
                    <input type="hidden" name="template_id" value="<?= $template['id'] ?>">
                <?php endif; ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Template Name *</label>
                        <input type="text" name="template_name" class="form-control" required
                               value="<?= htmlspecialchars(($template['name'] ?? '')) ?>"
                               placeholder="e.g., Agent Performance Report">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control"
                               value="<?= htmlspecialchars($template['description'] ?? '') ?>"
                               placeholder="Optional description">
                    </div>
                </div>

                <!-- Select All -->
                <div class="mb-3 p-2 bg-light rounded d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" class="form-check-input me-2" id="selectAll" onclick="toggleAllColumns(this)">
                        <label class="form-check-label fw-semibold" for="selectAll">Select All Columns</label>
                    </div>
                    <small class="text-muted"><span id="selectedCount">0</span> selected</small>
                </div>

                <?php
                $preselected = [];
                if ($editMode ?? false) {
                    $preselected = array_column($template['columns_config'] ?? [], 'field');
                }
                ?>

                <!-- ===== LEAD DATA (Static Columns) ===== -->
                <div class="accordion mb-4" id="columnsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#leadData" aria-expanded="true">
                                <i class="bi bi-database text-primary me-2"></i>
                                <strong>Lead Data</strong>
                                <span class="badge bg-primary ms-2" id="leadCountBadge">0</span>
                            </button>
                        </h2>
                        <div id="leadData" class="accordion-collapse collapse show" data-bs-parent="#columnsAccordion">
                            <div class="accordion-body">
                                <h6 class="text-muted small mb-2">Standard Lead Columns</h6>
                                <div class="row g-2 mb-3">
                                    <?php foreach ($leadColumns as $key => $col): ?>
                                    <div class="col-md-3 col-lg-2">
                                        <div class="form-check border rounded p-2 <?= in_array($key, $preselected) ? 'border-primary bg-light' : '' ?>">
                                            <input class="form-check-input column-check lead-check" type="checkbox" 
                                                   name="columns[]" value="<?= $key ?>" id="col_<?= md5($key) ?>"
                                                   <?= in_array($key, $preselected) ? 'checked' : '' ?>
                                                   onchange="updateCount()">
                                            <label class="form-check-label small" for="col_<?= md5($key) ?>"><?= htmlspecialchars($col['label']) ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php if (!empty($dynamicColumns)): ?>
                                <h6 class="text-success small mb-2 mt-3"><i class="bi bi-plus-circle me-1"></i> Additional Lead Columns</h6>
                                <div class="row g-2">
                                    <?php foreach ($dynamicColumns as $key => $col): ?>
                                    <div class="col-md-3 col-lg-2">
                                        <div class="form-check border rounded p-2 <?= in_array($key, $preselected) ? 'border-success bg-light' : '' ?>">
                                            <input class="form-check-input column-check lead-check" type="checkbox" 
                                                   name="columns[]" value="<?= $key ?>" id="col_<?= md5($key) ?>"
                                                   <?= in_array($key, $preselected) ? 'checked' : '' ?>
                                                   onchange="updateCount()">
                                            <label class="form-check-label small" for="col_<?= md5($key) ?>"><?= htmlspecialchars($col['label']) ?></label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ===== FORM BUILDER FIELDS (Dynamic) ===== -->
                    <?php if (!empty($formColumns)): ?>
                    <?php foreach ($formColumns as $form): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#form_<?= $form['id'] ?>">
                                <i class="bi bi-file-earmark-text text-info me-2"></i>
                                <strong><?= htmlspecialchars($form['name']) ?></strong>
                                <span class="text-muted ms-2">(<?= $form['code'] ?>)</span>
                                <span class="badge bg-info ms-2" id="formCountBadge_<?= $form['id'] ?>">0</span>
                            </button>
                        </h2>
                        <div id="form_<?= $form['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#columnsAccordion">
                            <div class="accordion-body">
                                <?php foreach ($form['sections'] as $section): ?>
                                <h6 class="text-muted small mb-2 mt-3">
                                    <i class="bi bi-list-ul me-1"></i> <?= htmlspecialchars($section['name']) ?>
                                </h6>
                                <div class="row g-2 mb-2">
                                    <?php foreach ($section['fields'] as $field): ?>
                                    <?php
                                    $fieldKey = "form_{$form['id']}_{$field['field_name']}";
                                    ?>
                                    <div class="col-md-3 col-lg-2">
                                        <div class="form-check border rounded p-2 <?= in_array($fieldKey, $preselected) ? 'border-info bg-light' : '' ?>">
                                            <input class="form-check-input column-check form-field-check" 
                                                   data-form-id="<?= $form['id'] ?>"
                                                   type="checkbox" 
                                                   name="columns[]" value="<?= $fieldKey ?>" 
                                                   id="col_<?= md5($fieldKey) ?>"
                                                   <?= in_array($fieldKey, $preselected) ? 'checked' : '' ?>
                                                   onchange="updateCount()">
                                            <label class="form-check-label small" for="col_<?= md5($fieldKey) ?>">
                                                <?= htmlspecialchars($field['label']) ?>
                                                <br><code class="text-muted" style="font-size:0.65rem"><?= htmlspecialchars($field['field_name']) ?></code>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> <?= ($editMode ?? false) ? 'Update Template' : 'Save Template' ?>
                    </button>
                    <a href="/bestdealcrm/admin/reports" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-3">
        <div class="table-container" style="position:sticky;top:80px;">
            <h6 class="fw-bold mb-3"><i class="bi bi-eye me-1"></i> Selected Columns</h6>
            <div id="previewList" class="text-muted small" style="max-height:60vh;overflow-y:auto;">
                <p>Select columns to see them here.</p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAllColumns(el) {
    document.querySelectorAll('.column-check').forEach(function(cb) {
        cb.checked = el.checked;
        updateCheckStyle(cb);
    });
    updateCount();
}

function updateCheckStyle(cb) {
    var wrapper = cb.closest('.form-check');
    if (!wrapper) return;
    var color = cb.classList.contains('form-field-check') ? 'border-info' : (cb.classList.contains('lead-check') ? 'border-primary' : 'border-success');
    if (cb.checked) {
        wrapper.classList.add(color, 'bg-light');
    } else {
        wrapper.classList.remove('border-primary', 'border-success', 'border-info', 'bg-light');
    }
}

function updateCount() {
    var checked = document.querySelectorAll('.column-check:checked');
    var count = checked.length;
    document.getElementById('selectedCount').textContent = count;
    
    // Count lead vs form
    var leadChecked = document.querySelectorAll('.lead-check:checked').length;
    var formChecked = document.querySelectorAll('.form-field-check:checked').length;

    // Update form badges
    var formBadges = {};
    checked.forEach(function(cb) {
        if (cb.classList.contains('form-field-check')) {
            var fid = cb.getAttribute('data-form-id');
            formBadges[fid] = (formBadges[fid] || 0) + 1;
        }
    });
    // Reset all form badges
    document.querySelectorAll('[id^="formCountBadge_"]').forEach(function(b) { b.textContent = '0'; });
    // Set active form badges
    Object.keys(formBadges).forEach(function(fid) {
        var badge = document.getElementById('formCountBadge_' + fid);
        if (badge) badge.textContent = formBadges[fid];
    });
    document.getElementById('leadCountBadge').textContent = leadChecked;

    // Update preview sidebar
    var preview = document.getElementById('previewList');
    if (count === 0) {
        preview.innerHTML = '<p>Select columns to see them here.</p>';
        return;
    }
    var html = '<ul class="list-unstyled ps-0 mb-0">';
    checked.forEach(function(cb) {
        var label = cb.closest('.form-check').querySelector('label').textContent.trim().split('\n')[0];
        var icon = cb.classList.contains('form-field-check') ? '📄' : '🗄️';
        html += '<li class="mb-1">' + icon + ' <small>' + label + '</small></li>';
    });
    html += '</ul>';
    preview.innerHTML = html;

    // Update styles for all
    document.querySelectorAll('.column-check').forEach(updateCheckStyle);
}

document.getElementById('templateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var checked = document.querySelectorAll('.column-check:checked');
    if (checked.length === 0) {
        showToast('Select at least one column.', 'warning');
        return;
    }
    var formData = new FormData(this);
    var url = <?= ($editMode ?? false) ? '"/bestdealcrm/admin/reports/update"' : '"/bestdealcrm/admin/reports/store"' ?>;
    ajaxPost(url, formData).then(function(result) {
        if (result && result.success) {
            showToast(result.message, 'success');
            setTimeout(function() { window.location.href = '/bestdealcrm/admin/reports'; }, 800);
        } else {
            showToast(result.error || 'Error saving.', 'danger');
        }
    });
});

updateCount();
</script>
