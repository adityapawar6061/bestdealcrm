<div class="page-header">
    <h4><i class="bi bi-shield-lock me-2"></i>Roles & Permissions</h4>
</div>

<div class="row g-4">
    <?php foreach ($roles as $role): ?>
    <div class="col-md-4">
        <div class="table-container">
            <h6 class="fw-bold mb-2">
                <span class="badge bg-primary me-1"><?= strtoupper(substr($role['name'], 0, 2)) ?></span>
                <?= htmlspecialchars($role['display_name']) ?>
            </h6>
            <p class="text-muted small mb-3"><?= htmlspecialchars($role['description'] ?? '') ?></p>
            
            <?php
            $rolePerms = [];
            $allPerms = $this->db->fetchAll(
                "SELECT p.name FROM permissions p 
                 JOIN role_permissions rp ON p.id = rp.permission_id 
                 WHERE rp.role_id = ?",
                [$role['id']]
            );
            $rolePerms = array_column($allPerms, 'name');
            ?>
            
            <div class="mb-3">
                <small class="text-muted">Assigned Permissions: <strong><?= count($rolePerms) ?></strong> / <?= count($permissions) ?></small>
            </div>
            
            <a href="/bestdealcrm/admin/roles/<?= $role['id'] ?>/permissions" class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-pencil me-1"></i> Manage Permissions
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
