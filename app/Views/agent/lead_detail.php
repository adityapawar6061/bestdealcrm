<?php
// Workflow steps for progress bar
$workflowSteps = [
    'LEAD_UPLOADED'       => ['label' => 'Lead Uploaded',      'icon' => 'bi-cloud-upload'],
    'LEAD_ASSIGNED'       => ['label' => 'Assigned',            'icon' => 'bi-person-check'],
    'AGENT_DRAFT'         => ['label' => 'Agent Draft',         'icon' => 'bi-pencil-square'],
    'AGENT_SUBMITTED'     => ['label' => 'Agent Submitted',     'icon' => 'bi-send'],
    'ADMIN_REVIEW_1'      => ['label' => 'Admin Review 1',      'icon' => 'bi-clipboard-check'],
    'LOGIN_AGENT_ASSIGNED'=> ['label' => 'Login Agent',         'icon' => 'bi-person-badge'],
    'LOGIN_AGENT_DRAFT'   => ['label' => 'Login Agent Draft',   'icon' => 'bi-pencil-square'],
    'LOGIN_AGENT_SUBMITTED'=> ['label' => 'Login Submitted',    'icon' => 'bi-send'],
    'RETURNED_TO_AGENT'   => ['label' => 'Returned',            'icon' => 'bi-arrow-return-left'],
    'ADMIN_REVIEW_2'      => ['label' => 'Admin Review 2',      'icon' => 'bi-clipboard2-check'],
    'LOGIN_APPROVED'      => ['label' => 'Approved',            'icon' => 'bi-check-circle'],
    'POST_LOGIN'          => ['label' => 'Post Login',          'icon' => 'bi-clipboard-data'],
    'UNDERWRITING'        => ['label' => 'Underwriting',        'icon' => 'bi-file-earmark-check'],
    'DISPATCH'            => ['label' => 'Dispatch',            'icon' => 'bi-truck'],
    'COMPLETED'           => ['label' => 'Completed',           'icon' => 'bi-trophy'],
    'REJECTED'            => ['label' => 'Rejected',            'icon' => 'bi-x-circle'],
];

// $assignments is passed from the controller
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-person-lines-fill me-2"></i>Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?></small>
    </div>
    <div class="d-flex gap-2">
        <?php if (in_array($lead['workflow_stage'], ['LEAD_ASSIGNED', 'AGENT_DRAFT', 'RETURNED_TO_AGENT'])): ?>
            <a href="<?= BASE_URL ?>/agent/leads/<?= $lead['id'] ?>/fill-form" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square me-1"></i> Fill Form
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/agent/leads" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
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
            // For rejected leads, highlight the rejection point
            if ($lead['workflow_stage'] === 'REJECTED' && $stage === 'REJECTED') { $isActive = true; $isCompleted = false; }
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
        <span class="badge bg-<?= in_array($lead['workflow_stage'], ['COMPLETED']) ? 'success' : ($lead['workflow_stage'] === 'REJECTED' ? 'danger' : 'primary') ?>">
            <?= humanStatus($lead['workflow_stage']) ?>
        </span>
        <small class="text-muted ms-2">Last updated: <?= formatDate($lead['updated_at'] ?? $lead['created_at']) ?></small>
    </div>
</div>

<!-- Lead Info -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1"></i> Lead Information</h6>
    <div class="row g-3">
        <?php
        $info = [
            'Customer'       => $lead['customer_name'] ?? '-',
            'Mobile'         => $lead['mobile_number'] ?? '-',
            'Location'       => $lead['location'] ?? '-',
            'State'          => $lead['state'] ?? '-',
            'Bank'           => $lead['bank_name'] ?? '-',
            'Data Type'      => $lead['data_type'] ?? '-',
            'Existing LA'    => $lead['existing_la'] ?? '-',
            'Salary'         => $lead['salary'] ? '₹' . number_format($lead['salary']) : '-',
            'Actual Salary'  => $lead['actual_salary'] ?? '-',
            'Response Date'  => $lead['response_date'] ?? '-',
            'Current Disposition' => ($lead['disposition'] ?? $lead['agent_disposition'] ?? '') ?: '<span class="text-muted">Pending</span>',
            'Agent Remarks'  => $lead['agent_remark'] ?? '-',
        ];
        foreach ($info as $label => $value):
        ?>
        <div class="col-md-3">
            <small class="text-muted d-block"><?= $label ?></small>
            <strong class="small"><?= is_string($value) && !str_starts_with($value, '<') ? htmlspecialchars($value) : $value ?></strong>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Who Worked On This Lead -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-people me-1"></i> Who Worked On This Lead</h6>
    <?php if (empty($assignments)): ?>
        <p class="text-muted small mb-0">No assignment history recorded.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Assigned To</th>
                        <th>Assigned By</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $a): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($a['assigned_to_name'] ?? 'Unknown') ?></strong></td>
                        <td><?= htmlspecialchars($a['assigned_by_name'] ?? 'System') ?></td>
                        <td><small class="text-muted"><?= formatDate($a['assigned_at']) ?></small></td>
                        <td><span class="badge bg-<?= $a['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($a['status']) ?></span></td>
                        <td><small class="text-muted"><?= htmlspecialchars($a['remark'] ?? '-') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Form Submissions -->
<?php if (!empty($submissions)): ?>
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-1"></i> Form Submissions</h6>
    <?php foreach ($submissions as $sub): ?>
    <div class="border rounded p-3 mb-2">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong><?= htmlspecialchars($sub['form_name'] ?? 'Form') ?></strong>
                <small class="text-muted ms-2">by <?= htmlspecialchars($sub['submitted_by_name'] ?? 'Unknown') ?></small>
            </div>
            <span class="badge bg-<?= $sub['status'] === 'submitted' ? 'success' : 'secondary' ?>"><?= ucfirst($sub['status']) ?></span>
        </div>
        <small class="text-muted"><?= formatDate($sub['created_at']) ?></small>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Timeline -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Activity Timeline</h6>
    <?php if (empty($timeline)): ?>
        <p class="text-muted small">No activity recorded.</p>
    <?php else: ?>
        <?php foreach (array_reverse($timeline) as $event): ?>
        <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px">
                <i class="bi bi-arrow-right-circle text-primary small"></i>
            </div>
            <div class="flex-grow-1">
                <div class="small">
                    <strong><?= htmlspecialchars($event['performed_by_name'] ?? 'System') ?></strong>
                    <span class="text-muted">— <?= htmlspecialchars(str_replace('_', ' ', $event['action'] ?? $event['new_stage'])) ?></span>
                    <?php if (!empty($event['user_role'])): ?>
                        <span class="badge bg-light text-dark" style="font-size:0.6rem"><?= ucfirst(str_replace('_', ' ', $event['user_role'])) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($event['remark'])): ?>
                    <div class="small text-muted fst-italic">"<?= htmlspecialchars($event['remark']) ?>"</div>
                <?php endif; ?>
                <small class="text-muted"><?= formatDate($event['created_at'], 'd M, h:i A') ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
