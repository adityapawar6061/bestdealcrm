<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-clipboard-data me-2"></i>CRM Data Entry</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#entryModal">
        <i class="bi bi-plus-lg me-1"></i> Enter Data
    </button>
</div>

<!-- Disposition Filter -->
<div class="table-container mb-3">
    <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Filter by Disposition</label>
            <select name="disposition" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Dispositions</option>
                <?php foreach (['RNR','Disconnected','Not Interested','Call Back','Follow Up','Not Eligible','Self Employed','Lead','DNC'] as $d): ?>
                    <option value="<?= $d ?>" <?= $dispositionFilter === $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($dispositionFilter): ?>
            <div class="col-auto"><a href="<?= BASE_URL ?>/agent/data-entry" class="btn btn-sm btn-outline-secondary">Clear</a></div>
        <?php endif; ?>
        <div class="col text-end">
            <small class="text-muted">Showing entries matching the selected disposition</small>
        </div>
    </form>
</div>

<!-- Entries Table -->
<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Created Date</th>
                    <th>Mobile No</th>
                    <th>Customer Name</th>
                    <th>City</th>
                    <th>Salary</th>
                    <th>Loan Amount</th>
                    <th>Disposition</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No entries yet. Click <strong>+ Enter Data</strong> to start.</td></tr>
                <?php else: ?>
                    <?php foreach ($entries as $e): ?>
                        <tr id="entry-row-<?= $e['id'] ?>">
                            <td><small class="text-muted"><?= date('m/d/Y<br>h:i A', strtotime($e['created_at'])) ?></small></td>
                            <td><?= htmlspecialchars($e['mobile_no']) ?></td>
                            <td><strong><?= htmlspecialchars($e['customer_name']) ?></strong></td>
                            <td><small><?= htmlspecialchars($e['city']) ?></small></td>
                            <td>₹<?= number_format((int)str_replace(['₹',','], '', $e['salary'])) ?></td>
                            <td>₹<?= number_format((int)str_replace(['₹',','], '', $e['loan_amount'])) ?></td>
                            <td>
                                <select class="form-select form-select-sm" style="width:130px;font-size:0.75rem" onchange="updateDisposition(<?= $e['id'] ?>, this.value)">
                                    <?php foreach (['RNR','Disconnected','Not Interested','Call Back','Follow Up','Not Eligible','Self Employed','Lead','DNC'] as $d): ?>
                                        <option value="<?= $d ?>" <?= $e['disposition'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" style="width:120px;font-size:0.75rem" value="<?= htmlspecialchars($e['remarks'] ?? '') ?>" onblur="updateRemarks(<?= $e['id'] ?>, this.value)" placeholder="Add remark...">
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick='editEntry(<?= json_encode($e) ?>)' title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $totalPages = ceil($total / $perPage); if ($totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= ($page-1)*$perPage + 1 ?>–<?= min($page*$perPage, $total) ?> of <?= number_format($total) ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&disposition=<?= urlencode($dispositionFilter) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- New Entry Modal -->
<div class="modal fade" id="entryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Customer Data Entry Form</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="dataForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mobile No <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="mobile_no" required placeholder="Enter mobile number" maxlength="10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="customer_name" required placeholder="Enter customer name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="city" required placeholder="Enter city">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Salary <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="salary" required placeholder="Enter salary amount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Loan Amount <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="loan_amount" required placeholder="Enter loan amount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Disposition <span class="text-danger">*</span></label>
                        <select class="form-select" name="disposition" required>
                            <option value="">Select Disposition</option>
                            <option value="RNR">RNR</option><option value="Disconnected">Disconnected</option>
                            <option value="Not Interested">Not Interested</option><option value="Call Back">Call Back</option>
                            <option value="Follow Up">Follow Up</option><option value="Not Eligible">Not Eligible</option>
                            <option value="Self Employed">Self Employed</option><option value="Lead">Lead</option>
                            <option value="DNC">DNC</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="Enter remarks (optional)"></textarea>
                    </div>
                    <div class="p-2 rounded" style="background:#f0fdf4">
                        <small class="text-muted"><strong>Agent:</strong> <?= htmlspecialchars($user['name']) ?></small><br>
                        <small class="text-muted"><strong>Note:</strong> Date and time will be automatically captured in IST timezone.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="entrySubmitBtn" onclick="submitEntry()">
                    <i class="bi bi-save me-1"></i>Save Entry
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Entry Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mobile No <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="mobile_no" id="edit_mobile_no" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="customer_name" id="edit_customer_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="city" id="edit_city" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Salary <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="salary" id="edit_salary" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Loan Amount <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="loan_amount" id="edit_loan_amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Disposition <span class="text-danger">*</span></label>
                        <select class="form-select" name="disposition" id="edit_disposition" required>
                            <option value="">Select Disposition</option>
                            <option value="RNR">RNR</option><option value="Disconnected">Disconnected</option>
                            <option value="Not Interested">Not Interested</option><option value="Call Back">Call Back</option>
                            <option value="Follow Up">Follow Up</option><option value="Not Eligible">Not Eligible</option>
                            <option value="Self Employed">Self Employed</option><option value="Lead">Lead</option>
                            <option value="DNC">DNC</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea class="form-control" name="remarks" id="edit_remarks" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="editSubmitBtn" onclick="submitEdit()">
                    <i class="bi bi-save me-1"></i>Update Entry
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function submitEntry() {
    const form = document.getElementById('dataForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const btn = document.getElementById('entrySubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
    try {
        const formData = new FormData(form);
        formData.append('_csrf_token', CSRF_TOKEN);
        const result = await ajaxPost(BASE_URL + '/agent/data-entry/submit', formData);
        if (result && result.success) {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('entryModal')).hide();
            form.reset();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.error || 'Failed', 'danger');
        }
    } catch(err) { showToast('Error: ' + err.message, 'danger'); }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save me-1"></i>Save Entry';
}

function editEntry(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_mobile_no').value = data.mobile_no;
    document.getElementById('edit_customer_name').value = data.customer_name;
    document.getElementById('edit_city').value = data.city;
    document.getElementById('edit_salary').value = data.salary;
    document.getElementById('edit_loan_amount').value = data.loan_amount;
    document.getElementById('edit_disposition').value = data.disposition;
    document.getElementById('edit_remarks').value = data.remarks || '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

async function submitEdit() {
    const form = document.getElementById('editForm');
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const btn = document.getElementById('editSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';
    try {
        const formData = new FormData(form);
        formData.append('_csrf_token', CSRF_TOKEN);
        formData.append('_method', 'PUT');
        const result = await ajaxPost(BASE_URL + '/agent/data-entry/update', formData);
        if (result && result.success) {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.error || 'Failed', 'danger');
        }
    } catch(err) { showToast('Error: ' + err.message, 'danger'); }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save me-1"></i>Update Entry';
}

async function updateDisposition(id, value) {
    var formData = new FormData();
    formData.append('id', id);
    formData.append('field', 'disposition');
    formData.append('value', value);
    formData.append('_csrf_token', CSRF_TOKEN);
    const result = await ajaxPost(BASE_URL + '/agent/data-entry/update-field', formData);
    if (result && result.success) {
        showToast('Disposition updated.', 'success');
    } else {
        showToast(result.error || 'Failed', 'danger');
    }
}

async function updateRemarks(id, value) {
    var formData = new FormData();
    formData.append('id', id);
    formData.append('field', 'remarks');
    formData.append('value', value);
    formData.append('_csrf_token', CSRF_TOKEN);
    const result = await ajaxPost(BASE_URL + '/agent/data-entry/update-field', formData);
    if (result && result.success) {
        showToast('Remarks saved.', 'success');
    } else {
        showToast(result.error || 'Failed', 'danger');
    }
}
</script>
