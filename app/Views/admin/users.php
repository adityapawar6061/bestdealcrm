<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-people me-2"></i>Manage Users</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-plus-lg me-1"></i> Add User
    </button>
</div>

<!-- Filters -->
<div class="table-container mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, email, username..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <select name="role_id" class="form-select form-select-sm">
                <option value="">All Roles</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>" <?= ($filters['role_id'] ?? '') == $role['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role['display_name']) ?>
                    </option>
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
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i> Filter</button>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users['data'])): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users['data'] as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['mobile'] ?? '-') ?></td>
                        <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $u['role_name'] ?? '')) ?></span></td>
                        <td>
                            <span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'danger' ?>">
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?= $u['last_login_at'] ? formatDate($u['last_login_at'], 'd M Y, h:i A') : 'Never' ?></small></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>', '<?= $u['mobile'] ?? '' ?>', <?= $u['role_id'] ?>, '<?= $u['team_leader_id'] ?? '' ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-<?= $u['status'] === 'active' ? 'warning' : 'success' ?>" onclick="toggleStatus(<?= $u['id'] ?>)">
                                    <i class="bi bi-<?= $u['status'] === 'active' ? 'pause' : 'play' ?>"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="resetPassword(<?= $u['id'] ?>)">
                                    <i class="bi bi-key"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($users['total_pages'] > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center mb-0">
            <?php for ($i = 1; $i <= $users['total_pages']; $i++): ?>
                <li class="page-item <?= $i == $users['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&role_id=<?= $filters['role_id'] ?? '' ?>&status=<?= $filters['status'] ?? '' ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createUserForm" onsubmit="return createUser(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile</label>
                            <input type="text" name="mobile" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" required minlength="4">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role *</label>
                            <select name="role_id" class="form-select" required>
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Team Leader</label>
                            <select name="team_leader_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($teamLeaders as $tl): ?>
                                    <option value="<?= $tl['id'] ?>"><?= htmlspecialchars($tl['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function createUser(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    const result = await ajaxPost('/bestdealcrm/admin/users/create', data);
    if (result.success) {
        location.reload();
    } else if (result.errors) {
        alert(Object.values(result.errors).join('\n'));
    }
}

async function toggleStatus(userId) {
    if (!confirm('Toggle user status?')) return;
    const result = await ajaxPost('/bestdealcrm/admin/toggle-user-status', { user_id: userId });
    if (result.success) location.reload();
}

async function resetPassword(userId) {
    const pwd = prompt('Enter new password (min 6 characters):');
    if (!pwd || pwd.length < 6) return;
    const result = await ajaxPost('/bestdealcrm/admin/users/reset-password', { user_id: userId, new_password: pwd });
    if (result.success) alert('Password reset successfully.');
}

function editUser(id, name, email, mobile, roleId, tlId) {
    // Simple alert-based edit for now - can be enhanced with modal
    alert('Edit functionality: User #' + id + ' - ' + name);
}
</script>
