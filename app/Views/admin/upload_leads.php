<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-cloud-upload me-2"></i>Upload Leads</h4>
    <a href="/bestdealcrm/admin/leads" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Step 1: File Upload -->
<div id="step1" class="table-container">
    <h6 class="fw-bold mb-3">Step 1: Select File</h6>
    <p class="text-muted small">Supported formats: CSV, XLSX. Maximum 10MB.</p>
    
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
    <h6 class="fw-bold mb-3">Step 2: Map Columns</h6>
    <p class="text-muted small">For each CSV column, select the database field it maps to. Leave as "-- Skip --" to ignore.</p>
    
    <form id="mappingForm">
        <?= csrfField() ?>
        <input type="hidden" name="upload_id" id="uploadId">
        
        <!-- Mapping: one row per CSV column -->
        <div id="mappingFields" class="mb-3"></div>
        
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="submitMapping()">
                <i class="bi bi-check-lg me-1"></i> Import Leads
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetUpload()">Cancel</button>
        </div>
    </form>
    
    <!-- Preview Table -->
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

<script>
// Database field names that leads can be mapped to
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

// Auto-map CSV columns to DB fields by fuzzy matching
function autoMapColumns(csvCols) {
    const mapping = {};
    csvCols.forEach(col => {
        const lower = col.toLowerCase().replace(/[^a-z0-9]/g, '');
        for (const [dbField, dbLabel] of Object.entries(DB_FIELDS)) {
            const dbLower = dbLabel.toLowerCase().replace(/[^a-z0-9]/g, '');
            if (lower === dbLower || lower.includes(dbLower) || dbLower.includes(lower)) {
                mapping[col] = dbField;
                break;
            }
        }
    });
    return mapping;
}

async function uploadFile() {
    const form = document.getElementById('uploadForm');
    const formData = new FormData(form);
    
    document.getElementById('uploadProgress').classList.remove('d-none');
    
    try {
        const response = await fetch('/bestdealcrm/admin/leads/upload/process', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        
        document.getElementById('uploadProgress').classList.add('d-none');
        
        if (result.success) {
            document.getElementById('uploadId').value = result.upload_id;
            
            // Auto-map columns based on name similarity
            const autoMap = autoMapColumns(result.columns);
            
            // Build mapping: one dropdown per CSV column
            let html = '';
            result.columns.forEach(col => {
                const autoValue = autoMap[col] || '';
                html += `<div class="row align-items-center mb-2">
                    <div class="col-md-5">
                        <span class="fw-semibold small">${escapeHtml(col)}</span>
                        <span class="text-muted small">(CSV)</span>
                    </div>
                    <div class="col-md-1 text-center">
                        <i class="bi bi-arrow-right text-muted"></i>
                    </div>
                    <div class="col-md-6">
                        <select name="mapping[${escapeHtml(col)}]" class="form-select form-select-sm">
                            <option value="">-- Skip --</option>`;
                for (const [dbField, dbLabel] of Object.entries(DB_FIELDS)) {
                    const selected = autoValue === dbField ? 'selected' : '';
                    html += `<option value="${dbField}" ${selected}>${dbLabel}</option>`;
                }
                html += `</select></div></div>`;
            });
            document.getElementById('mappingFields').innerHTML = html;
            
            // Preview table
            let headHtml = '<tr>';
            result.columns.forEach(col => {
                headHtml += `<th class="small">${escapeHtml(col)}</th>`;
            });
            headHtml += '</tr>';
            document.getElementById('previewHead').innerHTML = headHtml;
            
            let bodyHtml = '';
            result.sample.forEach(row => {
                bodyHtml += '<tr>';
                result.columns.forEach(col => {
                    bodyHtml += `<td class="small">${escapeHtml(row[col] || '')}</td>`;
                });
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
        showToast('Network error. Please try again.', 'danger');
    }
}

async function submitMapping() {
    const form = document.getElementById('mappingForm');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/bestdealcrm/admin/leads/upload/mapping', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        
        document.getElementById('step2').classList.add('d-none');
        document.getElementById('step3').classList.remove('d-none');
        
        if (result.success) {
            let html = `<div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>${escapeHtml(result.message)}
            </div>`;
            
            if (result.errors && result.errors.length > 0) {
                html += `<div class="mt-2"><small class="text-muted">Skipped rows:</small>
                    <ul class="small" style="max-height:200px;overflow-y:auto">`;
                result.errors.forEach(e => {
                    html += `<li class="text-danger">${escapeHtml(e)}</li>`;
                });
                html += `</ul></div>`;
            }
            
            html += `<a href="/bestdealcrm/admin/leads" class="btn btn-primary mt-2">View Leads</a>`;
            document.getElementById('importResults').innerHTML = html;
        } else {
            document.getElementById('importResults').innerHTML = 
                `<div class="alert alert-danger">${escapeHtml(result.error || 'Import failed.')}</div>`;
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'danger');
    }
}

function resetUpload() {
    document.getElementById('step1').classList.remove('d-none');
    document.getElementById('step2').classList.add('d-none');
    document.getElementById('uploadForm').reset();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
