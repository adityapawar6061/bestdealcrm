<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Eligibility Checker') ?> - BestDeal CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f7fa; }
        .hero { background: linear-gradient(135deg, #003366, #0066a1); color: #fff; padding: 25px 0; }
        .card { border: 0; box-shadow: 0 4px 16px rgba(0,51,102,.1); border-radius: 12px; }
        label { font-size: 12px; font-weight: 600; color: #38566b; }
        .title { color: #003366; font-weight: 700; border-bottom: 2px solid #eaf4fb; padding-bottom: 10px; }
        .table thead th { background: #eaf4fb; color: #003366; white-space: nowrap; }
        .status { font-size: 11px; }
        .save-bar { background: #fff; border-bottom: 2px solid #e2e8f0; padding: 8px 24px; position: sticky; top: 0; z-index: 100; }
    </style>
</head>
<body>

<!-- Save/Load Bar -->
<div class="save-bar d-flex align-items-center gap-2 flex-wrap shadow-sm">
    <input type="text" id="custName" placeholder="Customer Name" class="form-control form-control-sm" style="width:180px">
    <input type="text" id="loadCode" placeholder="Enter Code" class="form-control form-control-sm" style="width:120px" maxlength="6">
    <button class="btn btn-sm btn-primary" onclick="loadEligibility()"><i class="bi bi-download me-1"></i>Load</button>
    <button class="btn btn-sm btn-success" onclick="saveEligibility()"><i class="bi bi-save me-1"></i>Save</button>
    <button class="btn btn-sm btn-info text-white" onclick="getCode()"><i class="bi bi-key me-1"></i>Get Code</button>
    <button class="btn btn-sm btn-warning" id="updateBtn" onclick="updateEligibility()" style="display:none"><i class="bi bi-arrow-repeat me-1"></i>Update</button>
    <button class="btn btn-sm btn-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <div class="ms-auto">
        <a href="<?= BASE_URL ?>/tools/calculator" class="btn btn-sm btn-outline-primary"><i class="bi bi-calculator me-1"></i>EMI Calculator</a>
    </div>
</div>

<div class="hero mb-4">
    <div class="container">
        <h2><i class="bi bi-bank me-2"></i>Bank Eligibility Checker</h2>
        <p class="mb-0">Compare customer details with all available personal-loan policies.</p>
    </div>
</div>

<main class="container pb-5">
    <!-- Customer Form -->
    <form id="eligForm" class="card p-4">
        <h5 class="title"><i class="bi bi-person-check me-2"></i>Customer & Loan Details</h5>
        <div class="row g-3">
            <?php
            $defs = [
                ['net_salary','Net Monthly Salary (₹)','text'],
                ['requested_loan','Requested Loan (₹)','text'],
                ['existing_emi','Existing Monthly EMI (₹)','text'],
                ['proposed_emi','Proposed Loan EMI (₹)','text'],
                ['age','Age','number'],
                ['cibil_score','CIBIL Score','number'],
                ['employment_months','Current Employment (months)','number'],
                ['experience_months','Total Experience (months)','number'],
                ['enq1','1M Enquiries','number'],
                ['enq3','3M Enquiries','number'],
                ['live_pl','Live PL Count','number'],
            ];
            foreach ($defs as $d):
            ?>
            <div class="col-md-4">
                <label><?= htmlspecialchars($d[1]) ?></label>
                <input class="form-control" name="<?= $d[0] ?>" id="field_<?= $d[0] ?>" type="<?= $d[2] ?>" placeholder="<?= htmlspecialchars($d[1]) ?>">
            </div>
            <?php endforeach; ?>
            <div class="col-md-3">
                <label>Employer Profile</label>
                <select class="form-select" name="employer_profile" id="field_employer_profile">
                    <option value="listed">Listed</option>
                    <option value="unlisted">Unlisted</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>CIBIL Type</label>
                <select class="form-select" name="cibil_type" id="field_cibil_type">
                    <option value="normal">Normal</option>
                    <option value="zero">0</option>
                    <option value="minus1">-1</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Payslip</label>
                <select class="form-select" name="payslip" id="field_payslip">
                    <option value="available">Available</option>
                    <option value="not_available">Not available</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>PG Accommodation</label>
                <select class="form-select" name="pg" id="field_pg">
                    <option value="no">No</option>
                    <option value="yes">Yes</option>
                </select>
            </div>
            <div class="col-md-6">
                <label>Current Address Proof</label>
                <select class="form-select" name="address" id="field_address">
                    <option value="available">Available</option>
                    <option value="not_available">Not available</option>
                </select>
            </div>
        </div>
        <button type="button" class="btn btn-primary mt-4 w-100" onclick="checkEligibility()"><i class="bi bi-search me-2"></i>Check Eligibility</button>
    </form>

    <!-- Results -->
    <div class="card p-4 mt-4">
        <div class="d-flex justify-content-between">
            <h5 class="title mb-0">Bank Results</h5>
            <span class="badge bg-success align-self-start" id="eligibleCount">0 eligible</span>
        </div>
        <div class="alert alert-info mt-3 small">Pre-screen only. Final approval is subject to the latest bank policy and credit approval.</div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered" id="bankEligibilityTable">
                <thead><tr><th>Rank</th><th>Bank</th><th>Status</th><th>Reason / Action</th><th>Loan Range</th><th>ROI</th><th>Tenure</th><th>FOIR</th></tr></thead>
                <tbody>
                    <tr><td colspan="8" class="text-center text-muted">Enter details and click Check Eligibility.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= defined('BASE_URL') ? BASE_URL : '/bestdealcrm' ?>';
const CSRF_TOKEN = '<?= csrfToken() ?>';
const BANK_POLICIES = <?= json_encode($bankPolicies, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function getVal(id) {
    const el = document.getElementById(id);
    if (!el) return '';
    return el.value;
}

function checkEligibility() {
    const fields = ['net_salary','requested_loan','existing_emi','proposed_emi','age','cibil_score','employment_months','experience_months','enq1','enq3','live_pl'];
    const input = {};
    fields.forEach(f => input[f] = getVal('field_'+f));
    input.employer_profile = getVal('field_employer_profile');
    input.cibil_type = getVal('field_cibil_type');
    input.payslip = getVal('field_payslip');
    input.pg = getVal('field_pg');
    input.address = getVal('field_address');

    const num = k => parseFloat((input[k]||'0').replace(/,/g,'')) || 0;
    const salary = num('net_salary'), loan = num('requested_loan');
    const profile = input.employer_profile, type = input.cibil_type;
    const age = num('age'), score = num('cibil_score');
    const employment = num('employment_months'), experience = num('experience_months');
    const enq1 = num('enq1'), enq3 = num('enq3'), live = num('live_pl');
    const payslip = input.payslip === 'available', pg = input.pg === 'yes', address = input.address === 'available';
    const existing_emi = num('existing_emi'), proposed_emi = num('proposed_emi');
    const foir = salary > 0 ? (existing_emi + proposed_emi) / salary : 0;

    let eligible = 0;
    const results = BANK_POLICIES.map(b => {
        const bad = [], review = [];
        const minSalary = profile === 'listed' ? b.salaryListed : b.salaryUnlisted;
        const minCibil = profile === 'listed' ? b.cibilListed : b.cibilUnlisted;

        if (!loan || loan < b.min || loan > b.max) bad.push(loan > b.max ? 'Above bank maximum' : 'Below bank minimum');
        if (!salary || salary < minSalary) bad.push('Salary below policy minimum');
        if (type !== 'normal' && !b.zeroOne) bad.push('CIBIL 0/-1 not allowed');
        if (type === 'normal' && (!score || score < minCibil)) bad.push('CIBIL score below minimum');
        if (!age || age < b.ageMin || age > b.ageMax) bad.push('Age outside range');
        if (b.profile === 'listed' && profile !== 'listed') bad.push('Unlisted employer');
        if (b.employment && employment < b.employment) bad.push('Employment below minimum');
        if (b.experience && experience < b.experience) bad.push('Experience below minimum');
        if (foir > b.foir) bad.push('FOIR above limit');
        if (b.enq1 !== null && enq1 > b.enq1) bad.push('1M enquiries above limit');
        if (b.enq3 !== null && enq3 > b.enq3) bad.push('3M enquiries above limit');
        if (b.livePl !== null && live > b.livePl) bad.push('Live PL above limit');
        if (b.payslip === 'required' && !payslip) bad.push('Payslip required');
        if (b.pg === 'not_allowed' && pg) bad.push('PG not accepted');
        if (b.pg === 'review' && pg) review.push('PA/CPV may be needed');
        if (b.address === 'required' && !address) bad.push('Address proof required');

        const status = bad.length ? 'NOT ELIGIBLE' : (review.length ? 'REVIEW REQUIRED' : 'ELIGIBLE');
        if (status === 'ELIGIBLE') eligible++;
        return { ...b, status, reason: [...bad, ...review].join('; ') || 'All checks passed' };
    }).sort((a, b) => ({ ELIGIBLE: 1, 'REVIEW REQUIRED': 2, 'NOT ELIGIBLE': 3 })[a.status] - ({ ELIGIBLE: 1, 'REVIEW REQUIRED': 2, 'NOT ELIGIBLE': 3 })[b.status]);

    const body = document.querySelector('#bankEligibilityTable tbody');
    body.innerHTML = results.map((b, i) =>
        `<tr><td>${i+1}</td><td><strong>${b.name}</strong></td><td><span class="badge status ${b.status==='ELIGIBLE'?'bg-success':b.status==='REVIEW REQUIRED'?'bg-warning text-dark':'bg-danger'}">${b.status}</span></td><td>${b.reason}</td><td>₹${b.min.toLocaleString('en-IN')} - ₹${b.max.toLocaleString('en-IN')}</td><td>${b.roi}</td><td>${b.tenure}</td><td>${Math.round(b.foir*100)}%</td></tr>`
    ).join('');
    document.getElementById('eligibleCount').textContent = eligible + ' eligible';
}

function getAllData() {
    const fields = ['net_salary','requested_loan','existing_emi','proposed_emi','age','cibil_score','employment_months','experience_months','enq1','enq3','live_pl'];
    const data = { customer_name: getVal('custName') };
    fields.forEach(f => data[f] = getVal('field_'+f));
    data.employer_profile = getVal('field_employer_profile');
    data.cibil_type = getVal('field_cibil_type');
    data.payslip = getVal('field_payslip');
    data.pg = getVal('field_pg');
    data.address = getVal('field_address');
    return data;
}

function setAllData(data) {
    document.getElementById('custName').value = data.customer_name || '';
    ['net_salary','requested_loan','existing_emi','proposed_emi','age','cibil_score','employment_months','experience_months','enq1','enq3','live_pl'].forEach(f => {
        const el = document.getElementById('field_'+f);
        if (el) el.value = data[f] || '';
    });
    if (data.employer_profile) document.getElementById('field_employer_profile').value = data.employer_profile;
    if (data.cibil_type) document.getElementById('field_cibil_type').value = data.cibil_type;
    if (data.payslip) document.getElementById('field_payslip').value = data.payslip;
    if (data.pg) document.getElementById('field_pg').value = data.pg;
    if (data.address) document.getElementById('field_address').value = data.address;
    checkEligibility();
}

async function saveEligibility() {
    const name = document.getElementById('custName').value.trim();
    if (!name) { showToast('Please enter customer name', 'warning'); return null; }
    try {
        const fd = new FormData();
        fd.append('data', JSON.stringify(getAllData()));
        fd.append('save_type', 'eligibility');
        fd.append('_csrf_token', CSRF_TOKEN);
        const resp = await fetch(BASE_URL+'/tools/api/save', { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
        const result = await resp.json();
        if (result.success) {
            currentCode = result.code;
            document.getElementById('loadCode').value = result.code;
            document.getElementById('updateBtn').style.display = 'inline-block';
            showToast('Saved! Code: ' + result.code, 'success');
            return result.code;
        } else { showToast(result.message || 'Save failed', 'danger'); return null; }
    } catch(e) { showToast('Save error: ' + e.message, 'danger'); return null; }
}

async function getCode() {
    const code = await saveEligibility();
    if (code) showToast('Code: ' + code + '\nUse this to load on any device.', 'success');
}

async function loadEligibility() {
    const code = document.getElementById('loadCode').value.trim().toUpperCase();
    if (!code) { showToast('Enter a code', 'warning'); return; }
    try {
        const resp = await fetch(BASE_URL+'/tools/api/load?code='+code, { headers:{'X-Requested-With':'XMLHttpRequest'} });
        const result = await resp.json();
        if (result.success) {
            const data = JSON.parse(result.data);
            setAllData(data);
            currentCode = code;
            document.getElementById('updateBtn').style.display = 'inline-block';
            showToast('Loaded!', 'success');
        } else { showToast(result.message || 'Load failed', 'danger'); }
    } catch(e) { showToast('Load error: ' + e.message, 'danger'); }
}

let currentCode = null;
async function updateEligibility() {
    if (!currentCode) { showToast('No code loaded', 'warning'); return; }
    try {
        const fd = new FormData();
        fd.append('code', currentCode);
        fd.append('data', JSON.stringify(getAllData()));
        fd.append('_csrf_token', CSRF_TOKEN);
        const resp = await fetch(BASE_URL+'/tools/api/update', { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
        const result = await resp.json();
        showToast(result.success ? 'Updated!' : (result.message || 'Failed'), result.success ? 'success' : 'danger');
    } catch(e) { showToast('Update error: ' + e.message, 'danger'); }
}

function showToast(msg, type) {
    var c = document.getElementById('toast-container');
    if (!c) { c = document.createElement('div'); c.id = 'toast-container'; c.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;max-width:400px;'; document.body.appendChild(c); }
    var colors = { success:'#22c55e', danger:'#ef4444', warning:'#f59e0b', info:'#3b82f6' };
    var t = document.createElement('div');
    t.style.cssText = 'background:'+(colors[type]||colors.info)+';color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);font-size:14px;display:flex;justify-content:space-between;align-items:center;';
    t.innerHTML = '<span>'+msg+'</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:#fff;font-size:18px;cursor:pointer;margin-left:10px">&times;</button>';
    c.appendChild(t); setTimeout(() => t.remove(), 5000);
}
</script>
</body>
</html>
