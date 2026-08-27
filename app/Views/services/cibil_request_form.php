<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-credit-card me-2"></i>CIBIL Requests</h4>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cibilModal">
        <i class="bi bi-plus-lg me-1"></i> New CIBIL Request
    </button>
</div>

<!-- Recent Requests Table -->
<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0" style="min-width:1100px">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Customer Name</th>
                    <th>PAN</th>
                    <th>Mobile</th>
                    <th>Loan Type</th>
                    <th>Salary</th>
                    <th>Requirement</th>
                    <th>Admin Status</th>
                    <th>Actual CIBIL</th>
                    <th>Reply</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No CIBIL requests yet. Click <strong>+ New CIBIL Request</strong> to start.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><small class="text-muted"><?= formatDate($r['created_at'], 'd M Y (IST)') ?></small></td>
                            <td><strong><?= htmlspecialchars($r['name_as_pan']) ?></strong></td>
                            <td><code><?= htmlspecialchars($r['pan_no']) ?></code></td>
                            <td><?= htmlspecialchars($r['mobile']) ?></td>
                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($r['loan_type']) ?></span></td>
                            <td>₹<?= number_format((int)str_replace(['₹',','], '', $r['monthly_salary'])) ?></td>
                            <td><small><?= htmlspecialchars($r['requirement'] ?? '—') ?></small></td>
                            <td>
                                <?php if ($r['status'] === 'replied'): ?>
                                    <span class="badge bg-success">Replied</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['cibil_score_actual']): ?>
                                    <span class="badge bg-<?= $r['cibil_score_actual'] >= 750 ? 'success' : ($r['cibil_score_actual'] >= 650 ? 'warning' : 'danger') ?>">
                                        <?= $r['cibil_score_actual'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'replied'): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" style="min-width:280px">
                                            <li class="px-3 py-2">
                                                <small><strong>Main Status:</strong> <?= htmlspecialchars($r['main_status'] ?? 'N/A') ?></small><br>
                                                <small><strong>Sub Status:</strong> <?= htmlspecialchars($r['sub_status'] ?? 'N/A') ?></small><br>
                                                <small><strong>CIBIL Company:</strong> <?= htmlspecialchars($r['cibil_company'] ?? 'N/A') ?></small><br>
                                                <small><strong>Admin Remarks:</strong> <?= htmlspecialchars($r['admin_remarks'] ?? '—') ?></small>
                                            </li>
                                            <?php if ($r['cibil_pdf1']): ?>
                                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/uploads/cibil/<?= htmlspecialchars($r['cibil_pdf1']) ?>" target="_blank"><i class="bi bi-file-pdf me-2 text-danger"></i>CIBIL Report 1</a></li>
                                            <?php endif; ?>
                                            <?php if ($r['cibil_pdf2']): ?>
                                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/public/uploads/cibil/<?= htmlspecialchars($r['cibil_pdf2']) ?>" target="_blank"><i class="bi bi-file-pdf me-2 text-danger"></i>CIBIL Report 2</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New CIBIL Request Modal -->
<div class="modal fade" id="cibilModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i>Add New CIBIL Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="cibilSubmitBtn" onclick="submitCibil()">
                    <i class="bi bi-send me-1"></i>Submit
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function submitCibil() {
    const form = document.getElementById('cibilForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const btn = document.getElementById('cibilSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';
    try {
        const formData = new FormData(form);
        formData.append('_csrf_token', CSRF_TOKEN);
        const result = await ajaxPost(BASE_URL + '/services/cibil/submit', formData);
        if (result && result.success) {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('cibilModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.error || 'Failed', 'danger');
        }
    } catch(err) { showToast('Error: ' + err.message, 'danger'); }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-1"></i>Submit';
}
</script>
