<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-shield-lock me-2"></i>Permissions: <?= htmlspecialchars($role['display_name']) ?></h4>
    </div>
    <a href="/bestdealcrm/admin/roles" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="table-container" style="max-width:700px">
    <form id="permissionsForm">
        <?= csrfField() ?>
        <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
        
        <div class="mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll()">Select All</button>
        </div>

        <?php 
        $grouped = [];
        foreach ($allPermissions as $perm) {
            $parts = explode('.', $perm['name']);
            $group = $parts[0] ?? 'other';
            $grouped[$group][] = $perm;
        }
        foreach ($grouped as $group => $perms):
        ?>
        <div class="mb-3">
            <h6 class="text-muted small fw-bold text-uppercase"><?= ucfirst($group) ?></h6>
            <?php foreach ($perms as $perm): ?>
            <div class="form-check form-check-inline mb-1">
                <input class="form-check-input perm-check" type="checkbox" 
                       name="permissions[]" value="<?= $perm['id'] ?>" 
                       id="perm_<?= $perm['id'] ?>"
                       <?= in_array($perm['id'], $currentPermIds) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="perm_<?= $perm['id'] ?>">
                    <?= htmlspecialchars($perm['name']) ?>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <button type="button" class="btn btn-primary" onclick="savePermissions()">
            <i class="bi bi-check-lg me-1"></i> Save Permissions
        </button>
    </form>
</div>

<script>
function toggleAll() {
    const checks = document.querySelectorAll('.perm-check');
    const allChecked = [...checks].every(c => c.checked);
    checks.forEach(c => c.checked = !allChecked);
}

async function savePermissions() {
    const form = document.getElementById('permissionsForm');
    const result = await ajaxPost('/bestdealcrm/admin/roles/permissions/save', new FormData(form));
    if (result.success) alert(result.message);
}
</script>
