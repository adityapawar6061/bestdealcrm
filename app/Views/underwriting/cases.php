<div class="page-header">
    <h4><i class="bi bi-folder2-open me-2"></i>Underwriting Cases</h4>
</div>

<div class="table-container">
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <input type="text" class="form-control form-control-sm" style="max-width:250px" placeholder="Search..." 
               value="<?= htmlspecialchars($filters['search'] ?? '') ?>" onkeyup="debounceSearch(this.value)">
        <select class="form-select form-select-sm" style="max-width:200px" onchange="filterStage(this.value)">
            <option value="">All Stages</option>
            <option value="UNDERWRITING" <?= ($filters['workflow_stage'] ?? '') === 'UNDERWRITING' ? 'selected' : '' ?>>Underwriting</option>
            <option value="UNDERWRITING_APPROVED" <?= ($filters['workflow_stage'] ?? '') === 'UNDERWRITING_APPROVED' ? 'selected' : '' ?>>Approved</option>
            <option value="UNDERWRITING_REJECTED" <?= ($filters['workflow_stage'] ?? '') === 'UNDERWRITING_REJECTED' ? 'selected' : '' ?>>Rejected</option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Customer</th><th>Mobile</th><th>Bank</th><th>Stage</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($leads['data'])): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No cases found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr>
                        <td><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></td>
                        <td><small><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></small></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td>
                            <?php if ($lead['workflow_stage'] === 'UNDERWRITING'): ?>
                                <a href="/bestdealcrm/underwriting/cases/<?= $lead['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square me-1"></i>Review</a>
                            <?php else: ?>
                                <a href="/bestdealcrm/underwriting/cases/<?= $lead['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a>
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
        <small class="text-muted">Showing <?= $leads['from'] ?? 0 ?>-<?= $leads['to'] ?? 0 ?> of <?= number_format($leads['total']) ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $leads['total_pages']; $i++): ?>
                <li class="page-item <?= $i == $leads['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($filters['search'] ?? '') ?>&workflow_stage=<?= urlencode($filters['workflow_stage'] ?? '') ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>
<script>
const searchInput = document.querySelector('input[placeholder="Search..."]');
let timer;
function debounceSearch(val) { clearTimeout(timer); timer = setTimeout(() => { window.location.href = '?search=' + encodeURIComponent(val) + '&workflow_stage=<?= urlencode($filters["workflow_stage"] ?? "") ?>'; }, 500); }
function filterStage(val) { window.location.href = '?search=<?= urlencode($filters["search"] ?? "") ?>&workflow_stage=' + val; }
</script>
