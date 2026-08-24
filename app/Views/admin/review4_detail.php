<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard2-check me-2"></i>Review 4 - Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/admin/review4" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3">Lead Information</h6>
            <div class="row g-3">
                <?php foreach (['Customer Name' => 'customer_name', 'Mobile' => 'mobile_number', 'Location' => 'location', 'State' => 'state', 'Bank' => 'bank_name', 'Existing LA' => 'existing_la', 'Salary' => 'salary', 'Remark' => 'remark', 'Stage' => 'workflow_stage'] as $label => $key): ?>
                <div class="col-md-4">
                    <small class="text-muted"><?= $label ?></small><br>
                    <strong><?= htmlspecialchars($lead[$key] ?? '-') ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3">Previous Remarks</h6>
            <?php
            $remarks = [
                'admin_approval1_remark' => 'Admin Review 1',
                'admin_approval2_remark' => 'Admin Review 2',
                'admin_approval3_remark' => 'Admin Review 3',
            ];
            foreach ($remarks as $field => $label):
                if (!empty($lead[$field])):
            ?>
                <div class="mb-2 p-2 bg-light rounded">
                    <small class="fw-semibold text-primary"><?= $label ?>:</small>
                    <small><?= htmlspecialchars($lead[$field]) ?></small>
                </div>
            <?php endif; endforeach; ?>
        </div>

        <div class="table-container">
            <h6 class="fw-bold mb-3">Admin Decision - Send to Dispatch?</h6>
            <form id="review4Form">
                <?= csrfField() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Admin Remark</label>
                    <textarea name="admin_approval4_remark" class="form-control" rows="3" placeholder="Add your remark..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success" onclick="processReview4('approve_to_dispatch')">
                        <i class="bi bi-check-lg me-1"></i> Approve → Send to Dispatch
                    </button>
                    <button type="button" class="btn btn-danger" onclick="processReview4('reject')">
                        <i class="bi bi-x-lg me-1"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="table-container">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Timeline</h6>
            <?php if (!empty($timeline)): ?>
                <?php foreach ($timeline as $event): ?>
                <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex justify-content-between">
                        <small class="fw-semibold"><?= htmlspecialchars($event['action']) ?></small>
                        <small class="text-muted"><?= formatDate($event['created_at']) ?></small>
                    </div>
                    <small class="text-muted"><?= htmlspecialchars($event['performed_by_name'] ?? 'System') ?></small>
                    <?php if (!empty($event['remark'])): ?>
                        <div class="mt-1"><small class="text-secondary"><?= htmlspecialchars($event['remark']) ?></small></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted small">No timeline events yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
async function processReview4(action) {
    var form = document.getElementById('review4Form');
    var formData = new FormData(form);
    formData.append('action', action);

    var msg = action === 'approve_to_dispatch' ? 'Approve and send to Dispatch?' : 'Reject this lead?';
    if (!confirm(msg)) return;

    var result = await ajaxPost('/bestdealcrm/admin/review4/process', formData);
    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(function() { window.location.href = '/bestdealcrm/admin/review4'; }, 1000);
    } else {
        showToast(result.error || 'Error processing review.', 'danger');
    }
}
</script>
