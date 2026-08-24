<?php
// Disposition options (can be configured by admin later)
$dispositionOptions = [
    'Lead Not Connected',
    'Not Interested',
    'Wrong Number',
    'Call Later',
    'Interested',
    'Follow Up Required',
    'Documents Pending',
    'Eligible',
    'Not Eligible',
    'Third',
    'Converted',
    'Lost',
    'Dropped',
];

// Format salary as "20K" style
function formatSalaryShort($val) {
    if (!$val || $val == 0) return '—';
    $n = (float)$val;
    if ($n >= 10000000) return round($n / 10000000, 1) . 'Cr';
    if ($n >= 100000) return round($n / 100000, 1) . 'L';
    if ($n >= 1000) return round($n / 1000) . 'K';
    return number_format($n);
}

// Format response date
function formatResponseDate($d) {
    if (!$d || $d === '0000-00-00' || $d === '0000-00-00 00:00:00') return '—';
    try { return date('d-M', strtotime($d)); } catch(Exception $e) { return '—'; }
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-list-ul me-2"></i>My Leads</h4>
    <span class="badge bg-primary"><?= number_format($leads['total']) ?> leads</span>
</div>

<!-- Disposition Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="<?= BASE_URL ?>/agent/leads" class="text-decoration-none">
            <div class="stat-card <?= empty($filters['disposition']) ? 'border border-primary' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-primary"><?= number_format($totalAssigned) ?></div>
                        <div class="stat-label">Total Assigned</div>
                    </div>
                    <i class="bi bi-folder2-open text-primary" style="font-size:2rem;opacity:0.3"></i>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="<?= BASE_URL ?>/agent/leads?disposition=" class="text-decoration-none">
            <div class="stat-card <?= (isset($filters['disposition']) && $filters['disposition'] === '') ? 'border border-warning' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-warning"><?= number_format($pendingDisposition) ?></div>
                        <div class="stat-label">Pending Disposition</div>
                    </div>
                    <i class="bi bi-hourglass-split text-warning" style="font-size:2rem;opacity:0.3"></i>
                </div>
            </div>
        </a>
    </div>
    <?php foreach ($dispositionCounts as $dc): ?>
    <div class="col-md-3">
        <a href="<?= BASE_URL ?>/agent/leads?disposition=<?= urlencode($dc['disposition']) ?>" class="text-decoration-none">
            <div class="stat-card <?= (isset($filters['disposition']) && $filters['disposition'] === $dc['disposition']) ? 'border border-success' : '' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number text-success"><?= number_format($dc['cnt']) ?></div>
                        <div class="stat-label"><?= htmlspecialchars($dc['disposition']) ?></div>
                    </div>
                    <i class="bi bi-tag text-success" style="font-size:2rem;opacity:0.3"></i>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="table-container mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="ID, name, mobile..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Stage</label>
            <select name="workflow_stage" class="form-select form-select-sm">
                <option value="">All Stages</option>
                <?php
                $stages = ['LEAD_ASSIGNED','AGENT_DRAFT','AGENT_SUBMITTED','ADMIN_REVIEW_1','RETURNED_TO_AGENT','LOGIN_APPROVED','COMPLETED','REJECTED'];
                foreach ($stages as $stage):
                ?>
                    <option value="<?= $stage ?>" <?= ($filters['workflow_stage'] ?? '') === $stage ? 'selected' : '' ?>>
                        <?= humanStatus($stage) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Disposition</label>
            <select name="disposition" class="form-select form-select-sm">
                <option value="">All Dispositions</option>
                <option value="">— Pending —</option>
                <?php foreach ($dispositionOptions as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($filters['disposition'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i></button>
        </div>
        <?php if (!empty($filters['disposition']) || !empty($filters['workflow_stage']) || !empty($filters['search'])): ?>
        <div class="col-md-1">
            <a href="<?= BASE_URL ?>/agent/leads" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-x"></i> Clear</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <div class="table-responsive" style="overflow-x:auto">
        <table class="table table-hover table-sm align-middle mb-0" style="min-width:1400px">
            <thead class="table-light">
                <tr>
                    <th style="width:40px">#</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Location</th>
                    <th>State</th>
                    <th>Existing LA</th>
                    <th class="text-end">Salary</th>
                    <th class="text-end">Actual Salary</th>
                    <th>Data Type</th>
                    <th>Bank Name</th>
                    <th>Response Date</th>
                    <th>Status</th>
                    <th>Current Disposition</th>
                    <th style="min-width:140px">Disposition</th>
                    <th style="min-width:180px">Agent Remarks</th>
                    <th style="width:100px">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads['data'])): ?>
                    <tr><td colspan="16" class="text-center py-4 text-muted">No leads found.</td></tr>
                <?php else: ?>
                    <?php foreach ($leads['data'] as $lead): ?>
                    <tr id="lead-row-<?= $lead['id'] ?>">
                        <td class="text-muted"><?= $lead['id'] ?></td>
                        <td><strong><?= htmlspecialchars($lead['customer_name'] ?? '—') ?></strong></td>
                        <td><?= htmlspecialchars($lead['mobile_number'] ?? '—') ?></td>
                        <td><small><?= htmlspecialchars($lead['location'] ?? '—') ?></small></td>
                        <td><small><?= htmlspecialchars($lead['state'] ?? '—') ?></small></td>
                        <td><small><?= htmlspecialchars($lead['existing_la'] ?? '—') ?></small></td>
                        <td class="text-end"><small><?= formatSalaryShort($lead['salary']) ?></small></td>
                        <td class="text-end"><small><?= formatSalaryShort($lead['actual_salary']) ?></small></td>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($lead['data_type'] ?? '—') ?></span></td>
                        <td><small><?= htmlspecialchars($lead['bank_name'] ?? '—') ?></small></td>
                        <td><small class="text-muted"><?= formatResponseDate($lead['response_date']) ?></small></td>
                        <td><?= statusBadge($lead['workflow_stage']) ?></td>
                        <td>
                            <?php if (!empty($lead['disposition'])): ?>
                                <span class="badge bg-success"><?= htmlspecialchars($lead['disposition']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <select class="form-select form-select-sm disposition-select" data-lead-id="<?= $lead['id'] ?>" onchange="updateDisposition(<?= $lead['id'] ?>, this.value)">
                                <option value="">Select...</option>
                                <?php foreach ($dispositionOptions as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($lead['disposition'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control remark-input" data-lead-id="<?= $lead['id'] ?>" value="<?= htmlspecialchars($lead['agent_remark'] ?? '') ?>" placeholder="Add remark..." onblur="updateRemark(<?= $lead['id'] ?>, this.value)">
                            </div>
                        </td>
                        <td>
                            <?php if (in_array($lead['workflow_stage'], ['LEAD_ASSIGNED', 'AGENT_DRAFT', 'RETURNED_TO_AGENT'])): ?>
                                <a href="<?= BASE_URL ?>/agent/leads/<?= $lead['id'] ?>/fill-form" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/agent/leads/<?= $lead['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
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
        <small class="text-muted">Showing <?= $leads['from'] ?>–<?= $leads['to'] ?> of <?= number_format($leads['total']) ?></small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($leads['current_page'] > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $leads['current_page'] - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">«</a></li>
                <?php endif; ?>
                <?php
                $startPage = max(1, $leads['current_page'] - 3);
                $endPage = min($leads['total_pages'], $leads['current_page'] + 3);
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <li class="page-item <?= $i == $leads['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($leads['current_page'] < $leads['total_pages']): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $leads['current_page'] + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">»</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
var debounceTimers = {};

function updateDisposition(leadId, value) {
    clearTimeout(debounceTimers['disp_' + leadId]);
    debounceTimers['disp_' + leadId] = setTimeout(function() {
        var formData = new FormData();
        formData.append('lead_id', leadId);
        formData.append('disposition', value);
        ajaxPost(BASE_URL + '/agent/leads/update-disposition', formData).then(function(result) {
            if (result && result.success) {
                showToast('Disposition updated.', 'success');
                // Update the Current Disposition badge
                var row = document.getElementById('lead-row-' + leadId);
                if (row) {
                    var cells = row.querySelectorAll('td');
                    var dispCell = cells[12]; // Current Disposition column
                    if (dispCell) {
                        if (value) {
                            dispCell.innerHTML = '<span class="badge bg-success">' + escapeHtml(value) + '</span>';
                        } else {
                            dispCell.innerHTML = '<span class="badge bg-secondary">Pending</span>';
                        }
                    }
                }
            } else {
                showToast(result.error || 'Failed to update.', 'danger');
            }
        });
    }, 500);
}

function updateRemark(leadId, value) {
    clearTimeout(debounceTimers['remark_' + leadId]);
    debounceTimers['remark_' + leadId] = setTimeout(function() {
        var formData = new FormData();
        formData.append('lead_id', leadId);
        formData.append('agent_remark', value);
        ajaxPost(BASE_URL + '/agent/leads/update-disposition', formData).then(function(result) {
            if (result && result.success) {
                showToast('Remark saved.', 'success');
            }
        });
    }, 800);
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
