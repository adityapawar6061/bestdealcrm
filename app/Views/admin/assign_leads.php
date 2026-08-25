<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-person-check me-2"></i>Assign Application Records</h4>
    <a href="<?= BASE_URL ?>/admin/leads" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Leads
    </a>
</div>

<!-- Filter Panel -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-funnel me-1"></i> Filter Leads</h6>
    <div class="row g-3">
        <div class="col-md-3 col-lg-2">
            <label class="form-label small fw-semibold">Location</label>
            <select id="filterLocation" class="form-select form-select-sm">
                <option value="">All Locations</option>
            </select>
        </div>
        <div class="col-md-3 col-lg-2">
            <label class="form-label small fw-semibold">State</label>
            <select id="filterState" class="form-select form-select-sm">
                <option value="">All States</option>
            </select>
        </div>
        <div class="col-md-3 col-lg-2">
            <label class="form-label small fw-semibold">Response Date</label>
            <select id="filterResponseDate" class="form-select form-select-sm">
                <option value="">All Dates</option>
            </select>
        </div>
        <div class="col-md-3 col-lg-2">
            <label class="form-label small fw-semibold">Data Type</label>
            <select id="filterDataType" class="form-select form-select-sm">
                <option value="">All Types</option>
            </select>
        </div>
        <div class="col-md-3 col-lg-2">
            <label class="form-label small fw-semibold">Bank Name</label>
            <select id="filterBankName" class="form-select form-select-sm">
                <option value="">All Banks</option>
            </select>
        </div>
        <div class="col-md-3 col-lg-2">
            <label class="form-label small fw-semibold">Search</label>
            <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Name / Mobile / ID">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary btn-sm" onclick="loadFilteredLeads()">
                <i class="bi bi-search me-1"></i> Apply Filters
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                <i class="bi bi-x-circle me-1"></i> Reset
            </button>
            <span class="ms-auto align-self-center">
                <strong id="totalFilteredCount">0</strong> leads found
            </span>
        </div>
    </div>
</div>

<!-- Assign Panel + Table -->
<div class="row g-4">
    <div class="col-lg-12">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-table me-1"></i> Filtered Leads
                    <span class="badge bg-primary ms-2" id="selectedBadge">0 selected</span>
                </h6>
                <div class="d-flex gap-2 align-items-center">
                    <label class="form-label small fw-semibold mb-0 me-1">Number of Records:</label>
                    <input type="number" id="numRecords" class="form-control form-control-sm" style="width:80px" value="50" min="1" max="5000" onchange="loadFilteredLeads()">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0" id="leadsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                            <th style="width:50px">#</th>
                            <th>Customer Name</th>
                            <th>Mobile</th>
                            <th>Location</th>
                            <th>State</th>
                            <th>Existing LA</th>
                            <th>Salary</th>
                            <th>Actual Salary</th>
                            <th>Data Type</th>
                            <th>Bank Name</th>
                            <th>Response Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="leadsBody">
                        <tr><td colspan="13" class="text-center py-4 text-muted">Click "Apply Filters" to load leads.</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3" id="paginationRow" style="display:none !important">
                <small class="text-muted" id="paginationInfo"></small>
                <div id="paginationButtons" class="btn-group btn-group-sm"></div>
            </div>
        </div>
    </div>

    <!-- Assignment Panel (sticky sidebar) -->
    <div class="col-lg-12">
        <div class="table-container border-primary">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-check me-1"></i> Assign Selected Records</h6>
            </div>
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Assign to User</label>
                    <select id="assignTo" class="form-select">
                        <option value="">Select User...</option>
                        <?php foreach ($agents as $agent): ?>
                            <option value="<?= $agent['id'] ?>"><?= htmlspecialchars($agent['name']) ?> (<?= htmlspecialchars($agent['role_name'] ?? '') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Records to Assign</label>
                    <div class="form-control form-control-sm bg-light" id="assignCount">0 selected</div>
                </div>
                <div class="col-md-5">
                    <button type="button" class="btn btn-primary w-100" onclick="assignLeads()" id="assignBtn">
                        <i class="bi bi-person-check me-1"></i> Assign Selected Leads
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var currentLeads = [];
var selectedIds = new Set();
var currentPage = 1;
var totalPages = 1;
var debounceTimer = null;

// Load filter options on page load
document.addEventListener('DOMContentLoaded', function() {
    loadFilterOptions();
    loadFilteredLeads();
});

// Auto-load filters on Enter key
document.getElementById('filterSearch').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') loadFilteredLeads();
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadFilteredLeads, 500);
});

