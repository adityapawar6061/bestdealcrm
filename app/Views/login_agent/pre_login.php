<?php
$workflowSteps = [
    'LEAD_UPLOADED'       => ['label' => 'Lead Uploaded',  'icon' => 'bi-cloud-upload'],
    'LEAD_ASSIGNED'       => ['label' => 'Assigned',        'icon' => 'bi-person-check'],
    'AGENT_DRAFT'         => ['label' => 'Agent Draft',     'icon' => 'bi-pencil-square'],
    'AGENT_SUBMITTED'     => ['label' => 'Submitted',       'icon' => 'bi-send'],
    'ADMIN_REVIEW_1'      => ['label' => 'Admin Review',    'icon' => 'bi-clipboard-check'],
    'LOGIN_AGENT_ASSIGNED'=> ['label' => 'Login Agent',     'icon' => 'bi-person-badge'],
    'ADMIN_REVIEW_2'      => ['label' => 'Review 2',        'icon' => 'bi-clipboard2-check'],
    'LOGIN_APPROVED'      => ['label' => 'Approved',        'icon' => 'bi-check-circle'],
    'UNDERWRITING'        => ['label' => 'Underwriting',    'icon' => 'bi-file-earmark-check'],
    'DISPATCH'            => ['label' => 'Dispatch',        'icon' => 'bi-truck'],
    'COMPLETED'           => ['label' => 'Completed',       'icon' => 'bi-trophy'],
];
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard-check me-2"></i>Pre-Login Checklist</h4>
        <small class="text-muted">Lead #<?= $lead['id'] ?> - <?= htmlspecialchars($lead['customer_name'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/login-agent/cases" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Workflow Progress Bar -->
<div class="table-container mb-4 p-3">
    <div class="d-flex align-items-center flex-wrap gap-1" style="overflow-x:auto">
        <?php
        $stepKeys = array_keys($workflowSteps);
        $currentIdx = array_search($lead['workflow_stage'], $stepKeys);
        if ($currentIdx === false) $currentIdx = 0;
        foreach ($workflowSteps as $stage => $info):
            $sIdx = array_search($stage, $stepKeys);
            $isActive = ($stage === $lead['workflow_stage']);
            $isCompleted = ($sIdx < $currentIdx);
        ?>
            <div class="text-center flex-shrink-0" style="min-width:70px">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1
                    <?= $isActive ? 'bg-primary text-white' : ($isCompleted ? 'bg-success text-white' : 'bg-light text-muted') ?>"
                    style="width:32px;height:32px;font-size:0.75rem">
                    <i class="bi <?= $info['icon'] ?>"></i>
                </div>
                <div class="small <?= $isActive ? 'fw-bold text-primary' : ($isCompleted ? 'text-success' : 'text-muted') ?>" style="font-size:0.6rem;line-height:1.1">
                    <?= $info['label'] ?>
                </div>
            </div>
            <?php if ($sIdx < count($stepKeys) - 1): ?>
                <div class="flex-shrink-0" style="width:16px;height:2px;background:<?= $isCompleted ? '#22c55e' : '#e2e8f0' ?>;margin-bottom:14px"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<!-- Lead Info Bar -->
<div class="table-container mb-4 p-3">
    <div class="row">
        <div class="col-md-3"><small class="text-muted">Customer:</small> <strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted">Mobile:</small> <?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></div>
        <div class="col-md-2"><small class="text-muted">Status:</small> <?= statusBadge($lead['workflow_stage']) ?></div>
        <div class="col-md-2"><small class="text-muted">Bank:</small> <?= htmlspecialchars($lead['bank_name'] ?? '-') ?></div>
        <div class="col-md-3"><small class="text-muted">Salary:</small> <?= $lead['salary'] ? '₹' . number_format($lead['salary']) : '-' ?></div>
    </div>
</div>

<!-- ===== AGENT FORM (Read Only) ===== -->
<?php if (!empty($agentValues)): ?>
<div class="table-container mb-4" style="border-left:4px solid #6c757d">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-secondary mb-0">
            <i class="bi bi-person-check me-1"></i> Agent Form (Submitted by Agent)
        </h6>
        <span class="badge bg-secondary">Read Only</span>
    </div>
    <div class="row g-3">
        <?php foreach ($agentValues as $v): ?>
            <?php if (($v['field_type'] ?? 'field') === 'heading'): ?>
                <div class="col-12"><h6 class="text-primary fw-bold border-bottom pb-1 mt-2"><?= htmlspecialchars($v['label']) ?></h6></div>
            <?php elseif (($v['field_type'] ?? 'field') === 'subheading'): ?>
                <div class="col-12"><h6 class="text-dark mt-1" style="font-size:0.95rem"><?= htmlspecialchars($v['label']) ?></h6></div>
            <?php elseif (empty($v['label'])): ?>
                <?php continue; ?>
            <?php else: ?>
                <?php $val = $v['value'] ?? '-'; if (empty($val) || $val === '0000-00-00 00:00:00') $val = '-'; ?>
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= htmlspecialchars($v['label']) ?></small>
                    <strong class="small"><?= htmlspecialchars($val) ?></strong>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== PREVIOUS SUBMISSIONS (if any) ===== -->
<?php if (!empty($allSubmissions) && count($allSubmissions) > 1): ?>
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Previous Submissions</h6>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr><th>Form</th><th>Submitted By</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($allSubmissions as $sub): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($sub['form_name'] ?? 'Form') ?></strong></td>
                    <td><small><?= htmlspecialchars($sub['submitted_by_name'] ?? 'Unknown') ?></small></td>
                    <td><span class="badge bg-<?= $sub['status'] === 'submitted' ? 'success' : 'secondary' ?>"><?= ucfirst($sub['status']) ?></span></td>
                    <td><small class="text-muted"><?= formatDate($sub['created_at']) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

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

<?php if (!$form): ?>
    <div class="alert alert-warning">No Pre-Login Checklist form configured. Please contact admin.</div>
<?php else: ?>
<form id="checklistForm" onsubmit="return false;">
    <?= csrfField() ?>
    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
    <input type="hidden" name="form_id" value="<?= $form['id'] ?>">

    <?php foreach ($form['sections'] as $section): ?>
    <?php $sectionLayout = $section['column_layout'] ?? 2; ?>
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
                $colClass = getFieldColClass($field, $sectionLayout);
                ?>
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
