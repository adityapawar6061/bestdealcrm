<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard2-check me-2"></i>Review Lead #<?= $lead['id'] ?> (Stage 2)</h4>
    </div>
    <a href="/bestdealcrm/admin/review2" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if (!empty($submissions)): ?>
        <?php foreach ($submissions as $sub): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-2"><?= htmlspecialchars($sub['form_name'] ?? 'Form') ?></h6>
            <small class="text-muted">By <?= htmlspecialchars($sub['submitted_by_name'] ?? '') ?> on <?= formatDate($sub['created_at']) ?></small>
            <?php
            $values = $this->db->fetchAll(
                "SELECT fsv.*, ff.label, ff.type FROM form_submission_values fsv JOIN form_fields ff ON fsv.field_id = ff.id WHERE fsv.submission_id = ?",
                [$sub['id']]
            );
            ?>
            <div class="row g-2 mt-2">
                <?php foreach ($values as $v): ?>
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= htmlspecialchars($v['label']) ?></small>
                    <strong class="small"><?= htmlspecialchars($v['value'] ?? '-') ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
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
                    <div class="text-muted" style="font-size:0.75rem"><?= formatDate($event['created_at'], 'd M, h:i A (IST)') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="table-container">
            <h6 class="fw-bold mb-3">Your Review</h6>
            <form id="review2Form">
                <?= csrfField() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Admin Approval 2 Remark</label>
                    <textarea name="admin_approval2_remark" class="form-control form-control-sm" rows="3"></textarea>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="processReview2('approve')">
                        <i class="bi bi-check-lg me-1"></i> Approve (Login Approved)
                    </button>
                    <button type="button" class="btn btn-warning" onclick="processReview2('send_back')">
                        <i class="bi bi-arrow-return-left me-1"></i> Send Back to Agent
                    </button>
                    <button type="button" class="btn btn-danger" onclick="processReview2('reject')">
                        <i class="bi bi-x-lg me-1"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function processReview2(action) {
    if (action === 'reject' && !confirm('Reject this lead?')) return;
    const formData = new FormData(document.getElementById('review2Form'));
    formData.append('action', action);

    try {
        const response = await fetch('/bestdealcrm/admin/review2/process', {
            method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        if (result.success) { alert(result.message); window.location.href = '/bestdealcrm/admin/review2'; }
        else alert(result.error || 'Error occurred.');
    } catch (err) { alert('Network error.'); }
}
</script>
