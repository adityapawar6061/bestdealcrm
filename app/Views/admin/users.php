<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-people me-2"></i>Manage Users</h4>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-plus me-1"></i> Create User
    </button>
</div>

<!-- Filters -->
<div class="table-container mb-3">
    <form class="row g-2 align-items-end" method="GET">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search users..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="role_id" class="form-select form-select-sm">
                <option value="">All Roles</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= ($filters['role_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['display_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Status</option>
                <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
        </div>
    </form>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($users['data'])): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users['data'] as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><small><?= htmlspecialchars($u['email']) ?></small></td>
                        <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $u['role_name'] ?? '')) ?></span></td>
                        <td><span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($u['status']) ?></span></td>
                        <td><small class="text-muted"><?= $u['last_login_at'] ? formatDate($u['last_login_at']) : 'Never' ?></small></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="/bestdealcrm/admin/users/<?= $u['id'] ?>" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                <button class="btn btn-outline-warning" title="Edit" onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name']) ?>', '<?= htmlspecialchars($u['email']) ?>', '<?= $u['role_id'] ?>', '<?= $u['team_leader_id'] ?? '' ?>')"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-outline-<?= $u['status'] === 'active' ? 'danger' : 'success' ?>" title="<?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>" onclick="toggleStatus(<?= $u['id'] ?>)"><i class="bi bi-<?= $u['status'] === 'active' ? 'person-x' : 'person-check' ?>"></i></button>
                                <?php $cu = currentUser(); if ($u['id'] != 1 && ($cu['id'] ?? 0) != $u['id']): ?>
                                <button class="btn btn-outline-danger" title="Delete User" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($users['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= $users['from'] ?? 0 ?>-<?= $users['to'] ?? 0 ?> of <?= number_format($users['total']) ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $users['total_pages']; $i++): ?>
                <li class="page-item <?= $i == $users['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&role_id=<?= urlencode($filters['role_id'] ?? '') ?>&status=<?= urlencode($filters['status'] ?? '') ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Create User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="createUserForm">
                    <?= csrfField() ?>
                    <div class="mb-3"><label class="form-label small fw-semibold">Name *</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Username *</label><input type="text" name="username" class="form-control" required minlength="4"></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Password *</label><input type="password" name="password" class="form-control" required minlength="6"></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Mobile</label><input type="text" name="mobile" class="form-control"></div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role *</label>
                        <select name="role_id" class="form-select" required>
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Team Leader</label>
                        <select name="team_leader_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($teamLeaders as $tl): ?>
                                <option value="<?= $tl['id'] ?>"><?= htmlspecialchars($tl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="createUser()">Create User</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form id="editUserForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mb-3"><label class="form-label small fw-semibold">Name *</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Mobile</label><input type="text" name="mobile" id="edit_mobile" class="form-control"></div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role *</label>
                        <select name="role_id" id="edit_role_id" class="form-select" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Team Leader</label>
                        <select name="team_leader_id" id="edit_team_leader_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($teamLeaders as $tl): ?>
                                <option value="<?= $tl['id'] ?>"><?= htmlspecialchars($tl['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateUser()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
async function createUser() {
    const form = document.getElementById('createUserForm');
    const formData = new FormData(form);
    const result = await ajaxPost('/bestdealcrm/admin/users/create', formData);
    console.log('Create user result:', result);
    if (result && result.success) {
        showToast(result.message || 'User created!', 'success');
        setTimeout(function() { location.reload(); }, 1000);
    } else if (result && result.errors) {
        showToast(Object.values(result.errors).join('\n'), 'danger');
    } else {
        showToast(result.error || 'Error creating user.', 'danger');
    }
}

function editUser(id, name, email, roleId, teamLeaderId) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role_id').value = roleId;
    document.getElementById('edit_team_leader_id').value = teamLeaderId || '';
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

async function updateUser() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    const result = await ajaxPost('/bestdealcrm/admin/users/update', formData);
    if (result && result.success) { showToast(result.message || 'Updated!', 'success'); setTimeout(function() { location.reload(); }, 1000); }
    else if (result && result.errors) { showToast(Object.values(result.errors).join('\n'), 'danger'); }
    else { showToast(result.error || 'Error updating user.', 'danger'); }
}

async function toggleStatus(userId) {
    const fd = new FormData(); fd.append('user_id', userId);
    const result = await ajaxPost('/bestdealcrm/admin/users/toggle-status', fd);
    if (result && result.success) { showToast('Status updated.', 'success'); setTimeout(function() { location.reload(); }, 500); }
    else showToast(result.error || 'Error.', 'danger');
}

async function deleteUser(userId, userName) {
    if (!confirm('Delete user "' + userName + '"?\n\nTheir name will be changed to EX_EMPLOYEE and they will be deactivated.')) return;
    const fd = new FormData();
    fd.append('user_id', userId);
    const result = await ajaxPost('/bestdealcrm/admin/users/delete', fd);
    if (result && result.success) {
        showToast(result.message, 'success');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        showToast(result.error || 'Error deleting user.', 'danger');
    }
}
</script>
