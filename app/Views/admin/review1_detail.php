<?php require __DIR__ . '/../partials/form_renderer.php';

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
        <h4><i class="bi bi-clipboard-check me-2"></i>Review Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?> | <?= statusBadge($lead['workflow_stage']) ?></small>
    </div>
    <a href="<?= BASE_URL ?>/admin/review1" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
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
        <div class="col-md-2"><small class="text-muted d-block">Salary</small><strong><?= $lead['salary'] ? '₹' . number_format($lead['salary']) : '-' ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Stage</small><?= statusBadge($lead['workflow_stage']) ?></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Agent Form (Full Layout, Read Only) -->
        <?php if ($agentForm && !empty($agentForm['sections'])): ?>
        <div class="mb-4" style="border-left:4px solid #6c757d;padding-left:0">
            <div class="table-container" style="border-radius:0 12px 12px 0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-secondary mb-0">
                        <i class="bi bi-person-check me-2"></i>Agent Form
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
            <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-person-check me-2"></i>Agent Form</h5>
            <div class="alert alert-light mb-0">No agent form submission found.</div>
        </div>
        <?php endif; ?>

        <!-- Uploaded Documents -->
        <?php if (!empty($documents)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-paperclip me-1"></i> Uploaded Documents</h6>
            <div class="row g-2">
                <?php foreach ($documents as $doc): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center gap-2 p-2 bg-light rounded small">
                        <?php if (str_starts_with($doc['mime_type'] ?? '', 'image/')): ?>
                            <i class="bi bi-image text-primary"></i>
                        <?php elseif (($doc['mime_type'] ?? '') === 'application/pdf'): ?>
                            <i class="bi bi-file-pdf text-danger"></i>
                        <?php else: ?>
                            <i class="bi bi-file-earmark text-secondary"></i>
                        <?php endif; ?>
                        <div class="flex-grow-1 text-truncate">
                            <a href="<?= BASE_URL ?>/public/uploads/documents/<?= $lead['id'] ?>/<?= htmlspecialchars($doc['filename']) ?>" target="_blank" class="text-decoration-none">
                                <?= htmlspecialchars($doc['original_name']) ?>
                            </a>
                            <div class="text-muted" style="font-size:0.65rem">Uploaded by <?= htmlspecialchars($doc['uploaded_by_name'] ?? '') ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form Submissions History -->
        <?php if (!empty($allSubmissions)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Form Submissions History</h6>
            <table class="table table-sm small mb-0">
                <thead><tr><th>Form</th><th>Submitted By</th><th>Role</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($allSubmissions as $sub): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($sub['form_name'] ?? '') ?></strong></td>
                    <td><?= htmlspecialchars($sub['submitted_by_name'] ?? '') ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$sub['role_name'] ?? ''))) ?></span></td>
                    <td><span class="badge bg-<?= $sub['status']==='submitted'?'success':'warning' ?>"><?= ucfirst($sub['status']) ?></span></td>
                    <td><?= formatDate($sub['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Review Panel (RIGHT SIDE) -->
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

        <!-- Review Form -->
        <div class="table-container">
            <h6 class="fw-bold mb-3">Your Review</h6>
            <form id="reviewForm">
                <?= csrfField() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Admin Approval 1 Remark</label>
                    <textarea name="admin_approval1_remark" class="form-control form-control-sm" rows="3" placeholder="Enter your remarks..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Assign to Login Agent</label>
                    <select name="assigned_to" class="form-select form-select-sm">
                        <option value="">Select Login Agent</option>
                        <?php foreach ($loginAgents as $agent): ?>
                            <option value="<?= $agent['id'] ?>"><?= htmlspecialchars($agent['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="processReview('approve')">
                        <i class="bi bi-check-lg me-1"></i> Approve & Assign to Login Agent
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="processReview('reassign')">
                        <i class="bi bi-arrow-repeat me-1"></i> Reassign
                    </button>
                    <button type="button" class="btn btn-danger" onclick="processReview('reject')">
                        <i class="bi bi-x-lg me-1"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function processReview(action) {
    if (action === 'reject' && !confirm('Are you sure you want to reject this lead?')) return;
    const form = document.getElementById('reviewForm');
    const formData = new FormData(form);
    formData.append('action', action);
    try {
        const response = await fetch('<?= BASE_URL ?>/admin/review1/process', {
            method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            window.location.href = '<?= BASE_URL ?>/admin/review1';
        } else {
            alert(result.error || 'Error occurred.');
        }
    } catch (err) {
        alert('Network error.');
    }
}
</script>
