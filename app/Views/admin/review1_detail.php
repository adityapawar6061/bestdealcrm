<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard-check me-2"></i>Review Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted">Stage 1 Review</small>
    </div>
    <a href="/bestdealcrm/admin/review1" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-4">
    <!-- Lead Info & Submissions -->
    <div class="col-lg-8">
        <!-- Lead Info -->
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3">Lead Information</h6>
            <div class="row g-2">
                <?php
                $info = [
                    'Customer' => $lead['customer_name'] ?? '-',
                    'Mobile' => $lead['mobile_number'] ?? '-',
                    'Location' => $lead['location'] ?? '-',
                    'Bank' => $lead['bank_name'] ?? '-',
                    'Salary' => $lead['salary'] ? '₹' . number_format($lead['salary']) : '-',
                ];
                foreach ($info as $label => $value):
                ?>
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= $label ?></small>
                    <strong><?= htmlspecialchars($value) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Submissions -->
        <?php if (!empty($submissions)): ?>
        <?php foreach ($submissions as $sub): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><?= htmlspecialchars($sub['form_name'] ?? 'Form Submission') ?></h6>
            <p><small class="text-muted">Submitted by <?= htmlspecialchars($sub['submitted_by_name'] ?? '') ?> on <?= formatDate($sub['created_at']) ?></small></p>
            <?php
            $values = $this->db->fetchAll(
                "SELECT fsv.*, ff.label, ff.type FROM form_submission_values fsv JOIN form_fields ff ON fsv.field_id = ff.id WHERE fsv.submission_id = ?",
                [$sub['id']]
            );
            ?>
            <div class="row g-2">
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

    <!-- Review Actions -->
    <div class="col-lg-4">
        <!-- Timeline -->
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
        const response = await fetch('/bestdealcrm/admin/review1/process', {
            method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            window.location.href = '/bestdealcrm/admin/review1';
        } else {
            alert(result.error || 'Error occurred.');
        }
    } catch (err) {
        alert('Network error.');
    }
}
</script>
