<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-table me-2"></i>Create Dynamic Table</h4>
    <a href="/bestdealcrm/admin/table-builder" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="table-container" style="max-width:700px">
    <form id="createTableForm">
        <?= csrfField() ?>
        <div class="mb-3">
            <label class="form-label fw-semibold">Table Name * <small class="text-muted">(alphanumeric + underscore only)</small></label>
            <input type="text" name="name" class="form-control" required pattern="[a-zA-Z0-9_]+" placeholder="e.g., customer_data">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Display Name *</label>
            <input type="text" name="display_name" class="form-control" required placeholder="e.g., Customer Data">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <button type="button" class="btn btn-primary" onclick="createTable()">
            <i class="bi bi-check-lg me-1"></i> Create Table
        </button>
    </form>
</div>

<script>
async function createTable() {
    const form = document.getElementById('createTableForm');
    const result = await ajaxPost('/bestdealcrm/admin/table-builder/store', new FormData(form));
    if (result.success) {
        window.location.href = '/bestdealcrm/admin/table-builder/' + result.id + '/edit';
    } else if (result.errors) {
        alert(Object.values(result.errors).join('\n'));
    } else if (result.error) {
        alert(result.error);
    }
}
</script>
