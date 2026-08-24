<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-ui-checks-grid me-2"></i>Create Form</h4>
    <a href="/bestdealcrm/admin/form-builder" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="table-container" style="max-width:700px">
    <form id="createForm">
        <?= csrfField() ?>
        <div class="mb-3">
            <label class="form-label fw-semibold">Form Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g., Agent Lead Form">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Form Code *</label>
            <input type="text" name="code" class="form-control" required placeholder="e.g., agent_lead_form" pattern="[a-zA-Z0-9_]+">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Assigned Role</label>
                <select name="assigned_role" class="form-select">
                    <option value="">Any</option>
                    <option value="agent">Agent</option>
                    <option value="login_agent">Login Agent</option>
                    <option value="underwriting">Underwriting</option>
                    <option value="dispatch">Dispatch</option>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Workflow Stage</label>
                <select name="workflow_stage" class="form-select">
                    <option value="">None</option>
                    <?php foreach (['AGENT_DRAFT','LOGIN_AGENT_DRAFT','POST_LOGIN','UNDERWRITING','DISPATCH'] as $stage): ?>
                        <option value="<?= $stage ?>"><?= humanStatus($stage) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="createForm()">
            <i class="bi bi-check-lg me-1"></i> Create Form
        </button>
    </form>
</div>

<script>
async function createForm() {
    const form = document.getElementById('createForm');
    const formData = new FormData(form);
    const result = await ajaxPost('/bestdealcrm/admin/form-builder/store', formData);
    if (result.success) {
        window.location.href = '/bestdealcrm/admin/form-builder/' + result.id + '/edit';
    } else if (result.errors) {
        alert(Object.values(result.errors).join('\n'));
    }
}
</script>
