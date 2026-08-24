<div class="page-header">
    <h4><i class="bi bi-person-check me-2"></i>Assign Leads</h4>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="table-container">
            <h6 class="fw-bold mb-3">Unassigned Leads</h6>
            <form id="assignForm">
                <?= csrfField() ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Mobile</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leads['data'])): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No unassigned leads.</td></tr>
                            <?php else: ?>
                                <?php foreach ($leads['data'] as $lead): ?>
                                <tr>
                                    <td><input type="checkbox" name="lead_ids[]" value="<?= $lead['id'] ?>" class="lead-check"></td>
                                    <td><?= $lead['id'] ?></td>
                                    <td><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                                    <td><small><?= htmlspecialchars($lead['location'] ?? '-') ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="table-container">
            <h6 class="fw-bold mb-3">Assign To</h6>
            <div class="mb-3">
                <label class="form-label small fw-semibold">Select Agent</label>
                <select id="assignTo" class="form-select">
                    <option value="">Choose Agent...</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= $agent['id'] ?>"><?= htmlspecialchars($agent['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="btn btn-primary w-100" onclick="assignLeads()">
                <i class="bi bi-person-check me-1"></i> Assign Selected Leads
            </button>
        </div>
    </div>
</div>

<script>
function toggleSelectAll(cb) {
    document.querySelectorAll('.lead-check').forEach(c => c.checked = cb.checked);
}

async function assignLeads() {
    const checked = document.querySelectorAll('.lead-check:checked');
    const agentId = document.getElementById('assignTo').value;
    
    if (checked.length === 0) return alert('Please select leads.');
    if (!agentId) return alert('Please select an agent.');
    
    const formData = new FormData();
    formData.append('assigned_to', agentId);
    checked.forEach(c => formData.append('lead_ids[]', c.value));
    
    const result = await ajaxPost('/bestdealcrm/admin/leads/assign', formData);
    if (result.success) {
        alert(result.message);
        location.reload();
    } else {
        alert(result.error || 'Error occurred.');
    }
}
</script>
