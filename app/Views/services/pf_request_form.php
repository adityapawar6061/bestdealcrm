<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-file-earmark-text me-2"></i>PF Requests</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pfModal">
        <i class="bi bi-plus-lg me-1"></i> New PF Request
    </button>
</div>

<!-- Recent Requests Table -->
<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Salary</th>
                    <th>Loan Type</th>
                    <th>Bank</th>
                    <th>CIBIL</th>
                    <th>Admin Status</th>
                    <th>Remarks</th>
                    <th>Files</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No PF requests yet. Click <strong>+ New PF Request</strong> to start.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><strong><?= htmlspecialchars($r['customer_name']) ?></strong></td>
                            <td><?= htmlspecialchars($r['mobile']) ?></td>
                            <td>₹<?= number_format((int)str_replace(['₹',','], '', $r['monthly_salary'])) ?></td>
                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($r['loan_type']) ?></span></td>
                            <td><?= htmlspecialchars($r['processing_bank']) ?></td>
                            <td><span class="badge bg-<?= $r['cibil_score'] >= 750 ? 'success' : ($r['cibil_score'] >= 650 ? 'warning' : 'danger') ?>"><?= $r['cibil_score'] ?></span></td>
                            <td>
                                <?php if ($r['status'] === 'replied'): ?>
                                    <span class="badge bg-<?= $r['admin_approved'] === 'yes' ? 'success' : ($r['admin_approved'] === 'no' ? 'danger' : 'secondary') ?>">
                                        <?= ucfirst($r['admin_approved']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($r['admin_remarks'] ?? '—') ?></small></td>
                            <td>
                                <?php
                                $files = json_decode($r['admin_files'] ?? '[]', true) ?? [];
                                if (!empty($files)):
                                ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-paperclip"></i> <?= count($files) ?>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php foreach ($files as $f): ?>
                                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/uploads/pf/<?= htmlspecialchars($f) ?>" target="_blank"><i class="bi bi-file-earmark me-2"></i><?= htmlspecialchars($f) ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New PF Request Modal -->
<div class="modal fade" id="pfModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-file-earmark-text me-2"></i>Raise PF Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
                                <option value="HDFC">HDFC</option><option value="ICICI">ICICI</option>
                                <option value="Axis">Axis</option><option value="Kotak">Kotak</option>
                                <option value="Bajaj">Bajaj</option><option value="IDFC">IDFC</option>
                                <option value="IndusInd">IndusInd</option><option value="Poonawalla">Poonawalla</option>
                                <option value="Incred">Incred</option><option value="TATA Capital">TATA Capital</option>
                                <option value="Finnable">Finnable</option><option value="Piramal">Piramal</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">CIBIL Score <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="cibil_score" required placeholder="e.g. 798" min="300" max="900">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="pfSubmitBtn" onclick="submitPF()">
                    <i class="bi bi-send me-1"></i>Submit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function submitPF() {
    const form = document.getElementById('pfForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const btn = document.getElementById('pfSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';
    try {
        const formData = new FormData(form);
        formData.append('_csrf_token', CSRF_TOKEN);
        const result = await ajaxPost(BASE_URL + '/services/pf/submit', formData);
        if (result && result.success) {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('pfModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.error || 'Failed', 'danger');
        }
    } catch(err) { showToast('Error: ' + err.message, 'danger'); }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-1"></i>Submit';
}
</script>
