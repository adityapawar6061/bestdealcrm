<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard2-check me-2"></i>Review 3 - Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?> | Stage: <span class="badge bg-warning text-dark"><?= humanStatus($lead['workflow_stage']) ?></span></small>
    </div>
    <a href="/bestdealcrm/admin/review3" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Review 3</a>
</div>

<div class="row g-4">
    <div class="col-md-8">

        <!-- Lead Info -->
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3">Lead Information</h6>
            <div class="row g-3">
                <?php foreach ([
                    'Customer Name' => 'customer_name', 'Mobile' => 'mobile_number',
                    'Location' => 'location', 'State' => 'state',
                    'Bank' => 'bank_name', 'Existing LA' => 'existing_la',
                    'Salary' => 'salary', 'Remark' => 'remark',
                ] as $label => $key): ?>
                <div class="col-md-4">
                    <small class="text-muted"><?= $label ?></small><br>
                    <strong><?= htmlspecialchars($lead[$key] ?? '-') ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Form Submissions -->
        <?php if (!empty($submissions)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-1"></i> Form Submissions</h6>
            <?php foreach ($submissions as $sub): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <strong class="small"><?= htmlspecialchars($sub['form_name'] ?? 'Form') ?></strong>
                    <div>
                        <span class="badge bg-secondary"><?= htmlspecialchars($sub['status'] ?? '') ?></span>
                        <small class="text-muted ms-2"><?= formatDate($sub['submitted_at'] ?? $sub['created_at'] ?? '') ?></small>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($sub['sections']) && is_array($sub['sections'])): ?>
                        <?php foreach ($sub['sections'] as $section): ?>
                            <h6 class="text-primary mt-3 mb-2 small fw-bold"><?= htmlspecialchars($section['name'] ?? '') ?></h6>
                            <div class="row g-2 mb-2">
                            <?php if (!empty($section['fields']) && is_array($section['fields'])): ?>
                                <?php foreach ($section['fields'] as $field): ?>
                                    <?php if (($field['type'] ?? '') === 'file' || ($field['type'] ?? '') === 'image'): ?>
                                        <?php if (!empty($field['value'])): ?>
                                        <div class="col-md-6">
                                            <small class="text-muted"><?= htmlspecialchars($field['label'] ?? $field['field_name'] ?? '') ?></small><br>
                                            <?php
                                            $fileUrl = $field['value'];
                                            if (strpos($fileUrl, 'http') === false) {
                                                $fileUrl = '/bestdealcrm/public/uploads/documents/' . $lead['id'] . '/' . $field['value'];
                                            }
                                            $ext = strtolower(pathinfo($field['value'], PATHINFO_EXTENSION));
                                            ?>
                                            <?php if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                                <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank"><img src="<?= htmlspecialchars($fileUrl) ?>" class="img-thumbnail" style="max-height:80px"></a>
                                            <?php else: ?>
                                                <a href="<?= htmlspecialchars($fileUrl) ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-file-earmark"></i> <?= htmlspecialchars(pathinfo($field['value'], PATHINFO_FILENAME)) ?></a>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <div class="col-md-6">
                                        <small class="text-muted"><?= htmlspecialchars($field['label'] ?? $field['field_name'] ?? '') ?></small><br>
                                        <strong class="small"><?= htmlspecialchars($field['value'] ?? '-') ?></strong>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif (!empty($sub['values'])): ?>
                        <?php
                        // Fallback: flat key-value display
                        $values = is_string($sub['values']) ? json_decode($sub['values'], true) : $sub['values'];
                        if (is_array($values)):
                        ?>
                        <div class="row g-2">
                        <?php foreach ($values as $k => $v): ?>
                            <div class="col-md-6">
                                <small class="text-muted"><?= htmlspecialchars($k) ?></small><br>
                                <strong class="small"><?= htmlspecialchars($v ?? '-') ?></strong>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No submission values found.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Documents -->
        <?php if (!empty($documents)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-paperclip me-1"></i> Uploaded Documents</h6>
            <div class="row g-2">
            <?php foreach ($documents as $doc): ?>
                <div class="col-md-6">
                    <div class="d-flex align-items-center p-2 bg-light rounded">
                        <i class="bi bi-file-earmark me-2"></i>
                        <div class="flex-grow-1">
                            <small class="fw-semibold"><?= htmlspecialchars($doc['original_name'] ?? $doc['document_type'] ?? 'Document') ?></small><br>
                            <small class="text-muted"><?= $doc['document_type'] ?? '' ?> · <?= number_format(($doc['file_size'] ?? 0) / 1024) ?>KB</small>
                        </div>
                        <a href="/bestdealcrm/admin/documents/<?= $doc['id'] ?>/download" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Previous Remarks -->
        <?php if (!empty($remarks)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-1"></i> All Remarks</h6>
            <?php foreach ($remarks as $rm): ?>
            <div class="mb-2 p-2 bg-light rounded">
                <div class="d-flex justify-content-between">
                    <small class="fw-semibold text-primary"><?= htmlspecialchars($rm['user_name'] ?? 'Admin') ?> — <?= htmlspecialchars($rm['stage'] ?? '') ?></small>
                    <small class="text-muted"><?= formatDate($rm['created_at'] ?? '') ?></small>
                </div>
                <small><?= nl2br(htmlspecialchars($rm['remark'] ?? '')) ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Admin Review 3 Form -->
        <div class="table-container">
            <h6 class="fw-bold mb-3"><i class="bi bi-gear me-1"></i> Admin Decision - Assign to Underwriting</h6>
            <form id="review3Form">
                <?= csrfField() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Assign to Underwriting Agent *</label>
                    <select name="assigned_to" class="form-select" id="underwritingAgent" required>
                        <option value="">-- Select Underwriting Agent --</option>
                        <?php foreach ($underwritingAgents as $agent): ?>
                            <option value="<?= $agent['id'] ?>" <?= ($lead['assigned_to'] == $agent['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($agent['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($underwritingAgents)): ?>
                        <small class="text-danger">No underwriting agents found. Please create a user with Underwriting role first.</small>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Admin Remark</label>
                    <textarea name="admin_approval3_remark" class="form-control" rows="3" placeholder="Add your remark..."><?= htmlspecialchars($lead['admin_approval3_remark'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success" onclick="processReview3('approve_to_underwriting')" <?= empty($underwritingAgents) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-lg me-1"></i> Approve → Send to Underwriting
                    </button>
                    <button type="button" class="btn btn-danger" onclick="processReview3('reject')">
                        <i class="bi bi-x-lg me-1"></i> Reject
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Timeline -->
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
async function processReview3(action) {
    var form = document.getElementById('review3Form');
    var formData = new FormData(form);
    formData.append('action', action);

    var assignedAgent = document.getElementById('underwritingAgent').value;
    if (action === 'approve_to_underwriting' && !assignedAgent) {
        showToast('Please select an underwriting agent.', 'warning');
        return;
    }

    var msg = action === 'approve_to_underwriting' ? 'Approve and send to Underwriting?' : 'Reject this lead?';
    if (!confirm(msg)) return;

    var result = await ajaxPost('/bestdealcrm/admin/review3/process', formData);
    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(function() { window.location.href = '/bestdealcrm/admin/review3'; }, 1000);
    } else {
        showToast(result.error || 'Error processing review.', 'danger');
    }
}
</script>
