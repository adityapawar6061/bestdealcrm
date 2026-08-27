<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-arrow-left-right me-2"></i>Reassign Leads</h4>
    <a href="/bestdealcrm/admin/leads" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Leads
    </a>
</div>

<!-- Filters -->
<div class="table-container mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Assigned To</label>
            <select id="filterAssignedTo" class="form-select form-select-sm">
                <option value="">All Agents</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Location</label>
            <select id="filterLocation" class="form-select form-select-sm">
                <option value="">All Locations</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">State</label>
            <select id="filterState" class="form-select form-select-sm">
                <option value="">All States</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Data Type</label>
            <select id="filterDataType" class="form-select form-select-sm">
                <option value="">All Types</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Bank Name</label>
            <select id="filterBankName" class="form-select form-select-sm">
                <option value="">All Banks</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Search</label>
            <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Name / Mobile / ID">
        </div>
    </div>
</div>

<!-- Reassign Panel -->
<div class="table-container mb-3" id="reassignPanel" style="display:none">
    <div class="row align-items-center">
        <div class="col-md-5">
            <label class="form-label small fw-semibold">Reassign Selected To:</label>
            <select id="reassignTo" class="form-select form-select-sm">
                <option value="">Select Agent</option>
                <?php foreach ($agents as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?> (<?= $a['role_name'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">&nbsp;</label>
            <div>
                <button class="btn btn-warning btn-sm" onclick="processReassign()">
                    <i class="bi bi-arrow-left-right me-1"></i> Reassign <span id="reassignCount">0</span> Leads
                </button>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-outline-secondary btn-sm" onclick="clearReassignSelection()">Clear Selection</button>
        </div>
    </div>
</div>

<!-- Leads Table -->
<div class="table-container">
    <div class="table-responsive" style="overflow-x:auto">
        <table class="table table-hover table-sm align-middle mb-0" style="min-width:1000px">
            <thead class="table-light">
                <tr>
                    <th style="width:40px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                    <th>#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Location</th>
                    <th>State</th>
                    <th>Bank Name</th>
                    <th>Assigned To</th>
                    <th>Stage</th>
                </tr>
            </thead>
            <tbody id="leadsTableBody">
                <tr><td colspan="9" class="text-center py-4 text-muted">Loading...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3" id="paginationArea" style="display:none !important">
        <small class="text-muted" id="pageInfo"></small>
        <div id="paginationBtns"></div>
    </div>
</div>

<script>
var currentPage = 1;
var selectedIds = new Set();
var allFilters = {};

function getFilterParams() {
    return {
        assigned_to: document.getElementById('filterAssignedTo').value,
        location: document.getElementById('filterLocation').value,
        state: document.getElementById('filterState').value,
        data_type: document.getElementById('filterDataType').value,
        bank_name: document.getElementById('filterBankName').value,
        search: document.getElementById('filterSearch').value,
        page: currentPage,
        per_page: 50
    };
}

async function loadFilters() {
    var resp = await ajaxGet(BASE_URL + '/admin/leads/reassign/data?get_filters=1');
    if (!resp || !resp.success) return;
    
    fillSelect('filterAssignedTo', resp.assigned_users.map(function(u) { return {value: u.assigned_to, text: u.name + ' (' + u.cnt + ')'}; }), 'All Agents');
    fillSelect('filterLocation', resp.locations.map(function(v) { return {value: v, text: v}; }), 'All Locations');
    fillSelect('filterState', resp.states.map(function(v) { return {value: v, text: v}; }), 'All States');
    fillSelect('filterDataType', resp.data_types.map(function(v) { return {value: v, text: v}; }), 'All Types');
    fillSelect('filterBankName', resp.bank_names.map(function(v) { return {value: v, text: v}; }), 'All Banks');
}

function fillSelect(id, items, allLabel) {
    var sel = document.getElementById(id);
    var current = sel.value;
    sel.innerHTML = '<option value="">' + allLabel + '</option>';
    items.forEach(function(item) {
        var opt = document.createElement('option');
        opt.value = item.value;
        opt.textContent = item.text;
        sel.appendChild(opt);
    });
    sel.value = current;
}

async function loadLeads() {
    var params = getFilterParams();
    var qs = Object.keys(params).filter(function(k) { return params[k]; }).map(function(k) { return k + '=' + encodeURIComponent(params[k]); }).join('&');
    
    var resp = await ajaxGet(BASE_URL + '/admin/leads/reassign/data?' + qs);
    var tbody = document.getElementById('leadsTableBody');
    
    if (!resp || !resp.success || resp.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No leads found.</td></tr>';
        return;
    }
    
    var html = '';
    resp.data.forEach(function(lead) {
        var checked = selectedIds.has(String(lead.id)) ? 'checked' : '';
        html += '<tr>';
        html += '<td><input type="checkbox" class="lead-checkbox" value="' + lead.id + '" ' + checked + ' onchange="toggleLead(' + lead.id + ', this.checked)"></td>';
        html += '<td>' + lead.id + '</td>';
        html += '<td><strong>' + escapeHtml(lead.customer_name || '—') + '</strong></td>';
        html += '<td>' + escapeHtml(lead.mobile_number || '—') + '</td>';
        html += '<td><small>' + escapeHtml(lead.location || '—') + '</small></td>';
        html += '<td><small>' + escapeHtml(lead.state || '—') + '</small></td>';
        html += '<td><small>' + escapeHtml(lead.bank_name || '—') + '</small></td>';
        html += '<td><small>' + escapeHtml(lead.assigned_to_name || '—') + '</small></td>';
        html += '<td>' + (lead.workflow_stage || '—') + '</td>';
        html += '</tr>';
    });
    tbody.innerHTML = html;
    
    updateReassignUI();
    
    // Pagination
    if (resp.total_pages > 1) {
        var pagArea = document.getElementById('paginationArea');
        pagArea.style.display = 'flex';
        document.getElementById('pageInfo').textContent = 'Page ' + resp.page + ' of ' + resp.total_pages + ' (' + resp.total + ' total)';
    }
}

function toggleSelectAll(el) {
    var checked = el.checked;
    document.querySelectorAll('.lead-checkbox').forEach(function(cb) {
        cb.checked = checked;
        if (checked) selectedIds.add(cb.value);
        else selectedIds.delete(cb.value);
    });
    updateReassignUI();
}

function toggleLead(id, checked) {
    if (checked) selectedIds.add(String(id));
    else selectedIds.delete(String(id));
    updateReassignUI();
}

function updateReassignUI() {
    var count = selectedIds.size;
    document.getElementById('reassignCount').textContent = count;
    document.getElementById('reassignPanel').style.display = count > 0 ? 'block' : 'none';
}

function clearReassignSelection() {
    selectedIds.clear();
    document.getElementById('selectAll').checked = false;
    document.querySelectorAll('.lead-checkbox').forEach(function(cb) { cb.checked = false; });
    updateReassignUI();
}

async function processReassign() {
    var toUser = document.getElementById('reassignTo').value;
    if (!toUser) { showToast('Select an agent to reassign to.', 'warning'); return; }
    if (selectedIds.size === 0) { showToast('Select leads first.', 'warning'); return; }
    
    if (!confirm('Reassign ' + selectedIds.size + ' lead(s) to the selected agent?')) return;
    
    var formData = new FormData();
    selectedIds.forEach(function(id) { formData.append('lead_ids[]', id); });
    formData.append('assigned_to', toUser);
    
    var result = await ajaxPost(BASE_URL + '/admin/leads/reassign', formData);
    if (result && result.success) {
        showToast(result.message, 'success');
        selectedIds.clear();
        document.getElementById('reassignTo').value = '';
        loadLeads();
    } else {
        showToast(result.error || 'Reassignment failed.', 'danger');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Init
loadFilters().then(function() { loadLeads(); });

// Auto-reload on filter change
['filterAssignedTo','filterLocation','filterState','filterDataType','filterBankName'].forEach(function(id) {
    document.getElementById(id).addEventListener('change', function() { currentPage = 1; loadLeads(); });
});
document.getElementById('filterSearch').addEventListener('keyup', function() {
    clearTimeout(window._searchTimer);
    window._searchTimer = setTimeout(function() { currentPage = 1; loadLeads(); }, 400);
});
</script>
