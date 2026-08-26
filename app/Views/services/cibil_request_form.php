<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Add New CIBIL Request</h5>
            </div>
            <div class="card-body">
                <form id="cibilForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Name As per PAN Card <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name_as_pan" required placeholder="Enter name as on PAN">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">PAN No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="pan_no" required placeholder="e.g. ABCDE1234F" maxlength="10" style="text-transform:uppercase">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mobile No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="mobile" required placeholder="Enter mobile number" maxlength="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CIBIL Score</label>
                            <input type="number" class="form-control" name="cibil_score" placeholder="e.g. 730" min="300" max="900">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monthly Salary <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="monthly_salary" required placeholder="e.g. ₹150,000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Loan Requirement <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="loan_requirement" required placeholder="e.g. ₹42,00,000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Loan Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="loan_type" required>
                                <option value="">Select Loan Type</option>
                                <option value="Fresh">Fresh</option>
                                <option value="BT + Top Up">BT + Top Up</option>
                                <option value="Top Up">Top Up</option>
                                <option value="Personal Loan">Personal Loan</option>
                                <option value="Home Loan">Home Loan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Loan Eligible as Per Calculator</label>
                            <input type="text" class="form-control" name="loan_eligible_calc" placeholder="e.g. B1253915">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Calculator ID</label>
                            <input type="text" class="form-control" name="calculator_id" placeholder="Enter Calculator ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Requirement</label>
                            <select class="form-select" name="requirement">
                                <option value="">Select Requirement</option>
                                <option value="CRIF">CRIF</option>
                                <option value="Experian">Experian</option>
                                <option value="Equifax">Equifax</option>
                                <option value="CIBIL">CIBIL</option>
                                <option value="Full">Full</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-danger btn-lg" id="submitBtn">
                            <i class="bi bi-send me-2"></i>Submit CIBIL Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent CIBIL Requests</h6>
            </div>
            <div class="card-body p-0" style="max-height:500px;overflow-y:auto">
                <?php if (empty($recent)): ?>
                    <p class="text-muted p-3">No requests yet.</p>
                <?php else: ?>
                    <?php foreach ($recent as $r): ?>
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between">
                                <strong><?= htmlspecialchars($r['name_as_pan']) ?></strong>
                                <span class="badge bg-<?= $r['status'] === 'replied' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </div>
                            <small class="text-muted">PAN: <?= htmlspecialchars($r['pan_no']) ?> · <?= htmlspecialchars($r['loan_type']) ?></small><br>
                            <small class="text-muted"><?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></small>
                            <?php if ($r['status'] === 'replied' && $r['cibil_score_actual']): ?>
                                <div class="mt-2 p-2 rounded" style="background:#f0fdf4">
                                    <small><strong>Actual CIBIL:</strong> <?= $r['cibil_score_actual'] ?></small>
                                    <?php if ($r['cibil_company']): ?>
                                        <br><small><strong>Company:</strong> <?= htmlspecialchars($r['cibil_company']) ?></small>
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
document.getElementById('cibilForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';

    try {
        const formData = new FormData(this);
        formData.append('_csrf_token', CSRF_TOKEN);
        const result = await ajaxPost(BASE_URL + '/services/cibil/submit', formData);
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
    btn.innerHTML = '<i class="bi bi-send me-2"></i>Submit CIBIL Request';
});
</script>
