<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-cloud-upload me-2"></i>Lead Upload</h4>
    <a href="/bestdealcrm/admin/leads" class="btn btn-outline-secondary btn-sm">
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
            <i class="bi bi-upload me-1"></i> Upload with Template
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- TAB 1: Create Template -->
    <div class="tab-pane fade show active" id="tab-create">
        <div class="table-container">
            <h6 class="fw-bold mb-2">Create Upload Template</h6>
            <p class="text-muted small mb-3">Create a CSV template with column headers for your lead upload format. Add column names below.</p>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Template Name</label>
                <input type="text" id="templateName" class="form-control form-control-sm" placeholder="e.g., March 2026 Lead Format" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Column Names (one per row)</label>
                <div id="templateColumns">
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm template-col" placeholder="e.g., Customer Name" value="Customer Name">
                        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm template-col" placeholder="e.g., Mobile Number" value="Mobile Number">
                        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm template-col" placeholder="e.g., Location" value="Location">
                        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm template-col" placeholder="e.g., State" value="State">
                        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm template-col" placeholder="e.g., Existing LA" value="Existing LA">
                        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm template-col" placeholder="e.g., Salary" value="Salary">
                        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm template-col" placeholder="e.g., Actual Salary" value="Actual Salary">
                        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm template-col" placeholder="e.g., Bank Name" value="Bank Name">
                        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" class="form-control form-control-sm template-col" placeholder="e.g., Remark" value="Remark">
                        <button class="btn btn-outline-danger btn-sm" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
                    </div>
                </div>
                <button class="btn btn-outline-primary btn-sm mt-1" onclick="addTemplateColumn()">
                    <i class="bi bi-plus me-1"></i> Add Column
                </button>
            </div>

            <button class="btn btn-primary btn-sm" onclick="saveTemplate()">
                <i class="bi bi-save me-1"></i> Create & Download Template
            </button>
        </div>
    </div>

    <!-- TAB 2: View / Download Templates -->
    <div class="tab-pane fade" id="tab-templates">
        <div class="table-container">
            <h6 class="fw-bold mb-3">Saved Templates</h6>
            <div id="templatesList">
                <p class="text-muted">Loading templates...</p>
            </div>
        </div>
    </div>

    <!-- TAB 3: Upload with Template -->
    <div class="tab-pane fade" id="tab-upload">
        <!-- Step 1: File Upload -->
        <div id="step1" class="table-container">
            <h6 class="fw-bold mb-3">Upload Leads File</h6>
            <p class="text-muted small">Upload a CSV or XLSX file. Supported formats: CSV, XLSX. Maximum 10MB.</p>

            <form id="uploadForm" enctype="multipart/form-data">
                <?= csrfField() ?>
                <div class="mb-3">
                    <input type="file" name="lead_file" class="form-control" accept=".csv,.xlsx" required>
                </div>
                <button type="button" class="btn btn-primary" onclick="uploadFile()">
                    <i class="bi bi-upload me-1"></i> Upload & Map Columns
                </button>
            </form>

            <div id="uploadProgress" class="mt-3 d-none">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%">Processing...</div>
                </div>
            </div>
        </div>

        <!-- Step 2: Column Mapping -->
        <div id="step2" class="table-container mt-4 d-none">
            <h6 class="fw-bold mb-3">Map Columns</h6>
            <p class="text-muted small">For each CSV column, select the database field it maps to. Leave as "-- Skip --" to ignore.</p>

            <form id="mappingForm">
                <?= csrfField() ?>
                <input type="hidden" name="upload_id" id="uploadId">
                <div id="mappingFields" class="mb-3"></div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="submitMapping()">
                        <i class="bi bi-check-lg me-1"></i> Import Leads
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetUpload()">Cancel</button>
                </div>
            </form>

            <div class="mt-3">
                <h6 class="small fw-bold text-muted">Preview (first 5 rows):</h6>
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
            <h6 class="fw-bold mb-3">Import Results</h6>
            <div id="importResults"></div>
        </div>
    </div>
</div>

<script>
const DB_FIELDS = {
    'customer_name': 'Customer Name',
    'mobile_number': 'Mobile Number',
    'location': 'Location',
    'state': 'State',
    'existing_la': 'Existing LA',
    'salary': 'Salary',
    'actual_salary': 'Actual Salary',
    'dtmf_input': 'DTMF Input',
    'response_date': 'Response Date',
    'data_type': 'Data Type',
    'bank_name': 'Bank Name',
    'current_status': 'Current Status',
    'update_status': 'Update Status',
    'remark': 'Remark',
    'pan_number': 'PAN Number'
};

