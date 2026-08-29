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

// Helper to render a field value in read-only mode (same look as fill form)
function renderReadOnlyField($field, $value) {
    $type = $field['type'] ?? 'text';
    $label = htmlspecialchars($field['label'] ?? '');
    $fieldName = htmlspecialchars($field['field_name'] ?? '');
    $val = htmlspecialchars($value ?? '');
    if (empty($val) || $val === '0000-00-00 00:00:00') $val = '—';

    $html = '<label class="form-label small fw-semibold">' . $label;
    if (!empty($field['required'])) $html .= ' <span class="text-danger">*</span>';
    $html .= '</label>';

    switch ($type) {
        case 'heading':
            return '<h5 class="text-primary fw-bold border-bottom pb-1 mt-2">' . $label . '</h5>';
        case 'subheading':
            return '<h6 class="text-dark mt-1" style="font-size:0.95rem">' . $label . '</h6>';
        case 'textarea':
            $html .= '<div class="form-control form-control-sm bg-light" style="min-height:60px;white-space:pre-wrap">' . ($val === '—' ? '<span class="text-muted">—</span>' : $val) . '</div>';
            return $html;
        case 'dropdown':
        case 'multi-select':
            $html .= '<input type="text" class="form-control form-control-sm bg-light" value="' . $val . '" readonly>';
            return $html;
        case 'radio':
            $options = $field['options'] ?? [];
            $html .= '<div class="d-flex gap-3">';
            foreach ($options as $opt) {
                $checked = ($value === $opt['value']);
                $html .= '<div class="form-check"><input class="form-check-input" type="radio" disabled ' . ($checked ? 'checked' : '') . '><label class="form-check-label small">' . htmlspecialchars($opt['label']) . '</label></div>';
            }
            $html .= '</div>';
            return $html;
        case 'checkbox':
            $checked = !empty($value);
            $html = '<div class="form-check mt-1"><input class="form-check-input" type="checkbox" disabled ' . ($checked ? 'checked' : '') . '><label class="form-check-label small fw-semibold">' . $label . '</label></div>';
            return $html;
        case 'file':
        case 'image':
            if ($val === '—' || empty($value)) {
                $html .= '<div class="form-control form-control-sm bg-light text-muted">—</div>';
            } else {
                $uploadDir = '/public/uploads/documents/';
                $html .= '<div class="form-control form-control-sm bg-light">';
                $html .= '<a href="' . BASE_URL . $uploadDir . $val . '" target="_blank" class="text-decoration-none">';
                $ext = strtolower(pathinfo($val, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $html .= '<img src="' . BASE_URL . $uploadDir . $val . '" style="max-height:60px" class="rounded me-1"> ';
                } elseif ($ext === 'pdf') {
                    $html .= '<i class="bi bi-file-pdf text-danger me-1"></i>';
                } else {
                    $html .= '<i class="bi bi-file-earmark me-1"></i>';
                }
                $html .= htmlspecialchars($val) . '</a></div>';
            }
            return $html;
        case 'date':
            $html .= '<input type="text" class="form-control form-control-sm bg-light" value="' . $val . '" readonly>';
            return $html;
        case 'number':
        case 'decimal':
            $html .= '<input type="text" class="form-control form-control-sm bg-light" value="' . $val . '" readonly>';
            return $html;
        case 'readonly':
            $html .= '<input type="text" class="form-control form-control-sm bg-light" value="' . $val . '" readonly>';
            return $html;
        default:
            $html .= '<input type="text" class="form-control form-control-sm bg-light" value="' . $val . '" readonly>';
            return $html;
    }
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard-check me-2"></i>Pre-Login Checklist</h4>
        <small class="text-muted">Lead #<?= $lead['id'] ?> - <?= htmlspecialchars($lead['customer_name'] ?? '') ?></small>
    </div>
    <a href="<?= BASE_URL ?>/login-agent/cases" class="btn btn-outline-secondary btn-sm">
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
        <div class="col-md-2"><small class="text-muted">Customer:</small> <strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted">Mobile:</small> <?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></div>
        <div class="col-md-2"><small class="text-muted">Status:</small> <?= statusBadge($lead['workflow_stage']) ?></div>
        <div class="col-md-2"><small class="text-muted">Bank:</small> <?= htmlspecialchars($lead['bank_name'] ?? '-') ?></div>
        <div class="col-md-2"><small class="text-muted">Salary:</small> <?= $lead['salary'] ? '₹' . number_format($lead['salary']) : '-' ?></div>
        <div class="col-md-2"><small class="text-muted">Location:</small> <?= htmlspecialchars($lead['location'] ?? '-') ?></div>
    </div>
</div>

<!-- ===== AGENT FORM (Full Layout, Read Only) ===== -->
<?php if ($agentForm && !empty($agentForm['sections'])): ?>
<div class="mb-4" style="border-left:4px solid #6c757d;padding-left:0">
    <div class="table-container" style="border-radius:0 12px 12px 0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-secondary mb-0">
                <i class="bi bi-person-check me-2"></i>Agent Form
            </h5>
            <span class="badge bg-secondary">Read Only — Submitted by Agent</span>
        </div>

        <?php foreach ($agentForm['sections'] as $section): ?>
            <?php $sectionLayout = $section['column_layout'] ?? 2; ?>
            <div class="mb-4">
                <h6 class="small fw-bold text-muted mb-2 border-bottom pb-1">
                    <i class="bi bi-card-list me-1"></i> <?= htmlspecialchars($section['name']) ?>
                    <small class="fw-normal">(<?= (int)$sectionLayout ?> column<?= $sectionLayout > 1 ? 's' : '' ?>)</small>
                </h6>
                <div class="row g-3">
                    <?php foreach ($section['fields'] as $field): ?>
                        <?php if (!empty($field['is_hidden'])) continue; ?>
                        <?php $value = $agentFormValues[$field['id']] ?? $field['default_value'] ?? ''; ?>
                        <div class="<?= getFieldColClass($field, $sectionLayout) ?>">
                            <?= renderReadOnlyField($field, $value) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php elseif (!empty($agentFormValues)): ?>
<!-- Fallback: old-style flat rendering if form structure unavailable -->
<div class="table-container mb-4" style="border-left:4px solid #6c757d">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold text-secondary mb-0"><i class="bi bi-person-check me-2"></i>Agent Form</h5>
        <span class="badge bg-secondary">Read Only</span>
    </div>
    <div class="row g-3">
        <?php foreach ($agentFormValues as $v): ?>
            <?php if (($v['field_type'] ?? 'field') === 'heading'): ?>
                <div class="col-12"><h5 class="text-primary fw-bold border-bottom pb-1 mt-2"><?= htmlspecialchars($v['label']) ?></h5></div>
            <?php elseif (($v['field_type'] ?? 'field') === 'subheading'): ?>
                <div class="col-12"><h6 class="text-dark mt-1"><?= htmlspecialchars($v['label']) ?></h6></div>
            <?php elseif (empty($v['label'])): ?>
                <?php continue; ?>
            <?php else: ?>
                <?php $val = $v['value'] ?? '-'; if (empty($val) || $val === '0000-00-00 00:00:00') $val = '—'; ?>
                <div class="col-md-6">
                    <small class="text-muted d-block"><?= htmlspecialchars($v['label']) ?></small>
                    <strong class="small"><?= htmlspecialchars($val) ?></strong>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== UPLOADED DOCUMENTS ===== -->
<?php if (!empty($documents)): ?>
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-paperclip me-1"></i> Uploaded Documents</h6>
    <div class="row g-2">
        <?php foreach ($documents as $doc): ?>
        <div class="col-md-3">
            <div class="border rounded p-2 text-center">
                <?php $ext = strtolower(pathinfo($doc['original_name'] ?? '', PATHINFO_EXTENSION)); ?>
                <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <a href="<?= BASE_URL ?>/public/uploads/documents/<?= $doc['lead_id'] ?>/<?= htmlspecialchars($doc['filename']) ?>" target="_blank">
                        <img src="<?= BASE_URL ?>/public/uploads/documents/<?= $doc['lead_id'] ?>/<?= htmlspecialchars($doc['filename']) ?>" 
                             alt="<?= htmlspecialchars($doc['original_name']) ?>" class="img-fluid rounded" style="max-height:100px">
                    </a>
                <?php elseif ($ext === 'pdf'): ?>
                    <a href="<?= BASE_URL ?>/public/uploads/documents/<?= $doc['lead_id'] ?>/<?= htmlspecialchars($doc['filename']) ?>" target="_blank" class="text-decoration-none">
                        <i class="bi bi-file-pdf text-danger" style="font-size:2rem"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/public/uploads/documents/<?= $doc['lead_id'] ?>/<?= htmlspecialchars($doc['filename']) ?>" target="_blank" class="text-decoration-none">
                        <i class="bi bi-file-earmark text-primary" style="font-size:2rem"></i>
                    </a>
                <?php endif; ?>
                <div class="mt-1">
                    <small class="text-muted d-block text-truncate" style="max-width:150px" title="<?= htmlspecialchars($doc['original_name']) ?>">
                        <?= htmlspecialchars($doc['original_name']) ?>
                    </small>
                    <small class="text-muted">by <?= htmlspecialchars($doc['uploaded_by_name'] ?? 'Unknown') ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== PREVIOUS SUBMISSIONS ===== -->
<?php if (!empty($allSubmissions) && count($allSubmissions) > 0): ?>
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Form Submissions History</h6>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr><th>Form</th><th>Submitted By</th><th>Role</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php foreach ($allSubmissions as $sub): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($sub['form_name'] ?? 'Form') ?></strong></td>
                    <td><small><?= htmlspecialchars($sub['submitted_by_name'] ?? 'Unknown') ?></small></td>
                    <td><span class="badge bg-light text-dark"><?= ucfirst(str_replace('_', ' ', $sub['role_name'] ?? '')) ?></span></td>
                    <td><span class="badge bg-<?= $sub['status'] === 'submitted' ? 'success' : 'secondary' ?>"><?= ucfirst($sub['status']) ?></span></td>
                    <td><small class="text-muted"><?= formatDate($sub['created_at']) ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ===== PRE-LOGIN CHECKLIST FORM (Editable) ===== -->
<?php if (!$form): ?>
    <div class="alert alert-warning">No Pre-Login Checklist form configured. Please contact admin.</div>
<?php else: ?>
<div class="table-container mb-4">
    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-clipboard-check me-2"></i>Pre-Login Checklist</h5>
    <form id="checklistForm" onsubmit="return false;">
        <?= csrfField() ?>
        <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
        <input type="hidden" name="form_id" value="<?= $form['id'] ?>">

        <?php foreach ($form['sections'] as $section): ?>
            <?php $sectionLayout = $section['column_layout'] ?? 2; ?>
            <div class="mb-4">
                <h6 class="small fw-bold text-muted mb-2 border-bottom pb-1">
                    <i class="bi bi-card-list me-1"></i> <?= htmlspecialchars($section['name']) ?>
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
                            <?php if (($field['field_type'] ?? 'field') === 'heading'): ?>
                                <h5 class="text-primary mt-2"><?= htmlspecialchars($field['label']) ?></h5>
                            <?php elseif (($field['field_type'] ?? 'field') === 'subheading'): ?>
                                <h6 class="text-dark mt-1"><?= htmlspecialchars($field['label']) ?></h6>
                            <?php elseif ($field['type'] === 'textarea'): ?>
                                <label class="form-label small fw-semibold">
                                    <?= htmlspecialchars($field['label']) ?>
                                    <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <textarea name="<?= $fieldName ?>" class="form-control form-control-sm" rows="2" <?= $isRequired ?> placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>"><?= htmlspecialchars($value) ?></textarea>
                            <?php elseif ($field['type'] === 'dropdown'): ?>
                                <label class="form-label small fw-semibold">
                                    <?= htmlspecialchars($field['label']) ?>
                                    <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <select name="<?= $fieldName ?>" class="form-select form-select-sm" <?= $isRequired ?>>
                                    <option value="">Select...</option>
                                    <?php foreach ($field['options'] ?? [] as $opt): ?>
                                        <option value="<?= htmlspecialchars($opt['value']) ?>" <?= $value === $opt['value'] ? 'selected' : '' ?>><?= htmlspecialchars($opt['label']) ?></option>
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
                                <input type="file" name="<?= $fieldName ?>" class="form-control form-control-sm" <?= $field['required'] ? 'required' : '' ?> accept="<?= $field['type'] === 'image' ? 'image/*' : '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png' ?>">
                            <?php elseif ($field['type'] === 'date'): ?>
                                <label class="form-label small fw-semibold">
                                    <?= htmlspecialchars($field['label']) ?>
                                    <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="date" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?>>
                            <?php elseif (in_array($field['type'], ['number', 'decimal'])): ?>
                                <label class="form-label small fw-semibold">
                                    <?= htmlspecialchars($field['label']) ?>
                                    <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="number" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?> step="<?= $field['type'] === 'decimal' ? '0.01' : '1' ?>">
                            <?php else: ?>
                                <label class="form-label small fw-semibold">
                                    <?= htmlspecialchars($field['label']) ?>
                                    <?php if ($field['required']): ?><span class="text-danger">*</span><?php endif; ?>
                                </label>
                                <input type="<?= $field['type'] === 'mobile' ? 'tel' : ($field['type'] === 'email' ? 'email' : 'text') ?>" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?> placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
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
</div>
<?php endif; ?>

<script>
async function submitChecklist(action) {
    const form = document.getElementById('checklistForm');
    const formData = new FormData(form);
    const url = action === 'draft'
        ? BASE_URL + '/login-agent/cases/save-draft'
        : BASE_URL + '/login-agent/cases/submit-checklist';

    try {
        const response = await fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(function() { window.location.href = BASE_URL + '/login-agent/cases'; }, 1000);
        } else {
            showToast(result.error || 'Error occurred.', 'danger');
        }
    } catch (err) {
        showToast('Network error.', 'danger');
    }
}

async function sendBackToAgent() {
    const remark = prompt('Remark (mandatory when sending back to agent):');
    if (!remark) return;

    const formData = new FormData(document.getElementById('checklistForm'));
    formData.append('remark', remark);

    try {
        const response = await fetch(BASE_URL + '/login-agent/cases/send-back', {
            method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(function() { window.location.href = BASE_URL + '/login-agent/cases'; }, 1000);
        } else {
            showToast(result.error || 'Error occurred.', 'danger');
        }
    } catch (err) {
        showToast('Network error.', 'danger');
    }
}
</script>
