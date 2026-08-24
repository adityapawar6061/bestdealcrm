<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-cloud-upload me-2"></i>Upload Leads</h4>
    <a href="/bestdealcrm/admin/leads" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<!-- Step 1: File Upload -->
<div id="step1" class="table-container">
    <h6 class="fw-bold mb-3">Step 1: Select File</h6>
    <p class="text-muted small">Supported formats: CSV, XLSX</p>
    
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
    <p class="text-muted small">Map CSV columns to lead database fields. Unmapped columns will be ignored.</p>
    
    <form id="mappingForm">
        <?= csrfField() ?>
        <input type="hidden" name="upload_id" id="uploadId">
        
        <div class="row g-2 mb-3" id="mappingFields">
            <!-- Mapping fields will be populated by JS -->
        </div>
        
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
let uploadColumns = [];

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
            uploadColumns = result.columns;
            document.getElementById('uploadId').value = result.upload_id;
            
            // Build mapping fields
            const dbFields = [
                'customer_name', 'mobile_number', 'location', 'state', 'existing_la',
                'salary', 'actual_salary', 'dtmf_input', 'response_date', 'data_type',
                'bank_name', 'current_status', 'update_status', 'remark', 'pan_number'
            ];
            
            let html = '';
            dbFields.forEach(field => {
                html += `<div class="col-md-4">
                    <label class="form-label small text-muted">${field.replace(/_/g, ' ').toUpperCase()}</label>
                    <select name="mapping[${field}]" class="form-select form-select-sm">
                        <option value="">-- Skip --</option>`;
                result.columns.forEach(col => {
                    html += `<option value="${col}">${col}</option>`;
                });
                html += `</select></div>`;
            });
            document.getElementById('mappingFields').innerHTML = html;
            
            // Preview table
            let headHtml = '<tr>';
            result.columns.forEach(col => {
                headHtml += `<th class="small">${col}</th>`;
            });
            headHtml += '</tr>';
            document.getElementById('previewHead').innerHTML = headHtml;
            
            let bodyHtml = '';
            result.sample.forEach(row => {
                bodyHtml += '<tr>';
                result.columns.forEach(col => {
                    bodyHtml += `<td class="small">${row[col] || ''}</td>`;
                });
                bodyHtml += '</tr>';
            });
            document.getElementById('previewBody').innerHTML = bodyHtml;
            
            document.getElementById('step1').classList.add('d-none');
            document.getElementById('step2').classList.remove('d-none');
        } else {
            alert(result.error || 'Upload failed.');
        }
    } catch (err) {
        document.getElementById('uploadProgress').classList.add('d-none');
        alert('Network error. Please try again.');
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
            document.getElementById('importResults').innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?= result.message ?>
                </div>
                ${result.errors.length ? '<div class="mt-2"><small class="text-muted">Skipped rows:</small><ul class="small">' + result.errors.map(e => '<li>' + e + '</li>').join('') + '</ul></div>' : ''}
                <a href="/bestdealcrm/admin/leads" class="btn btn-primary mt-2">View Leads</a>
            `;
        } else {
            document.getElementById('importResults').innerHTML = `
                <div class="alert alert-danger">${result.error || 'Import failed.'}</div>
            `;
        }
    } catch (err) {
        alert('Network error. Please try again.');
    }
}

function resetUpload() {
    document.getElementById('step1').classList.remove('d-none');
    document.getElementById('step2').classList.add('d-none');
    document.getElementById('uploadForm').reset();
}
</script>
