<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-check-circle me-2"></i>Verify PF Request - <?= htmlspecialchars($row['customer_name']) ?></h5>
                <a href="<?= BASE_URL ?>/admin/pf-requests" class="btn btn-sm btn-light"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
            <div class="card-body">
                <!-- Customer Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary fw-bold">Customer Details</h6>
                        <table class="table table-sm">
                            <tr><td class="text-muted" style="width:150px">Customer</td><td><strong><?= htmlspecialchars($row['customer_name']) ?></strong></td></tr>
                            <tr><td class="text-muted">Mobile</td><td><?= htmlspecialchars($row['mobile']) ?></td></tr>
                            <tr><td class="text-muted">Monthly Salary</td><td>₹<?= number_format((int)str_replace(['₹',','], '', $row['monthly_salary'])) ?></td></tr>
                            <tr><td class="text-muted">Loan Requirement</td><td>₹<?= number_format((int)str_replace(['₹',','], '', $row['loan_requirement'])) ?></td></tr>
                            <tr><td class="text-muted">Loan Type</td><td><?= htmlspecialchars($row['loan_type']) ?></td></tr>
                            <tr><td class="text-muted">Processing Bank</td><td><?= htmlspecialchars($row['processing_bank']) ?></td></tr>
                            <tr><td class="text-muted">CIBIL Score</td><td><span class="badge bg-<?= $row['cibil_score'] >= 750 ? 'success' : ($row['cibil_score'] >= 650 ? 'warning' : 'danger') ?> fs-6"><?= $row['cibil_score'] ?></span></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info fw-bold">Request Info</h6>
                        <table class="table table-sm">
                            <tr><td class="text-muted" style="width:120px">Agent</td><td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['agent_name'] ?? 'N/A') ?></span></td></tr>
                            <tr><td class="text-muted">Submitted</td><td><?= formatDate($row['created_at'], 'd M Y, h:i A (IST)') ?></td></tr>
                            <tr><td class="text-muted">Status</td><td><span class="badge bg-<?= $row['status'] === 'replied' ? 'success' : 'warning' ?>"><?= ucfirst($row['status']) ?></span></td></tr>
                            <?php if ($row['admin_approved'] !== 'pending'): ?>
                            <tr><td class="text-muted">Decision</td><td><span class="badge bg-<?= $row['admin_approved'] === 'yes' ? 'success' : 'danger' ?>"><?= ucfirst($row['admin_approved']) ?></span></td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <hr>

                <!-- Admin Action Form -->
                <form id="pfProcessForm">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-pencil-square me-2"></i>Admin Verification</h6>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Approved *</label>
                            <select class="form-select" name="admin_approved" required>
                                <option value="yes" <?= $row['admin_approved'] === 'yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="no" <?= $row['admin_approved'] === 'no' ? 'selected' : '' ?>>No</option>
                                <option value="pending" <?= $row['admin_approved'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Admin PF Remarks</label>
                            <textarea class="form-control" name="admin_remarks" rows="3" placeholder="Enter remarks..."><?= htmlspecialchars($row['admin_remarks'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Upload Files</label>
                            <input type="file" class="form-control" name="admin_files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <small class="text-muted">Upload PF documents. Hold Ctrl/Cmd to select multiple files.</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-success btn-lg" id="processBtn">
                            <i class="bi bi-check-lg me-2"></i>Save & Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <?php
        $files = json_decode($row['admin_files'] ?? '[]', true) ?? [];
        if (!empty($files)):
        ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>Uploaded Files</h6>
            </div>
            <div class="card-body">
                <?php foreach ($files as $f): ?>
                    <div class="d-flex align-items-center mb-2 p-2 rounded" style="background:#f8f9fa">
                        <i class="bi bi-file-earmark me-2"></i>
                        <a href="<?= BASE_URL ?>/public/uploads/pf/<?= htmlspecialchars($f) ?>" target="_blank" class="text-decoration-none">
                            <?= htmlspecialchars($f) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('pfProcessForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('processBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    try {
        const formData = new FormData(this);
        formData.append('_csrf_token', CSRF_TOKEN);
        const resp = await fetch(BASE_URL + '/admin/pf-process', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await resp.json();
        if (result && result.success) {
            showToast(result.message || 'Updated!', 'success');
            setTimeout(() => window.location.href = BASE_URL + '/admin/pf-requests', 1500);
        } else {
            showToast(result.error || 'Failed', 'danger');
        }
    } catch(err) {
        showToast('Error: ' + err.message, 'danger');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-lg me-2"></i>Save & Reply';
});
</script>
