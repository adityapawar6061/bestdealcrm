<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard-check me-2"></i>Pre-Login Checklist</h4>
        <small class="text-muted">Lead #<?= $lead['id'] ?> - <?= htmlspecialchars($lead['customer_name'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/login-agent/cases" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Lead Info Bar -->
<div class="table-container mb-4 p-3">
    <div class="row">
        <div class="col-md-3"><small class="text-muted">Customer:</small> <strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted">Mobile:</small> <?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></div>
        <div class="col-md-2"><small class="text-muted">Status:</small> <?= statusBadge($lead['workflow_stage']) ?></div>
    </div>
</div>

<?php if (!$form): ?>
    <div class="alert alert-warning">No Pre-Login Checklist form configured. Please contact admin.</div>
<?php else: ?>
<form id="checklistForm" onsubmit="return false;">
    <?= csrfField() ?>
    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
    <input type="hidden" name="form_id" value="<?= $form['id'] ?>">

    <?php foreach ($form['sections'] as $section): ?>
    <div class="table-container mb-4">
        <h6 class="fw-bold text-primary mb-3">
            <i class="bi bi-check2-square me-1"></i>
            <?= htmlspecialchars($section['name']) ?>
        </h6>
        <div class="row g-3">
            <?php foreach ($section['fields'] as $field): ?>
                <?php 
                $value = $existingValues[$field['id']] ?? $field['default_value'] ?? '';
                $isRequired = $field['required'] ? 'required' : '';
                $fieldName = "form_data[{$field['id']}]";
                ?>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">
                        <?= htmlspecialchars($field['label']) ?>
                        <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>
                    <?php if ($field['type'] === 'textarea'): ?>
                        <textarea name="<?= $fieldName ?>" class="form-control form-control-sm" rows="2" <?= $isRequired ?>><?= htmlspecialchars($value) ?></textarea>
                    <?php elseif ($field['type'] === 'dropdown'): ?>
                        <select name="<?= $fieldName ?>" class="form-select form-select-sm" <?= $isRequired ?>>
                            <option value="">Select...</option>
                            <?php foreach ($field['options'] ?? [] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt['value']) ?>" <?= $value === $opt['value'] ? 'selected' : '' ?>><?= htmlspecialchars($opt['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field['type'] === 'checkbox'): ?>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="<?= $fieldName ?>" value="1" <?= $value ? 'checked' : '' ?>>
                            <label class="form-check-label small">Confirmed</label>
                        </div>
                    <?php else: ?>
                        <input type="<?= $field['type'] === 'number' || $field['type'] === 'decimal' ? 'number' : 'text' ?>" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Actions -->
    <div class="d-flex justify-content-between mb-4">
        <div>
            <button type="button" class="btn btn-outline-danger" onclick="sendBackToAgent()">
                <i class="bi bi-arrow-return-left me-1"></i> Send Back to Agent
            </button>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-warning" onclick="submitChecklist('draft')">
                <i class="bi bi-save me-1"></i> Save as Draft
            </button>
            <button type="button" class="btn btn-primary" onclick="submitChecklist('submit')">
                <i class="bi bi-send me-1"></i> Send to Admin
            </button>
        </div>
    </div>
</form>
<?php endif; ?>

<script>
async function submitChecklist(action) {
    const form = document.getElementById('checklistForm');
    const formData = new FormData(form);
    const url = action === 'draft'
        ? '/bestdealcrm/login-agent/cases/save-draft'
        : '/bestdealcrm/login-agent/cases/submit-checklist';

    try {
        const response = await fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            window.location.href = '/bestdealcrm/login-agent/cases';
        } else {
            alert(result.error || 'Error occurred.');
        }
    } catch (err) {
        alert('Network error.');
    }
}

async function sendBackToAgent() {
    const remark = prompt('Remark (mandatory when sending back to agent):');
    if (!remark) return alert('Remark is required.');

    const formData = new FormData(document.getElementById('checklistForm'));
    formData.append('remark', remark);

    try {
        const response = await fetch('/bestdealcrm/login-agent/cases/send-back', {
            method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            window.location.href = '/bestdealcrm/login-agent/cases';
        } else {
            alert(result.error || 'Error occurred.');
        }
    } catch (err) {
        alert('Network error.');
    }
}
</script>
