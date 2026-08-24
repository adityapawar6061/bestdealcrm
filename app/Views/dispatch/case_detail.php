<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-truck me-2"></i>Dispatch: Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/dispatch/cases" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3">Lead Information</h6>
            <div class="row g-2">
                <?php $fields = ['Customer' => $lead['customer_name'] ?? '-', 'Mobile' => $lead['mobile_number'] ?? '-', 'Location' => $lead['location'] ?? '-', 'Bank' => $lead['bank_name'] ?? '-', 'Stage' => statusBadge($lead['workflow_stage'])]; ?>
                <?php foreach ($fields as $label => $value): ?>
                <div class="col-md-4"><small class="text-muted d-block"><?= $label ?></small><strong><?= $value ?></strong></div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Documents -->
        <?php if (!empty($documents)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark me-1"></i> Documents</h6>
            <?php foreach ($documents as $doc): ?>
            <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
                <i class="bi bi-file-earmark text-primary"></i>
                <div class="flex-grow-1">
                    <small class="d-block"><?= htmlspecialchars($doc['original_name'] ?? $doc['filename']) ?></small>
                    <small class="text-muted"><?= formatDate($doc['created_at'], 'd M Y') ?></small>
                </div>
                <a href="/bestdealcrm/admin/documents/<?= $doc['id'] ?>/download" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
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

        <div class="table-container">
            <h6 class="fw-bold mb-3">Dispatch Action</h6>
            <form id="dispatchForm">
                <?= csrfField() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Dispatch Remark</label>
                    <textarea name="dispatch_remark" class="form-control form-control-sm" rows="3" placeholder="Enter remarks..."></textarea>
                </div>
                <button type="button" class="btn btn-success w-100" onclick="processDispatch()"><i class="bi bi-check-circle me-1"></i> Mark as Completed</button>
            </form>
        </div>
    </div>
</div>

<script>
async function processDispatch() {
    if (!confirm('Mark this lead as completed?')) return;
    const fd = new FormData(document.getElementById('dispatchForm'));
    fd.append('action', 'complete');
    const r = await fetch('/bestdealcrm/dispatch/cases/process', { method: 'POST', body: fd, headers: {'X-Requested-With': 'XMLHttpRequest'} });
    const result = await r.json();
    if (result.success) { alert(result.message); window.location.href = '/bestdealcrm/dispatch/cases'; }
    else alert(result.error || 'Error occurred.');
}
</script>
