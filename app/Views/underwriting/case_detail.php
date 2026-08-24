<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard-data me-2"></i>Underwriting: Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/underwriting/cases" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Lead Info -->
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3">Lead Information</h6>
            <div class="row g-2">
                <?php $fields = ['Customer' => $lead['customer_name'] ?? '-', 'Mobile' => $lead['mobile_number'] ?? '-', 'Location' => $lead['location'] ?? '-', 'Bank' => $lead['bank_name'] ?? '-', 'Stage' => statusBadge($lead['workflow_stage'])]; ?>
                <?php foreach ($fields as $label => $value): ?>
                <div class="col-md-4"><small class="text-muted d-block"><?= $label ?></small><strong><?= $value ?></strong></div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Submissions -->
        <?php if (!empty($submissions)): ?>
        <?php foreach ($submissions as $sub): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><?= htmlspecialchars($sub['form_name'] ?? 'Form') ?></h6>
            <small class="text-muted">Submitted by <?= htmlspecialchars($sub['submitted_by_name'] ?? '') ?> on <?= formatDate($sub['created_at']) ?></small>
        </div>
        <?php endforeach; ?>
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
        <div class="table-container">
            <h6 class="fw-bold mb-3">Underwriting Decision</h6>
            <form id="uwForm">
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
async function processCase(action) {
    if (action === 'reject' && !confirm('Reject this case?')) return;
    const form = document.getElementById('uwForm');
    const fd = new FormData(form); fd.append('action', action);
    const r = await fetch('/bestdealcrm/underwriting/cases/process', { method: 'POST', body: fd, headers: {'X-Requested-With': 'XMLHttpRequest'} });
    const result = await r.json();
    if (result.success) { alert(result.message); window.location.href = '/bestdealcrm/underwriting/cases'; }
    else alert(result.error || 'Error occurred.');
}
</script>
