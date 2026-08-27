<div class="page-header">
    <h4><i class="bi bi-folder2-open me-2"></i>Assigned Cases</h4>
</div>

<div class="table-container mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, mobile..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <select name="workflow_stage" class="form-select form-select-sm">
                <option value="">All Stages</option>
                <?php foreach (['LOGIN_AGENT_ASSIGNED','LOGIN_AGENT_DRAFT','ADMIN_REVIEW_2','LOGIN_APPROVED','POST_LOGIN','RETURNED_TO_AGENT'] as $stage): ?>
                    <option value="<?= $stage ?>" <?= ($filters['workflow_stage'] ?? '') === $stage ? 'selected' : '' ?>><?= humanStatus($stage) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i> Filter</button></div>
    </form>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Agent Form</th>
                    <th>Documents</th>
                    <th>Remarks</th>
                    <th>Stage</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads['data'])): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No cases found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($lead['agent_values'])): ?>
                                <button class="btn btn-sm btn-outline-info" onclick="toggleAgentData('agent-data-<?= $lead['id'] ?>')" title="View Agent Form Data">
                                    <i class="bi bi-eye me-1"></i> View (<?= count($lead['agent_values']) ?>)
                                </button>
                                <!-- Hidden agent data panel -->
                                <div id="agent-data-<?= $lead['id'] ?>" class="mt-2" style="display:none">
                                    <div class="card card-body p-2" style="background:#f8f9fa;max-height:300px;overflow-y:auto">
                                        <table class="table table-sm table-bordered mb-0" style="font-size:0.8rem">
                                            <thead class="table-light">
                                                <tr><th>Field</th><th>Value</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($lead['agent_values'] as $v): ?>
                                                <?php if (empty($v['value'])) continue; ?>
                                                <tr>
                                                    <td class="text-muted"><?= htmlspecialchars($v['label'] ?? $v['field_name']) ?></td>
                                                    <td>
                                                        <?php if ($v['type'] === 'file' && !empty($v['value'])): ?>
                                                            <?php $docPath = '/bestdealcrm/public/uploads/documents/' . $lead['id'] . '/' . htmlspecialchars($v['value']); ?>
                                                            <a href="<?= $docPath ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-file-earmark me-1"></i> View File
                                                            </a>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($v['value']) ?>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">No form data</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($lead['documents'])): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-paperclip me-1"></i> <?= count($lead['documents']) ?> files
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-sm">
                                        <?php foreach ($lead['documents'] as $doc): ?>
                                        <li>
                                            <a class="dropdown-item small" href="/bestdealcrm/public/uploads/documents/<?= $lead['id'] ?>/<?= htmlspecialchars($doc['filename']) ?>" target="_blank">
                                                <i class="bi bi-file-earmark me-1"></i> <?= htmlspecialchars($doc['original_name']) ?>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($lead['remarks'])): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown">
                                        <i class="bi bi-chat-left-text me-1"></i> <?= count($lead['remarks']) ?> remarks
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-sm" style="min-width:300px">
                                        <?php foreach (array_slice($lead['remarks'], 0, 5) as $r): ?>
                                        <li class="px-3 py-2 border-bottom">
                                            <small class="fw-bold text-primary"><?= htmlspecialchars($r['user_name'] ?? 'System') ?></small>
                                            <small class="text-muted d-block"><?= htmlspecialchars($r['stage']) ?> • <?= formatDate($r['created_at']) ?></small>
                                            <small><?= nl2br(htmlspecialchars($r['remark'])) ?></small>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td>
                            <?php if (in_array($lead['workflow_stage'], ['LOGIN_AGENT_ASSIGNED', 'LOGIN_AGENT_DRAFT'])): ?>
                                <a href="/bestdealcrm/login-agent/cases/<?= $lead['id'] ?>/pre-login" class="btn btn-sm btn-primary">
                                    <i class="bi bi-clipboard-check me-1"></i>Pre-Login
                                </a>
                            <?php elseif ($lead['workflow_stage'] === 'LOGIN_APPROVED'): ?>
                                <a href="/bestdealcrm/login-agent/cases/<?= $lead['id'] ?>/post-login" class="btn btn-sm btn-success">
                                    <i class="bi bi-clipboard-data me-1"></i>Post-Login
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($leads['total_pages'] > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= $leads['from'] ?? 0 ?>–<?= $leads['to'] ?? 0 ?> of <?= number_format($leads['total']) ?></small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $leads['total_pages']; $i++): ?>
                    <li class="page-item <?= $i == $leads['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&workflow_stage=<?= urlencode($filters['workflow_stage'] ?? '') ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleAgentData(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
