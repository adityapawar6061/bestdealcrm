<?php
// Include shared form renderer helpers
require __DIR__ . '/../partials/form_renderer.php';

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
        <h4><i class="bi bi-clipboard-data me-2"></i>Underwriting: Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?> | <?= statusBadge($lead['workflow_stage']) ?></small>
    </div>
    <a href="<?= BASE_URL ?>/underwriting/cases" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
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
    <div class="row g-3">
        <div class="col-md-2"><small class="text-muted d-block">Customer</small><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Mobile</small><strong><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Location</small><strong><?= htmlspecialchars($lead['location'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Bank</small><strong><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Assigned To</small><strong><?= htmlspecialchars($assignedAgentName ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Stage</small><?= statusBadge($lead['workflow_stage']) ?></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">

        <!-- ===== SECTION 1: AGENT FORM (Full Layout, Read Only) ===== -->
        <?php if ($agentForm && !empty($agentForm['sections'])): ?>
        <div class="mb-4" style="border-left:4px solid #6c757d;padding-left:0">
            <div class="table-container" style="border-radius:0 12px 12px 0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-secondary mb-0">
                        <i class="bi bi-person-check me-2"></i>1. Agent Form
                        <?php if (!empty($agentName)): ?>
                            <small class="fw-normal text-muted ms-2">by <?= htmlspecialchars($agentName) ?></small>
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-secondary">Read Only — Submitted by Agent</span>
                </div>
                <?php foreach ($agentForm['sections'] as $section): ?>
                    <?php renderFormSectionReadonly($section, $agentFormValues, $lead['id'], $agentName ?? '', 'agent'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="table-container mb-4" style="border-left:4px solid #6c757d">
            <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-person-check me-2"></i>1. Agent Form</h5>
            <div class="alert alert-light mb-0">No agent form submission found.</div>
        </div>
        <?php endif; ?>

        <!-- ===== SECTION 2: PRE-LOGIN CHECKLIST (Full Layout, Read Only) ===== -->
        <?php if ($preLoginForm && !empty($preLoginForm['sections'])): ?>
        <div class="mb-4" style="border-left:4px solid #0dcaf0;padding-left:0">
            <div class="table-container" style="border-radius:0 12px 12px 0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-info mb-0">
                        <i class="bi bi-clipboard-check me-2"></i>2. Pre-Login Checklist
                        <?php if (!empty($preLoginName)): ?>
                            <small class="fw-normal text-muted ms-2">by <?= htmlspecialchars($preLoginName) ?></small>
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-info">Read Only</span>
                </div>
                <?php foreach ($preLoginForm['sections'] as $section): ?>
                    <?php renderFormSectionReadonly($section, $preLoginFormValues, $lead['id'], $preLoginName ?? '', 'login_agent'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="table-container mb-4" style="border-left:4px solid #0dcaf0">
            <h5 class="fw-bold text-info mb-3"><i class="bi bi-clipboard-check me-2"></i>2. Pre-Login Checklist</h5>
            <div class="alert alert-light mb-0">No pre-login submission found.</div>
        </div>
        <?php endif; ?>

        <!-- ===== SECTION 3: POST-LOGIN FORM (Full Layout, Read Only) ===== -->
        <?php if ($postLoginForm && !empty($postLoginForm['sections'])): ?>
        <div class="mb-4" style="border-left:4px solid #198754;padding-left:0">
            <div class="table-container" style="border-radius:0 12px 12px 0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-success mb-0">
                        <i class="bi bi-clipboard-data me-2"></i>3. Post-Login Form
                        <?php if (!empty($postLoginName)): ?>
                            <small class="fw-normal text-muted ms-2">by <?= htmlspecialchars($postLoginName) ?></small>
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-success">Read Only</span>
                </div>
                <?php foreach ($postLoginForm['sections'] as $section): ?>
                    <?php renderFormSectionReadonly($section, $postLoginFormValues, $lead['id'], $postLoginName ?? '', 'login_agent'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="table-container mb-4" style="border-left:4px solid #198754">
            <h5 class="fw-bold text-success mb-3"><i class="bi bi-clipboard-data me-2"></i>3. Post-Login Form</h5>
            <div class="alert alert-light mb-0">No post-login submission found.</div>
        </div>
        <?php endif; ?>

        <!-- ===== SECTION 4: UNDERWRITING FORM (Editable) ===== -->
        <?php if ($uwForm): ?>
        <div class="table-container mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-warning mb-0">
                    <i class="bi bi-file-earmark-check me-2"></i>4. Underwriting Form (Fill Below)
                </h5>
                <span class="badge bg-warning text-dark">Editable</span>
            </div>

            <form id="uwForm" onsubmit="return false;">
                <?= csrfField() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">

                <?php foreach ($uwForm['sections'] as $section): ?>
                    <?php $sectionLayout = $section['column_layout'] ?? 2; ?>
                    <div class="mb-4">
                        <h6 class="small fw-bold text-muted mb-2 border-bottom pb-1">
                            <i class="bi bi-card-list me-1"></i> <?= htmlspecialchars($section['name']) ?>
                            <small class="fw-normal">(<?= (int)$sectionLayout ?> column<?= $sectionLayout > 1 ? 's' : '' ?>)</small>
                        </h6>
                        <div class="row g-3">
                            <?php foreach ($section['fields'] as $field): ?>
                                <?php
                                $value = $uwFormValues[$field['id']] ?? $field['default_value'] ?? '';
                                $isRequired = $field['required'] ? 'required' : '';
                                $fieldName = "form_data[{$field['id']}]";
                                $colClass = getFieldColClass($field, $sectionLayout);
                                $fieldType = $field['field_type'] ?? 'field';
                                ?>
                                <?php if ($fieldType === 'heading'): ?>
                                    <div class="col-12"><h5 class="text-primary mt-2"><?= htmlspecialchars($field['label']) ?></h5></div>
                                <?php elseif ($fieldType === 'subheading'): ?>
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
                                        <input type="file" name="<?= $fieldName ?>" class="form-control form-control-sm" <?= $field['required'] ? 'required' : '' ?>>
                                    <?php elseif (in_array($field['type'], ['number', 'decimal'])): ?>
                                        <input type="number" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?> step="<?= $field['type'] === 'decimal' ? '0.01' : '1' ?>">
                                    <?php else: ?>
                                        <input type="<?= $field['type'] === 'mobile' ? 'tel' : ($field['type'] === 'email' ? 'email' : 'text') ?>" name="<?= $fieldName ?>" class="form-control form-control-sm" value="<?= htmlspecialchars($value) ?>" <?= $isRequired ?>>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </form>
        </div>
        <?php else: ?>
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i> No Underwriting form configured.</div>
        <?php endif; ?>

        <!-- ===== DOCUMENTS ===== -->
        <?php if (!empty($documents)): ?>
        <div class="table-container mb-4">
            <?php renderUploadedDocuments($documents); ?>
        </div>
        <?php endif; ?>

        <!-- ===== SUBMISSIONS HISTORY ===== -->
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

        <!-- ===== REMARKS ===== -->
        <?php if (!empty($remarks)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-1"></i> All Remarks</h6>
            <?php foreach ($remarks as $r): ?>
            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong class="small text-primary"><?= htmlspecialchars($r['user_name'] ?? 'System') ?></strong>
                        <small class="text-muted"><?= formatDate($r['created_at'], 'd M, h:i A') ?></small>
                    </div>
                    <small class="text-muted d-block"><?= htmlspecialchars($r['stage'] ?? '') ?></small>
                    <div class="small mt-1"><?= nl2br(htmlspecialchars($r['remark'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- Timeline -->
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3">Timeline</h6>
            <?php foreach ($timeline as $event): ?>
            <div class="d-flex gap-2 mb-2 pb-2 border-bottom small">
                <div>
                    <strong><?= htmlspecialchars($event['performed_by_name'] ?? 'System') ?></strong>
                    <span class="text-muted">— <?= htmlspecialchars(str_replace('_', ' ', $event['action'] ?? $event['new_stage'])) ?></span>
                    <div class="text-muted" style="font-size:0.75rem"><?= formatDate($event['created_at'], 'd M, h:i A') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Actions -->
        <div class="table-container mb-3">
            <h6 class="fw-bold mb-3">Underwriting Form Actions</h6>
            <div class="d-grid gap-2 mb-3">
                <button type="button" class="btn btn-outline-warning" onclick="saveUwDraft()"><i class="bi bi-save me-1"></i> Save Draft</button>
                <button type="button" class="btn btn-primary" onclick="submitUwForm()"><i class="bi bi-send me-1"></i> Submit Underwriting Form</button>
            </div>
        </div>
        <div class="table-container">
            <h6 class="fw-bold mb-3">Underwriting Decision</h6>
            <form id="uwDecisionForm">
                <?= csrfField() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Underwriting Remark</label>
                    <textarea name="underwriting_remark" class="form-control form-control-sm" rows="3" placeholder="Enter remarks..."></textarea>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="processCase('approve')"><i class="bi bi-check-lg me-1"></i> Approve & Send to Dispatch</button>
                    <button type="button" class="btn btn-danger" onclick="processCase('reject')"><i class="bi bi-x-lg me-1"></i> Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function saveUwDraft() {
    var form = document.getElementById('uwForm');
    if (!form) return;
    var formData = new FormData(form);
    fetch(BASE_URL + '/underwriting/cases/save-draft', {
        method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) showToast(result.message, 'success');
        else showToast(result.error || 'Save failed.', 'danger');
    }).catch(function() { showToast('Network error.', 'danger'); });
}

function submitUwForm() {
    if (!confirm('Submit underwriting form?')) return;
    var form = document.getElementById('uwForm');
    if (!form) return;
    var formData = new FormData(form);
    fetch(BASE_URL + '/underwriting/cases/submit-form', {
        method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success) showToast(result.message, 'success');
        else showToast(result.error || 'Submit failed.', 'danger');
    }).catch(function() { showToast('Network error.', 'danger'); });
}

async function processCase(action) {
    if (action === 'reject' && !confirm('Reject this case?')) return;
    var remark = document.querySelector('#uwDecisionForm textarea[name="underwriting_remark"]').value;
    if (!remark && action === 'reject') { alert('Please enter a remark before rejecting.'); return; }
    var fd = new FormData(document.getElementById('uwDecisionForm'));
    fd.append('action', action);
    try {
        var r = await fetch(BASE_URL + '/underwriting/cases/process', {
            method: 'POST', body: fd,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        var result = await r.json();
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(function() { window.location.href = BASE_URL + '/underwriting/cases'; }, 1000);
        } else {
            showToast(result.error || 'Error occurred.', 'danger');
        }
    } catch(e) {
        showToast('Network error: ' + e.message, 'danger');
    }
}
</script>
