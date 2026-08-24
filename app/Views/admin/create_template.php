<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-file-earmark-plus me-2"></i>Create Upload Template</h4>
    <a href="/bestdealcrm/admin/leads/upload" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="table-container">
    <p class="text-muted small mb-3">Create a CSV template with column headers for your lead upload format.</p>

    <div class="mb-3">
        <label class="form-label small fw-semibold">Template Name</label>
        <input type="text" id="templateName" class="form-control" placeholder="e.g., March 2026 Lead Format" required>
    </div>

    <div class="mb-3">
        <label class="form-label small fw-semibold">Column Names</label>
        <div id="columns">
            <div class="input-group mb-2">
                <input type="text" class="form-control col-input" placeholder="Column name" value="Customer Name">
                <button class="btn btn-outline-danger" onclick="this.closest('.input-group').remove()"><i class="bi bi-x"></i></button>
            </div>
        </div>
        <button class="btn btn-outline-primary btn-sm" onclick="addColumn()"><i class="bi bi-plus me-1"></i> Add Column</button>
    </div>

    <button class="btn btn-primary" onclick="saveTpl()"><i class="bi bi-save me-1"></i> Create & Download</button>
</div>

<script>
function addColumn() {
    var html = '<div class="input-group mb-2"><input type="text" class="form-control col-input" placeholder="Column name"><button class="btn btn-outline-danger" onclick="this.closest(\'.input-group\').remove()"><i class="bi bi-x"></i></button></div>';
    document.getElementById('columns').insertAdjacentHTML('beforeend', html);
}

async function saveTpl() {
    var name = document.getElementById('templateName').value.trim();
    var cols = [];
    document.querySelectorAll('.col-input').forEach(function(el) { if (el.value.trim()) cols.push(el.value.trim()); });
    if (!name) { showToast('Enter a name.', 'warning'); return; }
    if (cols.length === 0) { showToast('Add at least one column.', 'warning'); return; }
    var fd = new FormData();
    fd.append('template_name', name);
    cols.forEach(function(c) { fd.append('columns[]', c); });
    var r = await ajaxPost('/bestdealcrm/admin/leads/template/store', fd);
    if (r.success) { showToast(r.message, 'success'); window.location.href = '/bestdealcrm/admin/leads/template/' + r.id; }
    else showToast(r.error || 'Failed.', 'danger');
}
</script>
