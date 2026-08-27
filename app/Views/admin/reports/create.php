<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-file-earmark-plus me-2"></i><?= $editMode ?? false ? 'Edit Report Template' : 'Create Report Template' ?></h4>
        <small class="text-muted">Select the columns you want to include in the export</small>
    </div>
    <a href="/bestdealcrm/admin/reports" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="row g-4">
    <div class="col-md-8">
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
                               value="<?= htmlspecialchars(($template['name'] ?? $_GET['name'] ?? '')) ?>"
                               placeholder="e.g., Agent Performance Report">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control"
                               value="<?= htmlspecialchars($template['description'] ?? '') ?>"
                               placeholder="Optional description">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Select Columns</h6>

                <!-- Select All -->
                <div class="mb-3 p-2 bg-light rounded d-flex justify-content-between align-items-center">
                    <div>
                        <input type="checkbox" class="form-check-input me-2" id="selectAll" onclick="toggleAllColumns(this)">
                        <label class="form-check-label fw-semibold" for="selectAll">Select All Columns</label>
                    </div>
                    <small class="text-muted" id="selectedCount">0 selected</small>
                </div>

                <!-- Lead Columns -->
                <h6 class="text-primary mt-3 mb-2"><i class="bi bi-database me-1"></i> Lead Data</h6>
                <div class="row g-2 mb-3">
                    <?php
                    $preselected = [];
                    if ($editMode ?? false) {
                        $preselected = array_column($template['columns_config'] ?? [], 'field');
                    }
                    ?>
                    <?php foreach ($availableColumns as $key => $col): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check border rounded p-2 <?= in_array($key, $preselected) ? 'border-primary bg-light' : '' ?>">
                            <input class="form-check-input column-check" type="checkbox" 
                                   name="columns[]" value="<?= $key ?>" id="col_<?= $key ?>"
                                   <?= in_array($key, $preselected) ? 'checked' : '' ?>
                                   onchange="updateCount()">
                            <label class="form-check-label small" for="col_<?= $key ?>"><?= htmlspecialchars($col['label']) ?></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Dynamic Columns (may exist in DB) -->
                <?php if (!empty($dynamicColumns)): ?>
                <h6 class="text-success mt-3 mb-2"><i class="bi bi-plus-circle me-1"></i> Additional Columns</h6>
                <div class="row g-2 mb-3">
                    <?php foreach ($dynamicColumns as $key => $col): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check border rounded p-2 <?= in_array($key, $preselected) ? 'border-success bg-light' : '' ?>">
                            <input class="form-check-input column-check" type="checkbox" 
                                   name="columns[]" value="<?= $key ?>" id="col_<?= $key ?>"
                                   <?= in_array($key, $preselected) ? 'checked' : '' ?>
                                   onchange="updateCount()">
                            <label class="form-check-label small" for="col_<?= $key ?>"><?= htmlspecialchars($col['label']) ?></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> <?= $editMode ?? false ? 'Update Template' : 'Save Template' ?>
                    </button>
                    <a href="/bestdealcrm/admin/reports" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="table-container">
            <h6 class="fw-bold mb-3"><i class="bi bi-eye me-1"></i> Preview Selection</h6>
            <div id="previewList" class="text-muted small">
                <p>Select columns to see them here.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Preselected columns for edit mode
var preselected = <?= json_encode($preselected) ?>;

function toggleAllColumns(el) {
    document.querySelectorAll('.column-check').forEach(function(cb) {
        cb.checked = el.checked;
        cb.closest('.form-check').classList.toggle('border-primary', el.checked);
        cb.closest('.form-check').classList.toggle('bg-light', el.checked);
    });
    updateCount();
}

function updateCount() {
    var checked = document.querySelectorAll('.column-check:checked');
    var count = checked.length;
    document.getElementById('selectedCount').textContent = count + ' selected';
    
    // Update preview
    var preview = document.getElementById('previewList');
    if (count === 0) {
        preview.innerHTML = '<p class="text-muted">Select columns to see them here.</p>';
        return;
    }
    var html = '<ol class="ps-3 mb-0">';
    checked.forEach(function(cb) {
        var label = cb.closest('.form-check').querySelector('label').textContent;
        html += '<li class="mb-1">' + label + '</li>';
    });
    html += '</ol>';
    preview.innerHTML = html;

    // Update border
    checked.forEach(function(cb) {
        cb.closest('.form-check').classList.add('border-primary', 'bg-light');
    });
    document.querySelectorAll('.column-check:not(:checked)').forEach(function(cb) {
        cb.closest('.form-check').classList.remove('border-primary', 'bg-light');
    });
}

// Form submit
document.getElementById('templateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var checked = document.querySelectorAll('.column-check:checked');
    if (checked.length === 0) {
        showToast('Select at least one column.', 'warning');
        return;
    }

    var formData = new FormData(this);
    var url = <?= ($editMode ?? false) ? "'/bestdealcrm/admin/reports/update'" : "'/bestdealcrm/admin/reports/store'" ?>;
    
    ajaxPost(url, formData).then(function(result) {
        if (result && result.success) {
            showToast(result.message, 'success');
            setTimeout(function() { window.location.href = '/bestdealcrm/admin/reports'; }, 800);
        } else {
            showToast(result.error || 'Error saving template.', 'danger');
        }
    });
});

// Init count
updateCount();
</script>
