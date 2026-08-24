<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard-data me-2"></i>Post-Login Form</h4>
        <small class="text-muted">Lead #<?= $lead['id'] ?> - <?= htmlspecialchars($lead['customer_name'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/login-agent/cases" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Lead Info -->
<div class="table-container mb-4 p-3">
    <div class="row">
        <div class="col-md-3"><small class="text-muted">Customer:</small> <strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted">Mobile:</small> <?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></div>
        <div class="col-md-2"><small class="text-muted">Status:</small> <?= statusBadge($lead['workflow_stage']) ?></div>
    </div>
</div>

<?php if (!$form): ?>
    <div class="alert alert-warning">No Post-Login form configured. Please contact admin.</div>
<?php else: ?>
<form id="postLoginForm" onsubmit="return false;">
    <?= csrfField() ?>
    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
    <input type="hidden" name="form_id" value="<?= $form['id'] ?>">

    <?php foreach ($form['sections'] as $section): ?>
    <div class="table-container mb-4">
        <h6 class="fw-bold text-primary mb-3">
            <i class="bi bi-card-list me-1"></i> <?= htmlspecialchars($section['name']) ?>
        </h6>
        <div class="row g-3">
            <?php foreach ($section['fields'] as $field): ?>
                <?php $fieldName = "form_data[{$field['id']}]"; ?>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">
                        <?= htmlspecialchars($field['label']) ?>
                        <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>
                    <?php if ($field['type'] === 'textarea'): ?>
                        <textarea name="<?= $fieldName ?>" class="form-control form-control-sm" rows="2" <?= $field['required'] ? 'required' : '' ?>></textarea>
                    <?php elseif ($field['type'] === 'dropdown'): ?>
                        <select name="<?= $fieldName ?>" class="form-select form-select-sm" <?= $field['required'] ? 'required' : '' ?>>
                            <option value="">Select...</option>
                            <?php foreach ($field['options'] ?? [] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt['value']) ?>"><?= htmlspecialchars($opt['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="<?= in_array($field['type'], ['number','decimal']) ? 'number' : 'text' ?>" name="<?= $fieldName ?>" class="form-control form-control-sm" <?= $field['required'] ? 'required' : '' ?> step="<?= $field['type'] === 'decimal' ? '0.01' : '1' ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <button type="button" class="btn btn-primary" onclick="submitPostLogin()">
            <i class="bi bi-send me-1"></i> Submit Post-Login
        </button>
    </div>
</form>
<?php endif; ?>

<script>
async function submitPostLogin() {
    const form = document.getElementById('postLoginForm');
    const formData = new FormData(form);

    try {
        const response = await fetch('/bestdealcrm/login-agent/cases/submit-checklist', {
            method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        if (result.success) { alert(result.message); window.location.href = '/bestdealcrm/login-agent/cases'; }
        else alert(result.error || 'Error occurred.');
    } catch (err) { alert('Network error.'); }
}
</script>
