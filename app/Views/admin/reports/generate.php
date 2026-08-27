<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-download me-2"></i><?= htmlspecialchars($template['name']) ?></h4>
        <small class="text-muted"><?= count($template['columns_config']) ?> columns selected · <?= htmlspecialchars($template['description'] ?? '') ?></small>
    </div>
    <a href="/bestdealcrm/admin/reports" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<!-- Filters -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-funnel me-1"></i> Filters</h6>
    <form id="filterForm" class="row g-3">
        <input type="hidden" name="template_id" value="<?= $template['id'] ?>">

        <div class="col-md-3">
            <label class="form-label small fw-semibold">Date Range From</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Date Range To</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Date Field</label>
            <select name="date_field" class="form-select form-select-sm">
                <option value="created_at" <?= ($_GET['date_field'] ?? '') === 'created_at' ? 'selected' : '' ?>>Created Date</option>
                <option value="updated_at" <?= ($_GET['date_field'] ?? '') === 'updated_at' ? 'selected' : '' ?>>Updated Date</option>
                <option value="response_date" <?= ($_GET['date_field'] ?? '') === 'response_date' ? 'selected' : '' ?>>Response Date</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Name / Mobile / ID" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Assigned Agent</label>
            <select name="agent_id" class="form-select form-select-sm">
                <option value="">All Agents</option>
                <?php foreach ($agents as $ag): ?>
                    <option value="<?= $ag['id'] ?>" <?= (string)$ag['id'] === ($_GET['agent_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($ag['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Bank</label>
            <select name="bank_name" class="form-select form-select-sm">
                <option value="">All Banks</option>
                <?php foreach ($banks as $bank): ?>
                    <option value="<?= htmlspecialchars($bank) ?>" <?= htmlspecialchars($bank) === ($_GET['bank_name'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($bank) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Workflow Stage</label>
            <select name="workflow_stage" class="form-select form-select-sm">
                <option value="">All Stages</option>
                <?php foreach ($stages as $st): ?>
                    <option value="<?= htmlspecialchars($st) ?>" <?= htmlspecialchars($st) === ($_GET['workflow_stage'] ?? '') ? 'selected' : '' ?>><?= humanStatus($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" onclick="previewReport()">
                <i class="bi bi-eye me-1"></i> Preview
            </button>
            <button type="button" class="btn btn-success btn-sm flex-grow-1" onclick="exportReport()">
                <i class="bi bi-download me-1"></i> Download Excel
            </button>
        </div>
    </form>
</div>

<!-- Selected Columns -->
<div class="table-container mb-4">
    <h6 class="fw-bold mb-2"><i class="bi bi-list-columns me-1"></i> Selected Columns</h6>
    <div>
        <?php foreach ($template['columns_config'] as $col): ?>
            <span class="badge bg-primary me-1 mb-1"><?= htmlspecialchars($col['label']) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<!-- Preview Table -->
<div class="table-container" id="previewSection" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-table me-1"></i> Data Preview <small class="text-muted" id="previewCount"></small></h6>
        <button class="btn btn-success btn-sm" onclick="exportReport()">
            <i class="bi bi-download me-1"></i> Download Full Excel
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm table-striped align-middle mb-0" id="previewTable">
            <thead class="table-dark">
                <tr id="previewHeader"></tr>
            </thead>
            <tbody id="previewBody"></tbody>
        </table>
    </div>
</div>

<script>
function getFilterParams() {
    var form = document.getElementById('filterForm');
    var data = new FormData(form);
    var params = {};
    data.forEach(function(val, key) {
        if (val) params[key] = val;
    });
    params.preview = 1;
    params.template_id = <?= $template['id'] ?>;
    return params;
}

function buildQueryString(params) {
    return Object.keys(params).map(function(k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); }).join('&');
}

function previewReport() {
    var params = getFilterParams();
    var url = '/bestdealcrm/admin/reports/<?= $template['id'] ?>/generate?' + buildQueryString(params);
    
    fetch(url)
        .then(function(r) { return r.json(); })
        .then(function(result) {
            if (!result.success) {
                showToast(result.error || 'Error loading preview.', 'danger');
                return;
            }

            var section = document.getElementById('previewSection');
            section.style.display = 'block';

            document.getElementById('previewCount').textContent = '— Showing ' + result.showing + ' of ' + result.total + ' rows';

            // Header
            var header = document.getElementById('previewHeader');
            header.innerHTML = '<th>#</th>';
            result.columns.forEach(function(col) {
                header.innerHTML += '<th>' + escapeHtml(col.label) + '</th>';
            });

            // Body
            var body = document.getElementById('previewBody');
            if (result.rows.length === 0) {
                body.innerHTML = '<tr><td colspan="' + (result.columns.length + 1) + '" class="text-center text-muted py-3">No data found for these filters.</td></tr>';
                return;
            }

            var html = '';
            result.rows.forEach(function(row, idx) {
                html += '<tr><td>' + (idx + 1) + '</td>';
                result.columns.forEach(function(col) {
                    var val = row[col.field] || '—';
                    // Format salary fields
                    if (['salary', 'actual_salary', 'existing_la'].indexOf(col.field) !== -1 && val !== '—' && val !== '') {
                        val = '₹' + Number(val).toLocaleString('en-IN');
                    }
                    html += '<td><small>' + escapeHtml(String(val)) + '</small></td>';
                });
                html += '</tr>';
            });
            body.innerHTML = html;

            // Scroll to preview
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        })
        .catch(function(err) {
            showToast('Error loading preview: ' + err.message, 'danger');
        });
}

function exportReport() {
    var params = getFilterParams();
    delete params.preview;
    var url = '/bestdealcrm/admin/reports/<?= $template['id'] ?>/export?' + buildQueryString(params);
    window.location.href = url;
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

// Auto-preview on page load if filters are set
<?php if (!empty($_GET['date_from']) || !empty($_GET['date_to']) || !empty($_GET['agent_id']) || !empty($_GET['search'])): ?>
document.addEventListener('DOMContentLoaded', function() { previewReport(); });
<?php endif; ?>
</script>