function addTemplateColumn() {
    var html = '<div class="input-group mb-2">' +
        '<input type="text" class="form-control form-control-sm template-col" placeholder="e.g., Column Name">' +
        '<button class="btn btn-outline-danger btn-sm" onclick="this.closest(\'.input-group\').remove()"><i class="bi bi-x"></i></button></div>';
    document.getElementById('templateColumns').insertAdjacentHTML('beforeend', html);
}

async function saveTemplate() {
    var name = document.getElementById('templateName').value.trim();
    var cols = [];
    document.querySelectorAll('.template-col').forEach(function(el) {
        var val = el.value.trim();
        if (val) cols.push(val);
    });
    if (!name) { showToast('Enter a template name.', 'warning'); return; }
    if (cols.length === 0) { showToast('Add at least one column.', 'warning'); return; }

    var formData = new FormData();
    formData.append('template_name', name);
    cols.forEach(function(c) { formData.append('columns[]', c); });

    var result = await ajaxPost('/bestdealcrm/admin/leads/template/store', formData);
    if (result.success) {
        showToast(result.message, 'success');
        // Auto-download the CSV
        window.location.href = '/bestdealcrm/admin/leads/template/' + result.id;
    } else {
        showToast(result.error || 'Failed to create template.', 'danger');
    }
}

async function loadTemplates() {
    var result = await ajaxGet('/bestdealcrm/admin/leads/templates');
    var container = document.getElementById('templatesList');
    if (!result.success || !result.templates || result.templates.length === 0) {
        container.innerHTML = '<p class="text-muted">No templates found. Create one in the first tab.</p>';
        return;
    }
    var html = '<table class="table table-sm table-hover"><thead class="table-light"><tr><th>Name</th><th>Columns</th><th>Created By</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
    result.templates.forEach(function(t) {
        var cols = JSON.parse(t.columns || '[]');
        html += '<tr>';
        html += '<td><strong>' + escapeHtml(t.name) + '</strong></td>';
        html += '<td><small class="text-muted">' + cols.length + ' columns</small></td>';
        html += '<td><small>' + escapeHtml(t.created_by_name || '') + '</small></td>';
        html += '<td><small class="text-muted">' + escapeHtml(t.created_at || '') + '</small></td>';
        html += '<td><a href="/bestdealcrm/admin/leads/template/' + t.id + '" class="btn btn-outline-primary btn-sm"><i class="bi bi-download"></i> Download</a></td>';
        html += '</tr>';
    });
    html += '</tbody></table>';
    container.innerHTML = html;
}

// Load templates when tab is shown
document.querySelector('[data-bs-target="#tab-templates"]').addEventListener('shown.bs.tab', loadTemplates);

function autoMapColumns(csvCols) {
    var mapping = {};
    csvCols.forEach(function(col) {
        var lower = col.toLowerCase().replace(/[^a-z0-9]/g, '');
        for (var dbField in DB_FIELDS) {
            var dbLower = DB_FIELDS[dbField].toLowerCase().replace(/[^a-z0-9]/g, '');
            if (lower === dbLower || lower.includes(dbLower) || dbLower.includes(lower)) {
                mapping[col] = dbField;
                break;
            }
        }
    });
    return mapping;
}

async function uploadFile() {
    var form = document.getElementById('uploadForm');
    var formData = new FormData(form);

    document.getElementById('uploadProgress').classList.remove('d-none');

    try {
        var response = await fetch('/bestdealcrm/admin/leads/upload/process', {
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
                var autoValue = autoMap[col] || '';
                html += '<div class="row align-items-center mb-2">';
                html += '<div class="col-md-5"><span class="fw-semibold small">' + escapeHtml(col) + '</span> <span class="text-muted small">(CSV)</span></div>';
                html += '<div class="col-md-1 text-center"><i class="bi bi-arrow-right text-muted"></i></div>';
                html += '<div class="col-md-6"><select name="mapping[' + escapeHtml(col) + ']" class="form-select form-select-sm"><option value="">-- Skip --</option>';
                for (var dbField in DB_FIELDS) {
                    var sel = autoValue === dbField ? 'selected' : '';
                    html += '<option value="' + dbField + '" ' + sel + '>' + DB_FIELDS[dbField] + '</option>';
                }
                html += '</select></div></div>';
            });
            document.getElementById('mappingFields').innerHTML = html;

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
        var response = await fetch('/bestdealcrm/admin/leads/upload/mapping', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        var text = await response.text();
        var result;
        try { result = JSON.parse(text); } catch(e) {
            showToast('Server error during import.', 'danger');
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
            html += '<a href="/bestdealcrm/admin/leads" class="btn btn-primary mt-2">View Leads</a>';
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
    document.getElementById('uploadForm').reset();
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