function loadFilterOptions() {
    ajaxGet(BASE_URL + '/admin/leads/assign/data?get_filters=1').then(function(result) {
        if (result && result.success) {
            populateSelect('filterLocation', result.locations || []);
            populateSelect('filterState', result.states || []);
            populateSelect('filterResponseDate', result.response_dates || []);
            populateSelect('filterDataType', result.data_types || []);
            populateSelect('filterBankName', result.bank_names || []);
        }
    });
}

function populateSelect(id, items) {
    var select = document.getElementById(id);
    var firstOption = select.options[0].text;
    select.innerHTML = '<option value="">' + firstOption + '</option>';
    items.forEach(function(item) {
        if (item && item.trim()) {
            var opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            select.appendChild(opt);
        }
    });
}

function resetFilters() {
    document.getElementById('filterLocation').value = '';
    document.getElementById('filterState').value = '';
    document.getElementById('filterResponseDate').value = '';
    document.getElementById('filterDataType').value = '';
    document.getElementById('filterBankName').value = '';
    document.getElementById('filterSearch').value = '';
    selectedIds.clear();
    updateSelectionUI();
    loadFilteredLeads();
}

function loadFilteredLeads(page) {
    page = page || 1;
    currentPage = page;
    var perPage = parseInt(document.getElementById('numRecords').value) || 50;

    var params = new URLSearchParams();
    params.set('page', page);
    params.set('per_page', perPage);
    var loc = document.getElementById('filterLocation').value;
    var state = document.getElementById('filterState').value;
    var rdate = document.getElementById('filterResponseDate').value;
    var dtype = document.getElementById('filterDataType').value;
    var bank = document.getElementById('filterBankName').value;
    var search = document.getElementById('filterSearch').value.trim();

    if (loc) params.set('location', loc);
    if (state) params.set('state', state);
    if (rdate) params.set('response_date', rdate);
    if (dtype) params.set('data_type', dtype);
    if (bank) params.set('bank_name', bank);
    if (search) params.set('search', search);

    var url = BASE_URL + '/admin/leads/assign/data?' + params.toString();

    document.getElementById('leadsBody').innerHTML = '<tr><td colspan="13" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Loading...</td></tr>';

    ajaxGet(url).then(function(result) {
        if (!result || !result.success) {
            document.getElementById('leadsBody').innerHTML = '<tr><td colspan="13" class="text-center py-3 text-danger">Error loading leads.</td></tr>';
            return;
        }

        currentLeads = result.data || [];
        totalPages = result.total_pages || 1;
        var total = result.total || 0;

        document.getElementById('totalFilteredCount').textContent = total;

        if (currentLeads.length === 0) {
            document.getElementById('leadsBody').innerHTML = '<tr><td colspan="13" class="text-center py-4 text-muted">No leads found matching your filters.</td></tr>';
            document.getElementById('paginationRow').style.display = 'none';
            return;
        }

        var html = '';
        currentLeads.forEach(function(lead) {
            var checked = selectedIds.has(lead.id) ? ' checked' : '';
            html += '<tr>';
            html += '<td><input type="checkbox" class="lead-check" value="' + lead.id + '"' + checked + ' onchange="toggleLead(' + lead.id + ', this.checked)"></td>';
            html += '<td class="text-muted small">' + lead.id + '</td>';
            html += '<td class="fw-semibold">' + escapeHtml(lead.customer_name || '—') + '</td>';
            html += '<td>' + escapeHtml(lead.mobile_number || '—') + '</td>';
            html += '<td><small>' + escapeHtml(lead.location || '—') + '</small></td>';
            html += '<td><small>' + escapeHtml(lead.state || '—') + '</small></td>';
            html += '<td><small>' + escapeHtml(lead.existing_la || '—') + '</small></td>';
            html += '<td class="text-end"><small>' + escapeHtml(lead.salary || '—') + '</small></td>';
            html += '<td class="text-end"><small>' + escapeHtml(lead.actual_salary || '—') + '</small></td>';
            html += '<td><span class="badge bg-light text-dark">' + escapeHtml(lead.data_type || '—') + '</span></td>';
            html += '<td><small>' + escapeHtml(lead.bank_name || '—') + '</small></td>';
            html += '<td><small class="text-muted">' + escapeHtml((lead.response_date && lead.response_date !== '0000-00-00') ? lead.response_date : '—') + '</small></td>';
            html += '<td><span class="badge bg-' + getStageColor(lead.workflow_stage) + '">' + escapeHtml(formatStage(lead.workflow_stage)) + '</span></td>';
            html += '</tr>';
        });
        document.getElementById('leadsBody').innerHTML = html;

        // Pagination
        if (totalPages > 1) {
            document.getElementById('paginationRow').style.display = 'flex';
            document.getElementById('paginationInfo').textContent = 'Page ' + currentPage + ' of ' + totalPages + ' (' + total + ' total)';
            var phtml = '';
            if (currentPage > 1) phtml += '<button class="btn btn-outline-secondary" onclick="loadFilteredLeads(' + (currentPage - 1) + ')">« Prev</button>';
            var start = Math.max(1, currentPage - 2);
            var end = Math.min(totalPages, currentPage + 2);
            for (var i = start; i <= end; i++) {
                phtml += '<button class="btn btn-' + (i === currentPage ? 'primary' : 'outline-secondary') + '" onclick="loadFilteredLeads(' + i + ')">' + i + '</button>';
            }
            if (currentPage < totalPages) phtml += '<button class="btn btn-outline-secondary" onclick="loadFilteredLeads(' + (currentPage + 1) + ')">Next »</button>';
            document.getElementById('paginationButtons').innerHTML = phtml;
        } else {
            document.getElementById('paginationRow').style.display = 'none';
        }

        updateSelectionUI();
    });
}

