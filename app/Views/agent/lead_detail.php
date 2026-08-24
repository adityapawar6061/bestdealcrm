<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-person-lines-fill me-2"></i>Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/agent/leads" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Lead Info -->
<div class="table-container mb-4">
    <div class="row g-2">
        <?php
        $info = [
            'Customer' => $lead['customer_name'] ?? '-',
            'Mobile' => $lead['mobile_number'] ?? '-',
            'Location' => $lead['location'] ?? '-',
            'Bank' => $lead['bank_name'] ?? '-',
            'Salary' => $lead['salary'] ? '₹' . number_format($lead['salary']) : '-',
            'Status' => statusBadge($lead['workflow_stage']),
        ];
        foreach ($info as $label => $value):
        ?>
        <div class="col-md-3">
            <small class="text-muted d-block"><?= $label ?></small>
            <strong><?= is_string($value) && !str_starts_with($value, '<') ? htmlspecialchars($value) : $value ?></strong>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Actions -->
<?php if (in_array($lead['workflow_stage'], ['LEAD_ASSIGNED', 'AGENT_DRAFT', 'RETURNED_TO_AGENT'])): ?>
<div class="mb-4">
    <a href="/bestdealcrm/agent/leads/<?= $lead['id'] ?>/fill-form" class="btn btn-primary">
        <i class="bi bi-pencil-square me-1"></i> Fill Form
    </a>
</div>
<?php endif; ?>

<!-- Submissions -->
<?php if (!empty($submissions)): ?>
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3">Form Submissions</h6>
    <?php foreach ($submissions as $sub): ?>
    <div class="border rounded p-3 mb-2">
        <div class="d-flex justify-content-between">
            <strong><?= htmlspecialchars($sub['form_name'] ?? 'Form') ?></strong>
            <span class="badge bg-<?= $sub['status'] === 'submitted' ? 'success' : 'secondary' ?>"><?= ucfirst($sub['status']) ?></span>
        </div>
        <small class="text-muted">Submitted on <?= formatDate($sub['created_at']) ?></small>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Timeline -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Timeline</h6>
    <?php if (empty($timeline)): ?>
        <p class="text-muted small">No activity recorded.</p>
    <?php else: ?>
        <?php foreach ($timeline as $event): ?>
        <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px">
                <i class="bi bi-arrow-right-circle text-primary small"></i>
            </div>
            <div>
                <div class="small">
                    <strong><?= htmlspecialchars($event['performed_by_name'] ?? 'System') ?></strong>
                    <span class="text-muted">— <?= htmlspecialchars(str_replace('_', ' ', $event['action'] ?? $event['new_stage'])) ?></span>
                </div>
                <small class="text-muted"><?= formatDate($event['created_at'], 'd M, h:i A') ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
