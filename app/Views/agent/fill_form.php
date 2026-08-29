<?php
// Determine if form is in read-only mode (already submitted)
$stagesReadOnly = ['AGENT_SUBMITTED', 'ADMIN_REVIEW_1', 'LOGIN_AGENT_ASSIGNED', 'LOGIN_AGENT_DRAFT', 'LOGIN_AGENT_SUBMITTED', 'RETURNED_TO_AGENT', 'ADMIN_REVIEW_2', 'LOGIN_APPROVED', 'POST_LOGIN', 'UNDERWRITING', 'DISPATCH', 'COMPLETED', 'REJECTED'];
$isReadOnly = in_array($lead['workflow_stage'], $stagesReadOnly);
$isEditable = in_array($lead['workflow_stage'], ['LEAD_ASSIGNED', 'AGENT_DRAFT', 'RETURNED_TO_AGENT']);

// Workflow steps for progress bar
$workflowSteps = [
    'LEAD_UPLOADED'     => ['label' => 'Lead Uploaded',  'icon' => 'bi-cloud-upload'],
    'LEAD_ASSIGNED'     => ['label' => 'Assigned',        'icon' => 'bi-person-check'],
    'AGENT_DRAFT'       => ['label' => 'Agent Working',   'icon' => 'bi-pencil-square'],
    'AGENT_SUBMITTED'   => ['label' => 'Submitted',       'icon' => 'bi-send'],
    'ADMIN_REVIEW_1'    => ['label' => 'Admin Review',    'icon' => 'bi-clipboard-check'],
    'LOGIN_AGENT_ASSIGNED' => ['label' => 'Login Agent', 'icon' => 'bi-person-badge'],
    'ADMIN_REVIEW_2'    => ['label' => 'Review 2',        'icon' => 'bi-clipboard2-check'],
    'LOGIN_APPROVED'    => ['label' => 'Approved',        'icon' => 'bi-check-circle'],
    'UNDERWRITING'      => ['label' => 'Underwriting',    'icon' => 'bi-file-earmark-check'],
    'DISPATCH'          => ['label' => 'Dispatch',        'icon' => 'bi-truck'],
    'COMPLETED'         => ['label' => 'Completed',       'icon' => 'bi-trophy'],
];

// Use sections in their saved display_order from the form builder
// (getFullForm already orders by display_order)
$sortedSections = $form['sections'] ?? [];

