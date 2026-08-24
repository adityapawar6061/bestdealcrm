<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-cloud-upload me-2"></i>Lead Upload</h4>
    <a href="<?= BASE_URL ?>/admin/leads" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Leads
    </a>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-create">
            <i class="bi bi-file-earmark-plus me-1"></i> Create Template
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-templates">
            <i class="bi bi-folder me-1"></i> View / Download Templates
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-upload">
            <i class="bi bi-upload me-1"></i> Upload Leads
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- TAB 1: Create Template -->
    <div class="tab-pane fade show active" id="tab-create">
        <div class="table-container">
            <h6 class="fw-bold mb-2"><i class="bi bi-grid me-1"></i> Create Upload Template</h6>
            <p class="text-muted small mb-3">Select the columns you want in your template, give it a name, and download a ready-to-fill CSV file.</p>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Template Name</label>
                    <input type="text" id="templateName" class="form-control form-control-sm" placeholder="e.g., March 2026 Lead Upload">
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label small fw-semibold mb-0">Available Columns</label>
                    <div>
                        <button type="button" class="btn btn-link btn-sm p-0 me-3" onclick="toggleAllColumns(true)">Select All</button>
                        <button type="button" class="btn btn-link btn-sm p-0" onclick="toggleAllColumns(false)">Deselect All</button>
                    </div>
                </div>

                <div class="row g-2" id="columnCheckboxes">
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="customer_name" id="col_customer_name" checked>
                            <label class="form-check-label small" for="col_customer_name">Customer Name</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="mobile_number" id="col_mobile_number" checked>
                            <label class="form-check-label small" for="col_mobile_number">Mobile Number</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="location" id="col_location" checked>
                            <label class="form-check-label small" for="col_location">Location</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="state" id="col_state" checked>
                            <label class="form-check-label small" for="col_state">State</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="existing_la" id="col_existing_la" checked>
                            <label class="form-check-label small" for="col_existing_la">Existing LA</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="salary" id="col_salary" checked>
                            <label class="form-check-label small" for="col_salary">Salary</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="actual_salary" id="col_actual_salary" checked>
                            <label class="form-check-label small" for="col_actual_salary">Actual Salary</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="dtmf_input" id="col_dtmf_input">
                            <label class="form-check-label small" for="col_dtmf_input">DTMF Input</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="response_date" id="col_response_date" checked>
                            <label class="form-check-label small" for="col_response_date">Response Date</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="data_type" id="col_data_type" checked>
                            <label class="form-check-label small" for="col_data_type">Data Type</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="bank_name" id="col_bank_name" checked>
                            <label class="form-check-label small" for="col_bank_name">Bank Name</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="current_status" id="col_current_status">
                            <label class="form-check-label small" for="col_current_status">Current Status</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="update_status" id="col_update_status">
                            <label class="form-check-label small" for="col_update_status">Update Status</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="remark" id="col_remark">
                            <label class="form-check-label small" for="col_remark">Remark</label>
                        </div>
                    </div>
                    <div class="col-md-4 col-lg-3">
                        <div class="form-check card card-body p-2 mb-0 border">
                            <input class="form-check-input col-check" type="checkbox" value="pan_number" id="col_pan_number">
                            <label class="form-check-label small" for="col_pan_number">PAN Number</label>
                        </div>
                    </div>
                </div>

                <div class="mt-2">
                    <small class="text-muted" id="selectedCount">9 columns selected</small>
                </div>
            </div>

            <!-- Column Labels -->
            <div class="mb-3">
                <label class="form-label small fw-semibold">Custom Column Headers (optional - leave default to use field names)</label>
                <div id="columnLabels" class="border rounded p-3" style="max-height:300px;overflow-y:auto">
                    <!-- Populated by JS based on checked columns -->
                </div>
            </div>

            <button class="btn btn-primary btn-sm" onclick="saveTemplate()">
                <i class="bi bi-download me-1"></i> Create &amp; Download Template
            </button>
        </div>
    </div>

    <!-- TAB 2: View / Download Templates -->
    <div class="tab-pane fade" id="tab-templates">
        <div class="table-container">
            <h6 class="fw-bold mb-3"><i class="bi bi-folder me-1"></i> Saved Templates</h6>
            <div id="templatesList">
                <p class="text-muted">Loading templates...</p>
            </div>
        </div>
    </div>

    <!-- TAB 3: Upload Leads -->
    <div class="tab-pane fade" id="tab-upload">
        <!-- Step 1: File Upload -->
        <div id="step1" class="table-container">
            <h6 class="fw-bold mb-3"><i class="bi bi-upload me-1"></i> Upload Leads File</h6>
            <p class="text-muted small mb-3">Upload a CSV file. The system will auto-detect columns and suggest mappings.</p>

            <form id="uploadForm" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Select CSV File</label>
                        <input type="file" name="lead_file" class="form-control" accept=".csv" required>
                        <small class="text-muted">Supported: CSV only. Max 10MB.</small>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100" onclick="uploadFile()">
                            <i class="bi bi-upload me-1"></i> Upload &amp; Map
                        </button>
                    </div>
                </div>
            </form>

            <div id="uploadProgress" class="mt-3 d-none">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%">Processing...</div>
                </div>
            </div>
        </div>

        <!-- Step 2: Column Mapping -->
        <div id="step2" class="table-container mt-4 d-none">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-1"></i> Map Columns</h6>
                <button class="btn btn-outline-secondary btn-sm" onclick="resetUpload()">
                    <i class="bi bi-x me-1"></i> Cancel
                </button>
            </div>
            <p class="text-muted small">Map each CSV column to a database field. Leave as "— Skip —" to ignore that column.</p>

            <div class="row mb-3">
                <div class="col-md-6">
                    <strong class="small text-muted">CSV Column</strong>
                </div>
                <div class="col-md-1 text-center"></div>
                <div class="col-md-5">
                    <strong class="small text-muted">Maps To</strong>
                </div>
            </div>

            <form id="mappingForm">
                <?= csrfField() ?>
                <input type="hidden" name="upload_id" id="uploadId">
                <div id="mappingFields" class="mb-3"></div>

                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-primary" onclick="submitMapping()">
                        <i class="bi bi-check-lg me-1"></i> Import Leads
                    </button>
                </div>
            </form>

            <div class="mt-2">
                <h6 class="small fw-bold text-muted mb-2">Preview (first 5 rows):</h6>
                <div class="table-responsive" style="max-height:300px;overflow-y:auto">
                    <table class="table table-sm table-bordered" id="previewTable">
                        <thead class="table-light" id="previewHead"></thead>
                        <tbody id="previewBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Step 3: Results -->
        <div id="step3" class="table-container mt-4 d-none">
            <h6 class="fw-bold mb-3"><i class="bi bi-clipboard-check me-1"></i> Import Results</h6>
            <div id="importResults"></div>
        </div>
    </div>
