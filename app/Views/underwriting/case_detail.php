<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard-data me-2"></i>Underwriting: Lead #<?= $lead['id'] ?></h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?> | <?= statusBadge($lead['workflow_stage']) ?></small>
    </div>
    <a href="/bestdealcrm/underwriting/cases" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<!-- Lead Info Bar -->
<div class="table-container mb-4 p-3">
    <div class="row g-3">
        <div class="col-md-2"><small class="text-muted d-block">Customer</small><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Mobile</small><strong><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Location</small><strong><?= htmlspecialchars($lead['location'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Bank</small><strong><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Salary</small><strong><?= htmlspecialchars($lead['salary'] ?? '-') ?></strong></div>
        <div class="col-md-2"><small class="text-muted d-block">Stage</small><?= statusBadge($lead['workflow_stage']) ?></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">

        <!-- ============================================================ -->
        <!-- SECTION 1: Agent Form (Read Only) -->
        <!-- ============================================================ -->
        <div class="table-container mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-secondary mb-0">
                    <i class="bi bi-person-check me-1"></i> 1. Agent Lead Form (Submitted by Agent)
                </h6>
                <span class="badge bg-secondary">Read Only</span>
            </div>
            <?php if (empty($agentValues)): ?>
                <div class="alert alert-light mb-0"><i class="bi bi-info-circle me-1"></i> No agent form submission found for this lead.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($agentValues as $v): ?>
                        <?php if (($v['field_type'] ?? 'field') === 'heading'): ?>
                            <div class="col-12"><h5 class="text-primary mt-2 mb-1"><?= htmlspecialchars($v['label'] ?? '') ?></h5></div>
                        <?php elseif (($v['field_type'] ?? 'field') === 'subheading'): ?>
                            <div class="col-12"><h6 class="text-dark mt-1 mb-1"><?= htmlspecialchars($v['label'] ?? '') ?></h6></div>
                        <?php else: ?>
                            <?php if (empty($v['label'])) continue; ?>
                            <?php $val = $v['value'] ?? '-'; ?>
                            <?php if (empty($val) || $val === '0000-00-00 00:00:00' || $val === '0000-00-00') $val = '-'; ?>
                            <div class="col-md-4">
                                <small class="text-muted d-block"><?= htmlspecialchars($v['label']) ?></small>
                                <?php if ($v['type'] === 'file' && !empty($v['value']) && $v['value'] !== '-'): ?>
                                    <a href="/bestdealcrm/public/uploads/documents/<?= $lead['id'] ?>/<?= htmlspecialchars($v['value']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="bi bi-file-earmark me-1"></i> View File
                                    </a>
                                <?php elseif ($v['type'] === 'image' && !empty($v['value']) && $v['value'] !== '-'): ?>
                                    <a href="/bestdealcrm/public/uploads/documents/<?= $lead['id'] ?>/<?= htmlspecialchars($v['value']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="bi bi-image me-1"></i> View Image
                                    </a>
                                <?php else: ?>
                                    <strong class="small"><?= htmlspecialchars($val) ?></strong>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================ -->
        <!-- SECTION 2: Pre-Login Checklist (Read Only) -->
        <!-- ============================================================ -->
        <div class="table-container mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-info mb-0">
                    <i class="bi bi-clipboard-check me-1"></i> 2. Pre-Login Checklist
                </h6>
                <span class="badge bg-info">Read Only</span>
            </div>
            <?php if (empty($preLoginValues)): ?>
                <div class="alert alert-light mb-0"><i class="bi bi-info-circle me-1"></i> No pre-login checklist submission found.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($preLoginValues as $v): ?>
                        <?php if (($v['field_type'] ?? 'field') === 'heading'): ?>
                            <div class="col-12"><h5 class="text-primary mt-2 mb-1"><?= htmlspecialchars($v['label'] ?? '') ?></h5></div>
                        <?php elseif (($v['field_type'] ?? 'field') === 'subheading'): ?>
                            <div class="col-12"><h6 class="text-dark mt-1 mb-1"><?= htmlspecialchars($v['label'] ?? '') ?></h6></div>
                        <?php else: ?>
                            <?php if (empty($v['label'])) continue; ?>
                            <?php $val = $v['value'] ?? '-'; ?>
                            <?php if (empty($val) || $val === '0000-00-00 00:00:00' || $val === '0000-00-00') $val = '-'; ?>
                            <div class="col-md-4">
                                <small class="text-muted d-block"><?= htmlspecialchars($v['label']) ?></small>
                                <?php if ($v['type'] === 'file' && !empty($v['value']) && $v['value'] !== '-'): ?>
                                    <a href="/bestdealcrm/public/uploads/documents/<?= $lead['id'] ?>/<?= htmlspecialchars($v['value']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="bi bi-file-earmark me-1"></i> View File
                                    </a>
                                <?php else: ?>
                                    <strong class="small"><?= htmlspecialchars($val) ?></strong>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================ -->
        <!-- SECTION 3: Post-Login Form (Read Only) -->
        <!-- ============================================================ -->
        <div class="table-container mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-success mb-0">
                    <i class="bi bi-clipboard-data me-1"></i> 3. Post-Login Form
                </h6>
                <span class="badge bg-success">Read Only</span>
            </div>
            <?php if (empty($postLoginValues)): ?>
                <div class="alert alert-light mb-0"><i class="bi bi-info-circle me-1"></i> No post-login form submission found.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($postLoginValues as $v): ?>
                        <?php if (($v['field_type'] ?? 'field') === 'heading'): ?>
                            <div class="col-12"><h5 class="text-primary mt-2 mb-1"><?= htmlspecialchars($v['label'] ?? '') ?></h5></div>
                        <?php elseif (($v['field_type'] ?? 'field') === 'subheading'): ?>
                            <div class="col-12"><h6 class="text-dark mt-1 mb-1"><?= htmlspecialchars($v['label'] ?? '') ?></h6></div>
                        <?php else: ?>
                            <?php if (empty($v['label'])) continue; ?>
                            <?php $val = $v['value'] ?? '-'; ?>
                            <?php if (empty($val) || $val === '0000-00-00 00:00:00' || $val === '0000-00-00') $val = '-'; ?>
                            <div class="col-md-4">
                                <small class="text-muted d-block"><?= htmlspecialchars($v['label']) ?></small>
                                <?php if ($v['type'] === 'file' && !empty($v['value']) && $v['value'] !== '-'): ?>
                                    <a href="/bestdealcrm/public/uploads/documents/<?= $lead['id'] ?>/<?= htmlspecialchars($v['value']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="bi bi-file-earmark me-1"></i> View File
                                    </a>
                                <?php else: ?>
                                    <strong class="small"><?= htmlspecialchars($val) ?></strong>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============================================================ -->
        <!-- SECTION 4: Documents -->
        <!-- ============================================================ -->
        <?php if (!empty($documents)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark me-1"></i> All Documents</h6>
            <div class="row g-2">
                <?php foreach ($documents as $doc): ?>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                        <i class="bi bi-file-earmark text-primary"></i>
                        <div class="flex-grow-1">
                            <small class="d-block"><?= htmlspecialchars($doc['original_name'] ?? $doc['filename']) ?></small>
                            <small class="text-muted"><?= htmlspecialchars($doc['purpose'] ?? 'Document') ?> • <?= formatDate($doc['created_at'], 'd M Y (IST)') ?></small>
                        </div>
                        <a href="/bestdealcrm/public/uploads/documents/<?= $lead['id'] ?>/<?= htmlspecialchars($doc['filename']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ============================================================ -->
        <!-- SECTION 5: All Remarks -->
        <!-- ============================================================ -->
        <?php if (!empty($remarks)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-chat-left-text me-1"></i> All Remarks</h6>
            <?php foreach ($remarks as $r): ?>
            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                        <strong class="small text-primary"><?= htmlspecialchars($r['user_name'] ?? 'System') ?></strong>
                        <small class="text-muted"><?= formatDate($r['created_at'], 'd M, h:i A (IST)') ?></small>
                    </div>
                    <small class="text-muted d-block"><?= htmlspecialchars($r['stage'] ?? '') ?></small>
                    <div class="small mt-1"><?= nl2br(htmlspecialchars($r['remark'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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
                    <div class="text-muted" style="font-size:0.75rem"><?= formatDate($event['created_at'], 'd M, h:i A (IST)') ?></div>
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
    var remark = document.querySelector('#uwForm textarea[name="underwriting_remark"]').value;
    if (!remark && action === 'reject') { alert('Please enter a remark before rejecting.'); return; }
    var form = document.getElementById('uwForm');
    var fd = new FormData(form);
    fd.append('action', action);
    try {
        var r = await fetch('/bestdealcrm/underwriting/cases/process', {
            method: 'POST', body: fd,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        var result = await r.json();
        if (result.success) {
            showToast(result.message, 'success');
            setTimeout(function() { window.location.href = '/bestdealcrm/underwriting/cases'; }, 1000);
        } else {
            showToast(result.error || 'Error occurred.', 'danger');
        }
    } catch(e) {
        showToast('Network error: ' + e.message, 'danger');
    }
}
</script>