function toggleSelectAll(cb) {
    document.querySelectorAll('.lead-check').forEach(function(c) {
        c.checked = cb.checked;
        var id = parseInt(c.value);
        if (cb.checked) { selectedIds.add(id); } else { selectedIds.delete(id); }
    });
    updateSelectionUI();
}

function toggleLead(id, checked) {
    if (checked) { selectedIds.add(id); } else { selectedIds.delete(id); }
    updateSelectionUI();
}

function updateSelectionUI() {
    var count = selectedIds.size;
    document.getElementById('selectedBadge').textContent = count + ' selected';
    document.getElementById('assignCount').textContent = count + ' record' + (count !== 1 ? 's' : '') + ' selected';
    document.getElementById('assignBtn').disabled = count === 0;
}

async function assignLeads() {
    var agentId = document.getElementById('assignTo').value;
    if (selectedIds.size === 0) { showToast('Please select at least one lead.', 'warning'); return; }
    if (!agentId) { showToast('Please select a user to assign to.', 'warning'); return; }

    var ids = Array.from(selectedIds);

    if (!confirm('Assign ' + ids.length + ' lead(s) to this user?')) return;

    var formData = new FormData();
    formData.append('assigned_to', agentId);
    ids.forEach(function(id) { formData.append('lead_ids[]', id); });

    document.getElementById('assignBtn').disabled = true;
    document.getElementById('assignBtn').innerHTML = '<span class="spinner-border spinner-border-sm"></span> Assigning...';

    var result = await ajaxPost(BASE_URL + '/admin/leads/assign', formData);

    document.getElementById('assignBtn').disabled = false;
    document.getElementById('assignBtn').innerHTML = '<i class="bi bi-person-check me-1"></i> Assign Selected Leads';

    if (result && result.success) {
        showToast(result.message, 'success');
        selectedIds.clear();
        updateSelectionUI();
        loadFilteredLeads(currentPage);
    } else {
        showToast(result.error || 'Assignment failed.', 'danger');
    }
}

function getStageColor(stage) {
    var colors = {
        'LEAD_UPLOADED': 'secondary', 'LEAD_ASSIGNED': 'info',
        'AGENT_DRAFT': 'warning', 'AGENT_SUBMITTED': 'primary',
        'ADMIN_REVIEW_1': 'danger', 'LOGIN_AGENT_ASSIGNED': 'info',
        'LOGIN_AGENT_DRAFT': 'warning', 'ADMIN_REVIEW_2': 'danger',
        'LOGIN_APPROVED': 'success', 'POST_LOGIN': 'info',
        'UNDERWRITING': 'primary', 'DISPATCH': 'warning',
        'COMPLETED': 'success', 'REJECTED': 'danger'
    };
    return colors[stage] || 'secondary';
}

function formatStage(stage) {
    if (!stage) return '—';
    return stage.replace(/_/g, ' ').toLowerCase().replace(/\b\w/g, function(l) { return l.toUpperCase(); });
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
