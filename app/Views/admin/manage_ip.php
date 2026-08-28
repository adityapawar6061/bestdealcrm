<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-shield-lock me-2"></i>Manage IP Restriction</h4>
    <a href="/bestdealcrm/admin/users" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-people me-1"></i>Manage Users
    </a>
</div>

<!-- Current IP Card -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card border-start border-primary border-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label mb-1">Your Current IP</div>
                    <div class="stat-number text-primary" style="font-size:1.2rem" id="currentIpDisplay">
                        <?= htmlspecialchars($currentIp) ?>
                    </div>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="refreshCurrentIp()" title="Refresh IP">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <small class="text-muted">This is the IP that will be checked for restricted users</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card border-start border-success border-4">
            <div class="stat-number text-success"><?= count($whitelistedIps) ?></div>
            <div class="stat-label">Whitelisted IPs</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card border-start border-warning border-4">
            <div class="stat-number text-warning"><?= $restrictedCount ?> / <?= $totalActiveUsers ?></div>
            <div class="stat-label">Users IP-Restricted</div>
        </div>
    </div>
</div>

<!-- Add IP Section -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle me-2"></i>Add IP to Whitelist</h6>
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">IP Address</label>
            <input type="text" id="newIpAddress" class="form-control form-control-sm" 
                   placeholder="<?= htmlspecialchars($currentIp) ?>" 
                   value="<?= htmlspecialchars($currentIp) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Description (optional)</label>
            <input type="text" id="newIpDescription" class="form-control form-control-sm" 
                   placeholder="e.g. Office, Home, VPN">
        </div>
        <div class="col-md-2">
            <button class="btn btn-success btn-sm w-100" onclick="addIp()">
                <i class="bi bi-plus me-1"></i> Add IP
            </button>
        </div>
    </div>
</div>

<!-- Bulk Actions -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-people me-2"></i>Bulk IP Restriction</h6>
    <p class="text-muted small mb-3">Restrict or unrestrict all non-admin users at once. Admin users are always exempt.</p>
    <div class="d-flex gap-2">
        <button class="btn btn-warning btn-sm" onclick="bulkToggle('restrict')">
            <i class="bi bi-lock me-1"></i> Restrict All Users
        </button>
        <button class="btn btn-success btn-sm" onclick="bulkToggle('unrestrict')">
            <i class="bi bi-unlock me-1"></i> Unrestrict All Users
        </button>
    </div>
</div>

<!-- Whitelisted IPs Table -->
<div class="table-container">
    <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2"></i>Whitelisted IP Addresses</h6>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>IP Address</th>
                    <th>Description</th>
                    <th>Added By</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($whitelistedIps)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-shield-x fs-1 d-block mb-2"></i>
                        No IPs whitelisted yet. Add your first IP above.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($whitelistedIps as $ip): ?>
                    <tr id="ip-row-<?= $ip['id'] ?>">
                        <td><?= $ip['id'] ?></td>
                        <td>
                            <code class="fs-6"><?= htmlspecialchars($ip['ip_address']) ?></code>
                            <?php if ($ip['ip_address'] === $currentIp): ?>
                                <span class="badge bg-primary ms-1">Your IP</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($ip['description'] ?: '—') ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($ip['added_by_name'] ?? 'System') ?></small></td>
                        <td><small class="text-muted"><?= formatDate($ip['created_at'], 'd M Y, h:i A') ?></small></td>
                        <td>
                            <button class="btn btn-outline-danger btn-sm" onclick="removeIp(<?= $ip['id'] ?>, '<?= htmlspecialchars($ip['ip_address']) ?>')" title="Remove">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- How It Works -->
<div class="card mt-4 border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i>How IP Restriction Works</h6>
        <ul class="small text-muted mb-0">
            <li><strong>Admin users</strong> are always exempt — they can log in from any IP.</li>
            <li><strong>Non-admin users</strong> with IP restriction enabled can only log in from whitelisted IPs.</li>
            <li>If a restricted user tries to log in from a non-whitelisted IP, they will be blocked.</li>
            <li>You can restrict/unrestrict individual users from the <a href="/bestdealcrm/admin/users">Users</a> page.</li>
            <li>Use <strong>Bulk Actions</strong> to restrict or unrestrict all non-admin users at once.</li>
        </ul>
    </div>
</div>

<script>
async function refreshCurrentIp() {
    var result = await ajaxGet(BASE_URL + '/admin/manage-ip/check');
    if (result && result.success) {
        document.getElementById('currentIpDisplay').textContent = result.ip;
        document.getElementById('newIpAddress').value = result.ip;
        showToast('IP refreshed: ' + result.ip, 'success');
    } else {
        showToast('Could not refresh IP.', 'danger');
    }
}

async function addIp() {
    var ip = document.getElementById('newIpAddress').value.trim();
    var desc = document.getElementById('newIpDescription').value.trim();
    
    if (!ip) {
        showToast('Please enter an IP address.', 'warning');
        return;
    }
    
    var fd = new FormData();
    fd.append('ip_address', ip);
    fd.append('description', desc);
    
    var result = await ajaxPost(BASE_URL + '/admin/manage-ip/add', fd);
    if (result && result.success) {
        showToast(result.message, 'success');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        showToast(result.error || 'Error adding IP.', 'danger');
    }
}

async function removeIp(id, ip) {
    if (!confirm('Remove IP ' + ip + ' from the whitelist?\n\nRestricted users will no longer be able to log in from this IP.')) return;
    
    var fd = new FormData();
    fd.append('ip_id', id);
    
    var result = await ajaxPost(BASE_URL + '/admin/manage-ip/remove', fd);
    if (result && result.success) {
        showToast(result.message, 'success');
        setTimeout(function() { location.reload(); }, 800);
    } else {
        showToast(result.error || 'Error removing IP.', 'danger');
    }
}

async function bulkToggle(action) {
    var label = action === 'restrict' ? 'Restrict' : 'Unrestrict';
    var warning = action === 'restrict' 
        ? 'Restrict ALL non-admin users to whitelisted IPs?\n\nUsers will only be able to log in from IPs in the whitelist above.'
        : 'Remove IP restriction from ALL non-admin users?\n\nAll users will be able to log in from any IP.';
    
    if (!confirm(warning)) return;
    
    var fd = new FormData();
    fd.append('action', action);
    
    var result = await ajaxPost(BASE_URL + '/admin/manage-ip/bulk-toggle', fd);
    if (result && result.success) {
        showToast(result.message, 'success');
        setTimeout(function() { location.reload(); }, 1000);
    } else {
        showToast(result.error || 'Error.', 'danger');
    }
}

// Enter key on IP input
document.getElementById('newIpAddress').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') addIp();
});
</script>
