<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-person-lines-fill me-2"></i>Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/admin/leads" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Lead Info -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="table-container">
            <h6 class="fw-bold mb-3">Lead Information</h6>
            <div class="row g-2">
                <?php 
                $fields = [
                    'Customer Name' => $lead['customer_name'] ?? '-',
                    'Mobile' => $lead['mobile_number'] ?? '-',
                    'Location' => $lead['location'] ?? '-',
                    'State' => $lead['state'] ?? '-',
                    'Bank' => $lead['bank_name'] ?? '-',
                    'Existing LA' => $lead['existing_la'] ?? '-',
                    'Salary' => $lead['salary'] ? '₹' . number_format($lead['salary']) : '-',
                    'Actual Salary' => $lead['actual_salary'] ? '₹' . number_format($lead['actual_salary']) : '-',
                    'PAN' => $lead['pan_number'] ?? '-',
                    'Current Status' => $lead['current_status'] ?? '-',
                    'Remark' => $lead['remark'] ?? '-',
                ];
                foreach ($fields as $label => $value):
                ?>
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= $label ?></small>
                    <strong><?= htmlspecialchars($value) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-4">
                    <small class="text-muted d-block">Assigned To</small>
                    <strong><?= htmlspecialchars($lead['assigned_to_name'] ?? 'Unassigned') ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Workflow Stage</small>
                    <?= statusBadge($lead['workflow_stage']) ?>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Created</small>
                    <strong><?= formatDate($lead['created_at']) ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <!-- Documents -->
        <div class="table-container">
            <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark me-1"></i> Documents</h6>
            <?php if (empty($documents)): ?>
                <p class="text-muted small">No documents uploaded.</p>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
                    <i class="bi bi-file-earmark text-primary"></i>
                    <div class="flex-grow-1">
                        <small class="d-block"><?= htmlspecialchars($doc['original_name'] ?? $doc['filename']) ?></small>
                        <small class="text-muted"><?= formatDate($doc['created_at'], 'd M Y (IST)') ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Submissions -->
<?php if (!empty($submissions)): ?>
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-1"></i> Form Submissions</h6>
    <?php foreach ($submissions as $sub): ?>
    <div class="border rounded p-3 mb-2">
        <div class="d-flex justify-content-between">
            <strong><?= htmlspecialchars($sub['form_name'] ?? 'Form') ?></strong>
            <span class="badge bg-<?= $sub['status'] === 'submitted' ? 'success' : 'secondary' ?>"><?= ucfirst($sub['status']) ?></span>
        </div>
        <small class="text-muted">Submitted by <?= htmlspecialchars($sub['submitted_by_name'] ?? '') ?> on <?= formatDate($sub['created_at']) ?></small>
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
        <div class="timeline">
            <?php foreach ($timeline as $event): ?>
            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                    <i class="bi bi-arrow-right-circle text-primary small"></i>
                </div>
                <div>
                    <div class="fw-semibold small">
                        <?= htmlspecialchars($event['performed_by_name'] ?? 'System') ?>
                        <span class="text-muted fw-normal">
                            — <?= htmlspecialchars(str_replace('_', ' ', $event['action'] ?? $event['new_stage'])) ?>
                        </span>
                    </div>
                    <div class="text-muted" style="font-size:0.8rem">
                        <?= htmlspecialchars($event['previous_stage'] ?? '') ?> → <?= htmlspecialchars($event['new_stage']) ?>
                    </div>
                    <?php if (!empty($event['remark'])): ?>
                        <div class="bg-light rounded p-2 mt-1 small"><?= htmlspecialchars($event['remark']) ?></div>
                    <?php endif; ?>
                    <small class="text-muted"><?= formatDate($event['created_at']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
