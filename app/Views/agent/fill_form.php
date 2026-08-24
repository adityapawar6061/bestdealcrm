<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-pencil-square me-2"></i>Lead Form</h4>
        <small class="text-muted">Lead #<?= $lead['id'] ?> - <?= htmlspecialchars($lead['customer_name'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/agent/leads" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Lead Info Bar -->
<div class="table-container mb-4 p-3">
    <div class="row">
        <div class="col-md-3"><small class="text-muted">Customer:</small> <strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted">Mobile:</small> <?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></div>
        <div class="col-md-2"><small class="text-muted">Location:</small> <?= htmlspecialchars($lead['location'] ?? '-') ?></div>
        <div class="col-md-2"><small class="text-muted">Salary:</small> <?= $lead['salary'] ? '₹' . number_format($lead['salary']) : '-' ?></div>
        <div class="col-md-3"><small class="text-muted">Status:</small> <?= statusBadge($lead['workflow_stage']) ?></div>
    </div>
</div>

<?php if (!$form): ?>
    <div class="alert alert-warning">No form configured for this stage. Please contact admin.</div>
<?php else: ?>
<form id="agentForm" onsubmit="return false;">
    <?= csrfField() ?>
    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
    <input type="hidden" name="form_id" value="<?= $form['id'] ?>">

    <?php foreach ($form['sections'] as $section): ?>
    <div class="table-container mb-4">
        <h6 class="fw-bold text-primary mb-3">
            <i class="bi bi-card-list me-1"></i>
            <?= htmlspecialchars($section['name']) ?>
        </h6>
        
        <div class="row g-3">
            <?php foreach ($section['fields'] as $field): ?>
                <?php 
                $value = $existingValues[$field['id']] ?? $field['default_value'] ?? '';
                $isRequired = $field['required'] ? 'required' : '';
                $fieldName = "form_data[{$field['id']}]";
                ?>
                
                <div class="col-md-<?= in_array($field['type'], ['textarea']) ? '12' : '6' ?>">
                    <?php if ($field['type'] === 'heading'): ?>
                        <h5 class="mt-2"><?= htmlspecialchars($field['label']) ?></h5>
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <textarea name="<?= $fieldName ?>" class="form-control form-control-sm" rows="3" <?= $isRequired ?> placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>"><?= htmlspecialchars($value) ?></textarea>
                    <?php elseif ($field['type'] === 'dropdown'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <select name="<?= $fieldName ?>" class="form-select form-select-sm" <?= $isRequired ?>>
                            <option value="">Select...</option>
                            <?php foreach ($field['options'] ?? [] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt['value']) ?>" <?= $value === $opt['value'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($opt['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field['type'] === 'radio'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <div class="d-flex gap-3">
                            <?php foreach ($field['options'] ?? [] as $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="<?= $fieldName ?>" value="<?= htmlspecialchars($opt['value']) ?>" <?= $value === $opt['value'] ? 'checked' : '' ?> <?= $isRequired ?>>
                                    <label class="form-check-label small"><?= htmlspecialchars($opt['label']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($field['type'] === 'checkbox'): ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="<?= $fieldName ?>" value="1" <?= $value ? 'checked' : '' ?>>
                            <label class="form-check-label small fw-semibold"><?= htmlspecialchars($field['label']) ?></label>
                        </div>
                    <?php elseif ($field['type'] === 'file' || $field['type'] === 'image'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="file" name="<?= $fieldName ?>" class="form-control form-control-sm" <?= $isRequired ?> <?= $field['type'] === 'image' ? 'accept="image/*"' : '' ?>>
                    <?php elseif ($field['type'] === 'date'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="date" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?>>
                    <?php elseif ($field['type'] === 'number' || $field['type'] === 'decimal'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="number" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?> step="<?= $field['type'] === 'decimal' ? '0.01' : '1' ?>" placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                    <?php else: ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="<?= $field['type'] === 'mobile' ? 'tel' : $field['type'] ?>" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?> placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Actions -->
    <div class="d-flex justify-content-end gap-2 mb-4">
        <button type="button" class="btn btn-outline-warning" onclick="submitAgentForm('draft')">
            <i class="bi bi-save me-1"></i> Save as Draft
        </button>
        <button type="button" class="btn btn-primary" onclick="submitAgentForm('submit')">
            <i class="bi bi-send me-1"></i> Submit to Admin
        </button>
    </div>
</form>
<?php endif; ?>

<script>
async function submitAgentForm(action) {
    const form = document.getElementById('agentForm');
    if (!form) return;
    
    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    let valid = true;
    requiredFields.forEach(f => {
        if (!f.value) {
            f.classList.add('is-invalid');
            valid = false;
        } else {
            f.classList.remove('is-invalid');
        }
    });
    
    if (!valid) {
        alert('Please fill in all required fields.');
        return;
    }
    
    const formData = new FormData(form);
    const url = action === 'draft' 
        ? '/bestdealcrm/agent/leads/save-draft' 
        : '/bestdealcrm/agent/leads/submit-form';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            window.location.href = '/bestdealcrm/agent/leads';
        } else {
            alert(result.error || 'An error occurred.');
        }
    } catch (err) {
        alert('Network error. Please try again.');
    }
}
</script>
