<div class="row">
    <div class="col-md-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Enter Data</h5>
            </div>
            <div class="card-body">
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
                    <div class="mb-3 p-2 rounded" style="background:#f0fdf4">
                        <small class="text-muted"><strong>Agent:</strong> <?= htmlspecialchars($user['name']) ?></small><br>
                        <small class="text-muted"><strong>Note:</strong> Date and time will be automatically captured in IST timezone.</small>
                    </div>
                    <button type="submit" class="btn btn-success w-100" id="submitBtn">
                        <i class="bi bi-save me-2"></i>Save Entry
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>My Recent Entries</h6>
                <span class="badge bg-light text-dark"><?= number_format($total) ?> total</span>
            </div>
            <div class="card-body p-0">
                <!-- Disposition Filter -->
                <div class="p-3 border-bottom">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-md-6">
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
                    </form>
                </div>

                <div class="table-responsive" style="max-height:600px;overflow-y:auto">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Created</th>
                                <th>Mobile No</th>
                                <th>Customer Name</th>
                                <th>City</th>
                                <th>Salary</th>
                                <th>Loan Amount</th>
                                <th>Disposition</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($entries)): ?>
                                <tr><td colspan="8" class="text-center text-muted py-4">No entries yet. Start entering data!</td></tr>
                            <?php else: ?>
                                <?php foreach ($entries as $e): ?>
                                    <tr>
                                        <td><small class="text-muted"><?= date('m/d/Y<br>h:i A', strtotime($e['created_at'])) ?></small></td>
                                        <td><?= htmlspecialchars($e['mobile_no']) ?></td>
                                        <td><strong><?= htmlspecialchars($e['customer_name']) ?></strong></td>
                                        <td><small><?= htmlspecialchars($e['city']) ?></small></td>
                                        <td>₹<?= number_format((int)str_replace(['₹',','], '', $e['salary'])) ?></td>
                                        <td>₹<?= number_format((int)str_replace(['₹',','], '', $e['loan_amount'])) ?></td>
                                        <td>
                                            <?php
                                            $badgeClass = match($e['disposition']) {
                                                'Follow Up' => 'bg-primary',
                                                'Not Eligible' => 'bg-danger',
                                                'Disconnected' => 'bg-secondary',
                                                'Not Interested' => 'bg-warning text-dark',
                                                'Call Back' => 'bg-info text-dark',
                                                'Lead' => 'bg-success',
                                                'Self Employed' => 'bg-dark',
                                                default => 'bg-light text-dark',
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($e['disposition']) ?></span>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars($e['remarks'] ?? '') ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('dataForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    try {
        const formData = new FormData(this);
        formData.append('_csrf_token', CSRF_TOKEN);
        const result = await ajaxPost(BASE_URL + '/agent/data-entry/submit', formData);
        if (result && result.success) {
            showToast(result.message, 'success');
            this.reset();
            setTimeout(() => location.reload(), 1000);
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