// Helper: determine column class based on section layout and field type
function getFieldColClass($field, $sectionLayout = 2) {
    $type = $field['type'] ?? 'text';
    $fieldType = $field['field_type'] ?? 'field';
    // Headings/subheadings always full width
    if ($fieldType === 'heading' || $fieldType === 'subheading') return 'col-12';
    // Textareas always full width
    if ($type === 'textarea') return 'col-12';
    switch ((int)$sectionLayout) {
        case 1:  return 'col-md-12';
        case 3:  return 'col-md-4';
        default: return 'col-md-6';
    }
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-pencil-square me-2"></i><?= $isReadOnly ? 'View Lead Form' : 'Fill Lead Form' ?></h4>
        <small class="text-muted">Lead #<?= $lead['id'] ?> — <?= htmlspecialchars($lead['customer_name'] ?? '') ?></small>
    </div>
    <a href="<?= BASE_URL ?>/agent/leads" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Workflow Progress Bar -->
<div class="table-container mb-4 p-3">
    <h6 class="fw-bold mb-3"><i class="bi bi-diagram-3 me-1"></i> Workflow Progress</h6>
    <div class="d-flex align-items-center flex-wrap gap-1" style="overflow-x:auto">
        <?php
        $stepKeys = array_keys($workflowSteps);
        $currentStageIndex = array_search($lead['workflow_stage'], $stepKeys);
        if ($currentStageIndex === false) $currentStageIndex = 0;
        foreach ($workflowSteps as $stage => $info):
            $stageIndex = array_search($stage, $stepKeys);
            $isActive = ($stage === $lead['workflow_stage']);
            $isCompleted = ($stageIndex < $currentStageIndex);
            $isRejected = ($lead['workflow_stage'] === 'REJECTED' && $stage === 'ADMIN_REVIEW_1');
        ?>
            <div class="text-center flex-shrink-0" style="min-width:80px">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1
                    <?= $isActive ? 'bg-primary text-white' : ($isCompleted ? 'bg-success text-white' : 'bg-light text-muted') ?>"
                    style="width:36px;height:36px;font-size:0.85rem">
                    <i class="bi <?= $info['icon'] ?>"></i>
                </div>
                <div class="small <?= $isActive ? 'fw-bold text-primary' : ($isCompleted ? 'text-success' : 'text-muted') ?>" style="font-size:0.65rem;line-height:1.1">
                    <?= $info['label'] ?>
                </div>
            </div>
            <?php if ($stageIndex < count($stepKeys) - 1): ?>
                <div class="flex-shrink-0" style="width:20px;height:2px;background:<?= $isCompleted ? '#22c55e' : '#e2e8f0' ?>;margin-bottom:18px"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <div class="mt-2">
        <span class="badge bg-<?= $isReadOnly ? 'secondary' : ($isEditable ? 'primary' : 'info') ?>">
            <?= humanStatus($lead['workflow_stage']) ?>
        </span>
        <?php if ($isReadOnly): ?>
            <small class="text-muted ms-2">This form is read-only. It has already been submitted.</small>
        <?php endif; ?>
    </div>
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

    <?php foreach ($sortedSections as $section): ?>
    <?php
        $isAdminSection = false;
        $lowerSec = strtolower($section['name'] ?? '');
        if (strpos($lowerSec, 'admin') !== false) $isAdminSection = true;
        $sectionLayout = $section['column_layout'] ?? 2;
    ?>
    <div class="table-container mb-4 <?= $isAdminSection ? 'border-start border-4 border-primary' : '' ?>">
        <h6 class="fw-bold mb-3 <?= $isAdminSection ? 'text-primary' : '' ?>">
            <?php if ($isAdminSection): ?>
                <i class="bi bi-lock me-1"></i>
            <?php else: ?>
                <i class="bi bi-card-list me-1"></i>
            <?php endif; ?>
            <?= htmlspecialchars($section['name']) ?>
            <?php if ($isAdminSection): ?>
                <span class="badge bg-secondary ms-2" style="font-size:0.65rem">READ ONLY</span>
            <?php endif; ?>
            <small class="text-muted fw-normal ms-2">(<?= (int)$sectionLayout ?> column<?= $sectionLayout > 1 ? 's' : '' ?>)</small>
        </h6>

        <div class="row g-3">
            <?php foreach ($section['fields'] as $field): ?>
                <?php
                $value = $existingValues[$field['id']] ?? $field['default_value'] ?? '';
                $fieldName = "form_data[{$field['id']}]";
                $fn = strtolower($field['field_name'] ?? '');
                $fl = strtolower($field['label'] ?? '');
                $fieldType = $field['field_type'] ?? 'field';

                // Skip hidden fields
                if (!empty($field['is_hidden'])) continue;

                // Auto-fill agent name
                if (empty($value) && (strpos($fn, 'agent_name') !== false || strpos($fl, 'agent name') !== false)) {
                    $value = currentUser()['name'];
                }

                // Determine if this field should be read-only
                $fieldReadOnly = false;
                if ($isReadOnly) {
                    $fieldReadOnly = true;
                } elseif ($isAdminSection) {
                    $fieldReadOnly = true;
                } elseif (strpos($fn, 'agent_name') !== false || strpos($fn, 'product_type') !== false || strpos($fl, 'agent name') !== false || strpos($fl, 'product type') !== false) {
                    $fieldReadOnly = true;
                }

                $roAttr = $fieldReadOnly ? 'readonly disabled' : '';
                $roClass = $fieldReadOnly ? 'bg-light' : '';
                $colClass = getFieldColClass($field, $sectionLayout);
                ?>
                <div class="<?= $colClass ?>">
                    <?php if ($field['type'] === 'heading'): ?>
                        <h5 class="mt-2"><?= htmlspecialchars($field['label']) ?></h5>
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required'] && !$fieldReadOnly): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <textarea name="<?= $fieldName ?>" class="form-control form-control-sm <?= $roClass ?>" rows="3" <?= $fieldReadOnly ? '' : ($field['required'] ? 'required' : '') ?> <?= $roAttr ?> placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>"><?= htmlspecialchars($value) ?></textarea>
                        <?php if ($fieldReadOnly && $value): ?><input type="hidden" name="<?= $fieldName ?>" value="<?= htmlspecialchars($value) ?>"><?php endif; ?>
                    <?php elseif ($field['type'] === 'dropdown'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required'] && !$fieldReadOnly): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <select name="<?= $fieldName ?>" class="form-select form-select-sm <?= $roClass ?>" <?= $fieldReadOnly ? 'disabled' : ($field['required'] ? 'required' : '') ?>>
                            <?php if (!$fieldReadOnly): ?><option value="">Select...</option><?php endif; ?>
                            <?php foreach ($field['options'] ?? [] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt['value']) ?>" <?= $value === $opt['value'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($opt['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($fieldReadOnly && $value): ?><input type="hidden" name="<?= $fieldName ?>" value="<?= htmlspecialchars($value) ?>"><?php endif; ?>
                    <?php elseif ($field['type'] === 'radio'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required'] && !$fieldReadOnly): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <div class="d-flex gap-3">
                            <?php foreach ($field['options'] ?? [] as $opt): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="<?= $fieldName ?>" value="<?= htmlspecialchars($opt['value']) ?>" <?= $value === $opt['value'] ? 'checked' : '' ?> <?= $fieldReadOnly ? 'disabled' : ($field['required'] ? 'required' : '') ?>>
                                    <label class="form-check-label small"><?= htmlspecialchars($opt['label']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($fieldReadOnly && $value): ?><input type="hidden" name="<?= $fieldName ?>" value="<?= htmlspecialchars($value) ?>"><?php endif; ?>
                    <?php elseif ($field['type'] === 'checkbox'): ?>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="<?= $fieldName ?>" value="1" <?= $value ? 'checked' : '' ?> <?= $fieldReadOnly ? 'disabled' : '' ?>>
                            <label class="form-check-label small fw-semibold"><?= htmlspecialchars($field['label']) ?></label>
                        </div>
                        <?php if ($fieldReadOnly && $value): ?><input type="hidden" name="<?= $fieldName ?>" value="1"><?php endif; ?>
                    <?php elseif ($field['type'] === 'file' || $field['type'] === 'image'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required'] && !$fieldReadOnly): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <?php if ($fieldReadOnly): ?>
                            <div class="form-control form-control-sm bg-light"><?= $value ? htmlspecialchars($value) : '—' ?></div>
                        <?php else: ?>
                            <input type="file" name="<?= $fieldName ?>" class="form-control form-control-sm" <?= $field['required'] ? 'required' : '' ?> <?= $field['type'] === 'image' ? 'accept="image/*"' : '' ?>>
                        <?php endif; ?>
                    <?php elseif ($field['type'] === 'date'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required'] && !$fieldReadOnly): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="date" name="<?= $fieldName ?>" class="form-control form-control-sm <?= $roClass ?>" value="<?= htmlspecialchars($value) ?>" <?= $fieldReadOnly ? $roAttr : ($field['required'] ? 'required' : '') ?>>
                        <?php if ($fieldReadOnly && $value): ?><input type="hidden" name="<?= $fieldName ?>" value="<?= htmlspecialchars($value) ?>"><?php endif; ?>
                    <?php elseif ($field['type'] === 'number' || $field['type'] === 'decimal'): ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required'] && !$fieldReadOnly): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="number" name="<?= $fieldName ?>" class="form-control form-control-sm <?= $roClass ?>" value="<?= htmlspecialchars($value) ?>" <?= $fieldReadOnly ? $roAttr : ($field['required'] ? 'required' : '') ?> step="<?= $field['type'] === 'decimal' ? '0.01' : '1' ?>" placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                        <?php if ($fieldReadOnly && $value): ?><input type="hidden" name="<?= $fieldName ?>" value="<?= htmlspecialchars($value) ?>"><?php endif; ?>
                    <?php else: ?>
                        <label class="form-label small fw-semibold">
                            <?= htmlspecialchars($field['label']) ?>
                            <?php if ($field['required'] && !$fieldReadOnly): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="<?= $field['type'] === 'mobile' ? 'tel' : $field['type'] ?>" name="<?= $fieldName ?>" class="form-control form-control-sm <?= $roClass ?>" value="<?= htmlspecialchars($value) ?>" <?= $fieldReadOnly ? $roAttr : ($field['required'] ? 'required' : '') ?> placeholder="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                        <?php if ($fieldReadOnly && $value): ?><input type="hidden" name="<?= $fieldName ?>" value="<?= htmlspecialchars($value) ?>"><?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Actions - only show when editable -->
    <?php if ($isEditable): ?>
    <div class="d-flex justify-content-end gap-2 mb-4">
        <button type="button" class="btn btn-outline-warning" onclick="submitAgentForm('draft')">
            <i class="bi bi-save me-1"></i> Save as Draft
        </button>
        <button type="button" class="btn btn-primary" onclick="submitAgentForm('submit')">
            <i class="bi bi-send me-1"></i> Submit to Admin
        </button>
    </div>
    <?php elseif ($isReadOnly): ?>
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-info-circle me-2"></i>
        <span>This form has been submitted and is now read-only. Current status: <?= humanStatus($lead['workflow_stage']) ?></span>
    </div>
    <?php endif; ?>
</form>
<?php endif; ?>

<script>
async function submitAgentForm(action) {
    const form = document.getElementById('agentForm');
    if (!form) return;

    // Validate required fields (only enabled ones)
    const requiredFields = form.querySelectorAll('[required]:not([disabled])');
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
        ? '<?= BASE_URL ?>/agent/leads/save-draft'
        : '<?= BASE_URL ?>/agent/leads/submit-form';

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(function() { window.location.href = '<?= BASE_URL ?>/agent/leads'; }, 1000);
        } else {
            showToast(result.error || 'An error occurred.', 'danger');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'danger');
    }
}
</script>