</div>

<script>
// Available DB columns with labels
var DB_FIELDS = {
    'customer_name':   'Customer Name',
    'mobile_number':   'Mobile Number',
    'location':        'Location',
    'state':           'State',
    'existing_la':     'Existing LA',
    'salary':          'Salary',
    'actual_salary':   'Actual Salary',
    'dtmf_input':      'DTMF Input',
    'response_date':   'Response Date',
    'data_type':       'Data Type',
    'bank_name':       'Bank Name',
    'current_status':  'Current Status',
    'update_status':   'Update Status',
    'remark':          'Remark',
    'pan_number':      'PAN Number'
};

function toggleAllColumns(state) {
    document.querySelectorAll('.col-check').forEach(function(cb) { cb.checked = state; });
    updateSelectedCount();
    renderColumnLabels();
}

function updateSelectedCount() {
    var count = document.querySelectorAll('.col-check:checked').length;
    document.getElementById('selectedCount').textContent = count + ' column' + (count !== 1 ? 's' : '') + ' selected';
}

function renderColumnLabels() {
    var container = document.getElementById('columnLabels');
    var html = '';
    document.querySelectorAll('.col-check:checked').forEach(function(cb) {
        var val = cb.value;
        var label = DB_FIELDS[val] || val;
        html += '<div class="row align-items-center mb-2">';
        html += '<div class="col-md-4"><small class="text-muted">' + escapeHtml(label) + '</small></div>';
        html += '<div class="col-md-8"><input type="text" class="form-control form-control-sm col-label-input" data-field="' + val + '" value="' + escapeHtml(label) + '" placeholder="Column header in CSV"></div>';
        html += '</div>';
    });
    if (!html) html = '<p class="text-muted small mb-0">No columns selected.</p>';
    container.innerHTML = html;
}

