<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add Data Entry</h5>
                <a href="<?= BASE_URL ?>/admin/data-dashboard" class="btn btn-sm btn-light"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
            </div>
            <div class="card-body">
                <form id="addDataForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign to User <span class="text-danger">*</span></label>
                        <select class="form-select" name="user_id" required>
                            <option value="">Select User</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
                            <option value="RNR">RNR</option>
                            <option value="Disconnected">Disconnected</option>
                            <option value="Not Interested">Not Interested</option>
                            <option value="Call Back">Call Back</option>
                            <option value="Follow Up">Follow Up</option>
                            <option value="Not Eligible">Not Eligible</option>
                            <option value="Self Employed">Self Employed</option>
                            <option value="Lead">Lead</option>
                            <option value="DNC">DNC</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="Enter remarks (optional)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100" id="submitBtn">
                        <i class="bi bi-save me-2"></i>Save Entry
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('addDataForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    try {
        const formData = new FormData(this);
        formData.append('_csrf_token', CSRF_TOKEN);
        const result = await ajaxPost(BASE_URL + '/admin/data-add/submit', formData);
        if (result && result.success) {
            showToast(result.message, 'success');
            this.reset();
        } else {
            showToast(result.error || 'Failed', 'danger');
        }
    } catch(err) {
        showToast('Error: ' + err.message, 'danger');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-save me-2"></i>Save Entry';
});
</script>
