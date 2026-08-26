<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Raise PF Request</h5>
            </div>
            <div class="card-body">
                <form id="pfForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="customer_name" required placeholder="Enter customer name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mobile No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="mobile" required placeholder="Enter mobile number" maxlength="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monthly Salary <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="monthly_salary" required placeholder="e.g. ₹77,000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Loan Requirement <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="loan_requirement" required placeholder="e.g. ₹22,00,000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Loan Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="loan_type" required>
                                <option value="">Select Loan Type</option>
                                <option value="Personal Loan">Personal Loan</option>
                                <option value="BT + Top Up">BT + Top Up</option>
                                <option value="Top Up">Top Up</option>
                                <option value="Fresh">Fresh</option>
                                <option value="Home Loan">Home Loan</option>
                                <option value="Business Loan">Business Loan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Processing Bank <span class="text-danger">*</span></label>
                            <select class="form-select" name="processing_bank" required>
                                <option value="">Select Bank</option>
                                <option value="HDFC">HDFC</option>
                                <option value="ICICI">ICICI</option>
                                <option value="Axis">Axis</option>
                                <option value="Kotak">Kotak</option>
                                <option value="Bajaj">Bajaj</option>
                                <option value="IDFC">IDFC</option>
                                <option value="IndusInd">IndusInd</option>
                                <option value="Poonawalla">Poonawalla</option>
                                <option value="Incred">Incred</option>
                                <option value="TATA Capital">TATA Capital</option>
                                <option value="Finnable">Finnable</option>
                                <option value="Piramal">Piramal</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CIBIL Score <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="cibil_score" required placeholder="e.g. 798" min="300" max="900">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="bi bi-send me-2"></i>Submit PF Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent PF Requests</h6>
            </div>
            <div class="card-body p-0" style="max-height:500px;overflow-y:auto">
                <?php if (empty($recent)): ?>
                    <p class="text-muted p-3">No requests yet.</p>
                <?php else: ?>
                    <?php foreach ($recent as $r): ?>
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= htmlspecialchars($r['customer_name']) ?></strong>
                                <span class="badge bg-<?= $r['status'] === 'replied' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </div>
                            <small class="text-muted"><?= htmlspecialchars($r['mobile']) ?> · <?= htmlspecialchars($r['processing_bank']) ?></small><br>
                            <small class="text-muted"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></small>
                            <?php if ($r['status'] === 'replied'): ?>
                                <div class="mt-2 p-2 rounded" style="background:#f0fdf4">
                                    <small><strong>Approved:</strong> <?= ucfirst($r['admin_approved']) ?></small><br>
                                    <?php if ($r['admin_remarks']): ?>
                                        <small><strong>Remarks:</strong> <?= htmlspecialchars($r['admin_remarks']) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('pfForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';

    try {
        const formData = new FormData(this);
        formData.append('_csrf_token', CSRF_TOKEN);
        const result = await ajaxPost(BASE_URL + '/services/pf/submit', formData);
        if (result && result.success) {
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.error || 'Failed', 'danger');
        }
    } catch(err) {
        showToast('Error: ' + err.message, 'danger');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-2"></i>Submit PF Request';
});
</script>
