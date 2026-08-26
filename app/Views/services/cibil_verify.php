<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Verify CIBIL Request - <?= htmlspecialchars($row['name_as_pan']) ?></h5>
                <a href="<?= BASE_URL ?>/admin/cibil-requests" class="btn btn-sm btn-light"><i class="bi bi-arrow-left me-1"></i>Back</a>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-danger fw-bold">Customer Details</h6>
                        <table class="table table-sm">
                            <tr><td class="text-muted" style="width:180px">Customer</td><td><strong><?= htmlspecialchars($row['name_as_pan']) ?></strong></td></tr>
                            <tr><td class="text-muted">PAN</td><td><code><?= htmlspecialchars($row['pan_no']) ?></code></td></tr>
                            <tr><td class="text-muted">Mobile</td><td><?= htmlspecialchars($row['mobile']) ?></td></tr>
                            <tr><td class="text-muted">CIBIL Score</td><td><span class="badge bg-<?= ($row['cibil_score'] ?? 0) >= 750 ? 'success' : (($row['cibil_score'] ?? 0) >= 650 ? 'warning' : 'danger') ?> fs-6"><?= $row['cibil_score'] ?? 'N/A' ?></span></td></tr>
                            <tr><td class="text-muted">Monthly Salary</td><td>₹<?= number_format((int)str_replace(['₹',','], '', $row['monthly_salary'])) ?></td></tr>
                            <tr><td class="text-muted">Loan Requirement</td><td>₹<?= number_format((int)str_replace(['₹',','], '', $row['loan_requirement'])) ?></td></tr>
                            <tr><td class="text-muted">Loan Type</td><td><?= htmlspecialchars($row['loan_type']) ?></td></tr>
                            <tr><td class="text-muted">Requirement</td><td><?= htmlspecialchars($row['requirement'] ?? 'N/A') ?></td></tr>
                            <tr><td class="text-muted">Calculator ID</td><td><?= htmlspecialchars($row['calculator_id'] ?? 'N/A') ?></td></tr>
                            <tr><td class="text-muted">Loan Eligible</td><td><?= htmlspecialchars($row['loan_eligible_calc'] ?? 'N/A') ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-info fw-bold">Request Info</h6>
                        <table class="table table-sm">
                            <tr><td class="text-muted" style="width:120px">Agent</td><td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['agent_name'] ?? 'Admin') ?></span></td></tr>
                            <tr><td class="text-muted">Submitted</td><td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td></tr>
                            <tr><td class="text-muted">Status</td><td><span class="badge bg-<?= $row['status'] === 'replied' ? 'success' : 'warning' ?>"><?= ucfirst($row['status']) ?></span></td></tr>
                            <?php if ($row['cibil_checked'] === 'yes'): ?>
                            <tr><td class="text-muted">CIBIL Checked</td><td><span class="badge bg-success">Yes</span></td></tr>
                            <tr><td class="text-muted">CIBIL Company</td><td><?= htmlspecialchars($row['cibil_company'] ?? 'N/A') ?></td></tr>
                            <tr><td class="text-muted">Actual CIBIL</td><td><strong class="text-primary fs-5"><?= $row['cibil_score_actual'] ?? 'N/A' ?></strong></td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <hr>

                <form id="cibilProcessForm">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">

                    <h6 class="text-danger fw-bold mb-3"><i class="bi bi-pencil-square me-2"></i>Admin Verification</h6>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">CIBIL Checked *</label>
                            <select class="form-select" name="cibil_checked" required>
                                <option value="yes" <?= $row['cibil_checked'] === 'yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="no" <?= $row['cibil_checked'] === 'no' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">CIBIL Company</label>
                            <select class="form-select" name="cibil_company">
                                <option value="">Select Company</option>
                                <option value="CRIF" <?= $row['cibil_company'] === 'CRIF' ? 'selected' : '' ?>>CRIF</option>
                                <option value="Experian" <?= $row['cibil_company'] === 'Experian' ? 'selected' : '' ?>>Experian</option>
                                <option value="Equifax" <?= $row['cibil_company'] === 'Equifax' ? 'selected' : '' ?>>Equifax</option>
                                <option value="CIBIL" <?= $row['cibil_company'] === 'CIBIL' ? 'selected' : '' ?>>CIBIL</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">CIBIL Score Actual by Admin</label>
                            <input type="number" class="form-control" name="cibil_score_actual" value="<?= $row['cibil_score_actual'] ?? '' ?>" placeholder="300-900" min="300" max="900">
                            <small class="text-muted">Enter the actual CIBIL score found in the report</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Main Status</label>
                            <select class="form-select" name="main_status">
                                <option value="N/A" <?= $row['main_status'] === 'N/A' ? 'selected' : '' ?>>N/A</option>
                                <option value="Not Eligible" <?= $row['main_status'] === 'Not Eligible' ? 'selected' : '' ?>>Not Eligible</option>
                                <option value="Follow Up" <?= $row['main_status'] === 'Follow Up' ? 'selected' : '' ?>>Follow Up</option>
                                <option value="Login Pending" <?= $row['main_status'] === 'Login Pending' ? 'selected' : '' ?>>Login Pending</option>
                                <option value="Underwriting" <?= $row['main_status'] === 'Underwriting' ? 'selected' : '' ?>>Underwriting</option>
                                <option value="Approved" <?= $row['main_status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="Rejected" <?= $row['main_status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="Disbursed" <?= $row['main_status'] === 'Disbursed' ? 'selected' : '' ?>>Disbursed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sub Status</label>
                            <input type="text" class="form-control" name="sub_status" value="<?= htmlspecialchars($row['sub_status'] ?? 'N/A') ?>" placeholder="e.g. Over Obligated">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Agent CIBIL Remarks</label>
                            <input type="text" class="form-control" name="agent_cibil_remarks" value="<?= htmlspecialchars($row['agent_cibil_remarks'] ?? '') ?>" placeholder="Remarks">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Admin Remarks</label>
                            <textarea class="form-control" name="admin_remarks" rows="2" placeholder="Enter admin remarks..."><?= htmlspecialchars($row['admin_remarks'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CIBIL PDF (First Attachment)</label>
                            <?php if ($row['cibil_pdf1']): ?>
                                <div class="mb-1"><a href="<?= BASE_URL ?>/public/uploads/cibil/<?= htmlspecialchars($row['cibil_pdf1']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-pdf me-1"></i>View Current PDF</a></div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="cibil_pdf1" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Upload a new file only if you want to replace the current one.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CIBIL PDF 2 (Second Attachment)</label>
                            <?php if ($row['cibil_pdf2']): ?>
                                <div class="mb-1"><a href="<?= BASE_URL ?>/public/uploads/cibil/<?= htmlspecialchars($row['cibil_pdf2']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-pdf me-1"></i>View Current PDF 2</a></div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="cibil_pdf2" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="text-muted">Upload a second PDF file (optional).</small>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-danger btn-lg" id="processBtn">
                            <i class="bi bi-check-lg me-2"></i>Save & Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <?php if ($row['cibil_pdf1'] || $row['cibil_pdf2']): ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-paperclip me-2"></i>Uploaded Files</h6>
            </div>
            <div class="card-body">
                <?php if ($row['cibil_pdf1']): ?>
                    <div class="d-flex align-items-center mb-2 p-2 rounded" style="background:#f8f9fa">
                        <i class="bi bi-file-pdf me-2 text-danger"></i>
                        <a href="<?= BASE_URL ?>/public/uploads/cibil/<?= htmlspecialchars($row['cibil_pdf1']) ?>" target="_blank" class="text-decoration-none">CIBIL Report 1</a>
                    </div>
                <?php endif; ?>
                <?php if ($row['cibil_pdf2']): ?>
                    <div class="d-flex align-items-center mb-2 p-2 rounded" style="background:#f8f9fa">
                        <i class="bi bi-file-pdf me-2 text-danger"></i>
                        <a href="<?= BASE_URL ?>/public/uploads/cibil/<?= htmlspecialchars($row['cibil_pdf2']) ?>" target="_blank" class="text-decoration-none">CIBIL Report 2</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('cibilProcessForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('processBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    try {
        const formData = new FormData(this);
        formData.append('_csrf_token', CSRF_TOKEN);
        const resp = await fetch(BASE_URL + '/admin/cibil-process', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await resp.json();
        if (result && result.success) {
            showToast(result.message || 'Updated!', 'success');
            setTimeout(() => window.location.href = BASE_URL + '/admin/cibil-requests', 1500);
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