// Listen for checkbox changes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('col-check')) {
        updateSelectedCount();
        renderColumnLabels();
    }
});

// Initial render
renderColumnLabels();

function saveTemplate() {
    var name = document.getElementById('templateName').value.trim();
    if (!name) { showToast('Please enter a template name.', 'warning'); return; }

    var columns = [];
    var labels = {};
    document.querySelectorAll('.col-check:checked').forEach(function(cb) {
        var field = cb.value;
        columns.push(field);
        var input = document.querySelector('.col-label-input[data-field="' + field + '"]');
        labels[field] = input ? input.value.trim() : (DB_FIELDS[field] || field);
    });

    if (columns.length === 0) { showToast('Select at least one column.', 'warning'); return; }

    // Build CSV content
    var header = columns.map(function(c) { return labels[c] || c; });
    var csv = header.map(function(h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(',') + '\n';

    // Download the CSV
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = name.replace(/[^a-z0-9]/gi, '_') + '_template.csv';
    link.click();

    // Save to server
    var formData = new FormData();
    formData.append('template_name', name);
    columns.forEach(function(c) { formData.append('columns[]', c); });
    ajaxPost(BASE_URL + '/admin/leads/template/store', formData).then(function(result) {
        if (result && result.success) {
            showToast('Template created successfully!', 'success');
        }
    });
}

async function loadTemplates() {
    var result = await ajaxGet(BASE_URL + '/admin/leads/templates');
    var container = document.getElementById('templatesList');
    if (!result || !result.success || !result.templates || result.templates.length === 0) {
        container.innerHTML = '<p class="text-muted">No templates found. Create one in the first tab.</p>';
        return;
    }
    var html = '<table class="table table-sm table-hover"><thead class="table-light"><tr><th>Name</th><th>Columns</th><th>Created By</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
    result.templates.forEach(function(t) {
        var cols = [];
        try { cols = JSON.parse(t.columns || '[]'); } catch(e) {}
        html += '<tr>';
        html += '<td><strong>' + escapeHtml(t.name) + '</strong></td>';
        html += '<td><span class="badge bg-light text-dark">' + cols.length + ' columns</span></td>';
        html += '<td><small class="text-muted">' + escapeHtml(t.created_by_name || '—') + '</small></td>';
        html += '<td><small class="text-muted">' + escapeHtml(t.created_at || '') + '</small></td>';
        html += '<td><a href="' + BASE_URL + '/admin/leads/template/' + t.id + '" class="btn btn-outline-primary btn-sm"><i class="bi bi-download"></i> Download</a></td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    container.innerHTML = html;
}

document.querySelector('[data-bs-target="#tab-templates"]').addEventListener('shown.bs.tab', loadTemplates);

// ---- Upload & Mapping ----

function autoMapColumns(csvCols) {
    var mapping = {};
    csvCols.forEach(function(col) {
        var lower = col.toLowerCase().replace(/[^a-z0-9]/g, '');
        for (var dbField in DB_FIELDS) {
            var dbLabel = DB_FIELDS[dbField].toLowerCase().replace(/[^a-z0-9]/g, '');
            if (lower === dbLabel || lower.includes(dbLabel) || dbLabel.includes(lower)) {
                mapping[col] = dbField;
                break;
            }
        }
    });
    return mapping;
}

async function uploadFile() {
    var form = document.getElementById('uploadForm');
    if (!form.querySelector('input[name="lead_file"]').value) {
        showToast('Please select a CSV file.', 'warning');
        return;
    }
    var formData = new FormData(form);
    document.getElementById('uploadProgress').classList.remove('d-none');

    try {
        var response = await fetch(BASE_URL + '/admin/leads/upload/process', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        var text = await response.text();
        var result;
        try { result = JSON.parse(text); } catch(e) {
            console.error('Non-JSON:', text.substring(0, 500));
            showToast('Server error. Check console.', 'danger');
            document.getElementById('uploadProgress').classList.add('d-none');
            return;
        }
        document.getElementById('uploadProgress').classList.add('d-none');

        if (result.success) {
            document.getElementById('uploadId').value = result.upload_id;
            var autoMap = autoMapColumns(result.columns);

            var html = '';
            result.columns.forEach(function(col) {
                var autoVal = autoMap[col] || '';
                html += '<div class="row align-items-center mb-2">';
                html += '<div class="col-md-5"><span class="fw-semibold small">' + escapeHtml(col) + '</span> <span class="text-muted small">(CSV)</span></div>';
                html += '<div class="col-md-1 text-center"><i class="bi bi-arrow-right text-primary"></i></div>';
                html += '<div class="col-md-6"><select name="mapping[' + escapeHtml(col) + ']" class="form-select form-select-sm">';
                html += '<option value="">— Skip —</option>';
                for (var dbField in DB_FIELDS) {
                    var sel = autoVal === dbField ? ' selected' : '';
                    html += '<option value="' + dbField + '"' + sel + '>' + DB_FIELDS[dbField] + ' (' + dbField + ')</option>';
                }
                html += '</select></div></div>';
            });
            document.getElementById('mappingFields').innerHTML = html;

            // Preview
            var headHtml = '<tr>';
            result.columns.forEach(function(col) { headHtml += '<th class="small">' + escapeHtml(col) + '</th>'; });
            headHtml += '</tr>';
            document.getElementById('previewHead').innerHTML = headHtml;

            var bodyHtml = '';
            result.sample.forEach(function(row) {
                bodyHtml += '<tr>';
                result.columns.forEach(function(col) { bodyHtml += '<td class="small">' + escapeHtml(row[col] || '') + '</td>'; });
                bodyHtml += '</tr>';
            });
            document.getElementById('previewBody').innerHTML = bodyHtml;

            document.getElementById('step1').classList.add('d-none');
            document.getElementById('step2').classList.remove('d-none');
            showToast('File uploaded. Map columns below, then import.', 'info');
        } else {
            showToast(result.error || 'Upload failed.', 'danger');
        }
    } catch (err) {
        document.getElementById('uploadProgress').classList.add('d-none');
        showToast('Network error: ' + err.message, 'danger');
    }
}

async function submitMapping() {
    var form = document.getElementById('mappingForm');
    var formData = new FormData(form);

    try {
        var response = await fetch(BASE_URL + '/admin/leads/upload/mapping', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        var text = await response.text();
        var result;
        try {
            result = JSON.parse(text);
        } catch(e) {
            console.error('Non-JSON import response:', text.substring(0, 1000));
            showToast('Server returned an error. The file may be too large or the session expired. Try uploading again.', 'danger');
            return;
        }

        document.getElementById('step2').classList.add('d-none');
        document.getElementById('step3').classList.remove('d-none');

        if (result.success) {
            var html = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>' + escapeHtml(result.message) + '</div>';
            if (result.errors && result.errors.length > 0) {
                html += '<div class="mt-2"><small class="text-muted">Skipped rows:</small><ul class="small" style="max-height:200px;overflow-y:auto">';
                result.errors.forEach(function(e) { html += '<li class="text-danger">' + escapeHtml(e) + '</li>'; });
                html += '</ul></div>';
            }
            html += '<div class="d-flex gap-2 mt-3">';
            html += '<a href="' + BASE_URL + '/admin/leads/assign" class="btn btn-primary btn-sm"><i class="bi bi-person-check me-1"></i> Assign Leads Now</a>';
            html += '<a href="' + BASE_URL + '/admin/leads" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list-ul me-1"></i> View All Leads</a>';
            html += '<button class="btn btn-outline-secondary btn-sm" onclick="resetUpload()"><i class="bi bi-upload me-1"></i> Upload More</button>';
            html += '</div>';
            document.getElementById('importResults').innerHTML = html;
        } else {
            document.getElementById('importResults').innerHTML = '<div class="alert alert-danger">' + escapeHtml(result.error || 'Import failed.') + '</div>';
        }
    } catch (err) {
        showToast('Network error: ' + err.message, 'danger');
    }
}

function resetUpload() {
    document.getElementById('step1').classList.remove('d-none');
    document.getElementById('step2').classList.add('d-none');
    document.getElementById('step3').classList.add('d-none');
    document.getElementById('uploadForm').reset();
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
