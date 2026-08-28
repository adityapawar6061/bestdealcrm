<?php
// Section layout helper
if (!function_exists('getFieldColClass')) {
    function getFieldColClass($field, $sectionLayout = 2) {
        $type = $field['type'] ?? 'text';
        $fieldType = $field['field_type'] ?? 'field';
        if ($fieldType === 'heading' || $fieldType === 'subheading') return 'col-12';
        if ($type === 'textarea') return 'col-12';
        switch ((int)$sectionLayout) {
            case 1:  return 'col-md-12';
            case 3:  return 'col-md-4';
            default: return 'col-md-6';
        }
    }
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard-data me-2"></i>Post-Login Form</h4>
        <small class="text-muted">Lead #<?= $lead['id'] ?> - <?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= statusBadge($lead['workflow_stage']) ?></small>
    </div>
    <a href="/bestdealcrm/login-agent/cases" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Cases
    </a>
</div>

<!-- Lead Info Bar -->
<div class="table-container mb-4 p-3">
    <div class="row g-3">
        <div class="col-md-3"><small class="text-muted">Customer:</small> <strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted">Mobile:</small> <?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></div>
        <div class="col-md-2"><small class="text-muted">Location:</small> <?= htmlspecialchars($lead['location'] ?? '-') ?></div>
        <div class="col-md-2"><small class="text-muted">Bank:</small> <?= htmlspecialchars($lead['bank_name'] ?? '-') ?></div>
        <div class="col-md-3"><small class="text-muted">Stage:</small> <?= statusBadge($lead['workflow_stage']) ?></div>
    </div>
</div>

<!-- ============================================================ -->
<!-- SECTION 1: Agent Form (Read Only) -->
<!-- ============================================================ -->
<div class="table-container mb-4" id="agentFormSection">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-secondary mb-0">
            <i class="bi bi-person-check me-1"></i> 1. Agent Form (Submitted by Agent)
        </h6>
        <span class="badge bg-secondary">Read Only</span>
    </div>
    <?php if (empty($agentValues)): ?>
        <div class="alert alert-light mb-0"><i class="bi bi-info-circle me-1"></i> No agent form submission found for this lead.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($agentValues as $v): ?>
                <?php if (in_array($v['field_name'], ['heading', 'subheading']) || empty($v['label'])) continue; ?>
                <?php $val = $v['value'] ?? '-'; ?>
                <?php if (empty($val) || $val === '0000-00-00 00:00:00') $val = '-'; ?>
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= htmlspecialchars($v['label']) ?></small>
                    <strong class="small"><?= htmlspecialchars($val) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- SECTION 2: Pre-Login Checklist (Read Only) -->
<!-- ============================================================ -->
<div class="table-container mb-4" id="preLoginSection">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-info mb-0">
            <i class="bi bi-clipboard-check me-1"></i> 2. Pre-Login Checklist (Your Submission)
        </h6>
        <span class="badge bg-info">Read Only</span>
    </div>
    <?php if (empty($preLoginValues)): ?>
        <div class="alert alert-light mb-0"><i class="bi bi-info-circle me-1"></i> No pre-login checklist submission found.</div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($preLoginValues as $v): ?>
                <?php if (empty($v['label'])) continue; ?>
                <?php $val = $v['value'] ?? '-'; ?>
                <?php if (empty($val) || $val === '0000-00-00 00:00:00') $val = '-'; ?>
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= htmlspecialchars($v['label']) ?></small>
                    <strong class="small"><?= htmlspecialchars($val) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- SECTION 3: Post-Login Form (Editable) -->
<!-- ============================================================ -->
<?php if (!$postForm): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i> No Post-Login form configured. Please contact admin.
    </div>
<?php else: ?>
<div class="table-container mb-4" id="postLoginFormSection">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-primary mb-0">
            <i class="bi bi-pencil-square me-1"></i> 3. Post-Login Form (Fill Below)
        </h6>
        <span class="badge bg-primary">Editable</span>
    </div>

    <form id="postLoginForm" onsubmit="return false;">
        <?= csrfField() ?>
        <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
        <input type="hidden" name="form_id" value="<?= $postForm['id'] ?>">    <?php foreach ($postForm['sections'] as $section): ?>
    <?php $sectionLayout = $section['column_layout'] ?? 2; ?>
    <div class="mb-4">
            <h6 class="small fw-bold text-muted mb-2 border-bottom pb-1">
                <i class="bi bi-card-list me-1"></i> <?= htmlspecialchars($section['name']) ?>
            </h6>
            <div class="row g-3">
                <?php foreach ($section['fields'] as $field): ?>
                    <?php
                    $value = $postLoginValues[$field['id']] ?? $field['default_value'] ?? '';
                    $isRequired = $field['required'] ? 'required' : '';
                    $fieldName = "form_data[{$field['id']}]";
                    $colClass = getFieldColClass($field, $sectionLayout);
                    ?>
                    <?php if (($field['field_type'] ?? 'field') === 'heading'): ?>
                        <div class="col-12"><h5 class="text-primary mt-2"><?= htmlspecialchars($field['label']) ?></h5></div>
                    <?php elseif (($field['field_type'] ?? 'field') === 'subheading'): ?>
                        <div class="col-12"><h6 class="text-dark mt-1"><?= htmlspecialchars($field['label']) ?></h6></div>
                    <?php else: ?>
                    <div class="<?= $colClass ?>">
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
                                    <option value="<?= htmlspecialchars($opt['value']) ?>" <?= $value == $opt['value'] ? 'selected' : '' ?>><?= htmlspecialchars($opt['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($field['type'] === 'radio'): ?>
                            <div class="d-flex gap-3 mt-1">
                                <?php foreach ($field['options'] ?? [] as $opt): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="<?= $fieldName ?>" value="<?= htmlspecialchars($opt['value']) ?>" <?= $value == $opt['value'] ? 'checked' : '' ?>>
                                        <label class="form-check-label small"><?= htmlspecialchars($opt['label']) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($field['type'] === 'checkbox'): ?>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="<?= $fieldName ?>" value="1" <?= $value ? 'checked' : '' ?>>
                                <label class="form-check-label small">Yes</label>
                            </div>
                        <?php elseif ($field['type'] === 'file' || $field['type'] === 'image'): ?>
                            <input type="file" name="<?= $fieldName ?>" class="form-control form-control-sm" <?= $field['required'] ? 'required' : '' ?> accept="<?= $field['type'] === 'image' ? 'image/*' : '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png' ?>">
                        <?php elseif ($field['type'] === 'readonly'): ?>
                            <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($value) ?>" readonly>
                        <?php elseif (in_array($field['type'], ['number', 'decimal'])): ?>
                            <input type="number" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?> step="<?= $field['type'] === 'decimal' ? '0.01' : '1' ?>">
                        <?php else: ?>
                            <input type="text" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?>>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </form>
</div>

<!-- Action Buttons -->
<div class="d-flex justify-content-between mb-4">
    <div>
        <button type="button" class="btn btn-outline-danger" onclick="sendBackFromPostLogin()">
            <i class="bi bi-arrow-return-left me-1"></i> Send Back to Agent
        </button>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-warning" onclick="savePostDraft()">
            <i class="bi bi-save me-1"></i> Save Draft
        </button>
        <button type="button" class="btn btn-primary" onclick="submitPostLoginAction()">
            <i class="bi bi-send me-1"></i> Submit to Admin (Review 3)
        </button>
    </div>
</div>
<?php endif; ?>

<script>
function savePostDraft() {
    var form = document.getElementById('postLoginForm');
    if (!form) return;
    var formData = new FormData(form);

    fetch('/bestdealcrm/login-agent/cases/save-post-login-draft', {
        method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) { showToast(result.message, 'success'); }
        else { showToast(result.error || 'Save failed.', 'danger'); }
    }).catch(function(e) { showToast('Network error.', 'danger'); });
}

function submitPostLoginAction() {
    if (!confirm('Submit Post-Login form to Admin for Review 3?')) return;
    var form = document.getElementById('postLoginForm');
    if (!form) return;
    var formData = new FormData(form);

    fetch('/bestdealcrm/login-agent/cases/submit-post-login', {
        method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(function() { window.location.href = '/bestdealcrm/login-agent/cases'; }, 1000);
        } else {
            showToast(result.error || 'Submit failed.', 'danger');
        }
    }).catch(function(e) { showToast('Network error.', 'danger'); });
}

function sendBackFromPostLogin() {
    var remark = prompt('Remark (mandatory when sending back to agent):');
    if (!remark) return;
    var formData = new FormData();
    formData.append('lead_id', <?= $lead['id'] ?>);
    formData.append('remark', remark);

    fetch('/bestdealcrm/login-agent/cases/send-back', {
        method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(function() { window.location.href = '/bestdealcrm/login-agent/cases'; }, 1000);
        } else {
            showToast(result.error || 'Failed.', 'danger');
        }
    }).catch(function(e) { showToast('Network error.', 'danger'); });
}
</script>
