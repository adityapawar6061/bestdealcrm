<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'EMI Calculator') ?> - BestDeal CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', sans-serif; }
        .calc-header { background: linear-gradient(135deg, #003366 0%, #0066a1 100%); color: white; padding: 16px; border-radius: 12px; }
        .emi-card { background: #fff; border: 2px solid #003366; padding: 16px; margin-bottom: 12px; border-radius: 10px; }
        .result-box { background: #e3f2fd; padding: 12px; border-radius: 8px; font-weight: bold; }
        input[type="number"], select { font-size: 14px; }
        .table-sm td, .table-sm th { padding: 0.35rem; font-size: 13px; }
        .input-with-rupee { position: relative; }
        .input-with-rupee::before { content: '₹'; position: absolute; left: 10px; top: 50%; transform: translateY(-50%); z-index: 10; color: #666; font-weight: 600; }
        .input-with-rupee input { padding-left: 25px; }
        .bank-checker-heading { background: linear-gradient(135deg, #003366 0%, #0066a1 100%); color: #fff; border-radius: 12px; padding: 18px 22px; margin: 18px 0 14px; box-shadow: 0 5px 16px rgba(0,51,102,.2); }
        .bank-checker-heading h4 { margin: 0; font-weight: 700; }
        .bank-checker-heading p { margin: 4px 0 0; opacity: .85; font-size: 13px; }
        .bank-inputs-card { background: #fff; border: 1px solid #c9dbe8; border-radius: 12px; padding: 16px; margin-top: 14px; box-shadow: 0 3px 12px rgba(0,51,102,.08); }
        .bank-inputs-title { color: #003366; font-size: 17px; font-weight: 700; border-bottom: 2px solid #eaf4fb; padding-bottom: 10px; margin-bottom: 12px; }
        .bank-inputs-card label { color: #38566b; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
        .save-bar { background: #fff; border-bottom: 2px solid #e2e8f0; padding: 10px 24px; position: sticky; top: 0; z-index: 100; }
        .save-bar .btn { font-size: 13px; }
        .section-divider { border-top: 2px solid #003366; margin: 30px 0 10px; }
        .section-title { color: #003366; font-weight: 700; font-size: 15px; }
        @media print { .save-bar { display: none !important; } .sidebar, .main-content { margin-left: 0 !important; } }
    </style>
</head>
<body>

<!-- Save/Load Bar -->
<div class="save-bar d-flex align-items-center gap-2 flex-wrap shadow-sm">
    <div class="d-flex align-items-center gap-2">
        <input type="text" id="customerName" placeholder="Customer Name" class="form-control form-control-sm" style="width:180px">
        <input type="text" id="loadCode" placeholder="Enter Code" class="form-control form-control-sm" style="width:120px" maxlength="6">
    </div>
    <button class="btn btn-sm btn-primary" onclick="loadCalculator()"><i class="bi bi-download me-1"></i>Load</button>
    <button class="btn btn-sm btn-success" onclick="saveCalculator()"><i class="bi bi-save me-1"></i>Save</button>
    <button class="btn btn-sm btn-info text-white" onclick="generateCode()"><i class="bi bi-key me-1"></i>Get Code</button>
    <button class="btn btn-sm btn-warning" id="updateBtn" onclick="updateCalculator()" style="display:none"><i class="bi bi-arrow-repeat me-1"></i>Update</button>
    <button class="btn btn-sm btn-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <div class="ms-auto">
        <a href="<?= BASE_URL ?>/tools/eligibility" class="btn btn-sm btn-outline-danger"><i class="bi bi-bank me-1"></i>Eligibility Checker</a>
    </div>
</div>

<div class="container-fluid py-3" id="pdfContent" style="transform:scale(0.85);transform-origin:top left;width:118%;">

    <!-- ===== EXISTING EMI DETAILS ===== -->
    <div class="row mt-2">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white"><h6 class="mb-0"><i class="bi bi-list-ul me-1"></i>Existing EMI Details</h6></div>
                <div class="card-body p-2">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Bank</th><th>Loan Type</th><th>Total Loan</th><th>Outstanding</th><th>IRR</th><th>EMI</th><th>BT</th></tr>
                        </thead>
                        <tbody id="emiTable">
                            <tr>
                                <td><input type="text" class="form-control form-control-sm" id="bank1" oninput="calculateObligations()"></td>
                                <td><select class="form-select form-select-sm" id="type1" onchange="handleLoanTypeChange(1)"><option value="">Select</option><option value="HL">HL</option><option value="PL">PL</option><option value="AL">AL</option><option value="EL">EL</option><option value="PRTY">PRTY</option><option value="CD">CD</option></select></td>
                                <td><input type="text" class="form-control form-control-sm" id="total1" value="0" oninput="formatNumber(this);calculateObligations()"></td>
                                <td><input type="text" class="form-control form-control-sm" id="out1" value="0" oninput="formatNumber(this);calculateObligations()"></td>
                                <td><input type="number" step="0.01" class="form-control form-control-sm" id="irr1" value="0" oninput="calculateObligations()"></td>
                                <td><input type="text" class="form-control form-control-sm" id="emiamt1" value="0" oninput="formatNumber(this);calculateObligations()"></td>
                                <td><select class="form-select form-select-sm" id="bt1" disabled onchange="calculateObligations()"><option>No</option><option>Yes</option><option>Closed</option></select></td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="btn btn-sm btn-success mt-2" onclick="addEMIRow()"><i class="bi bi-plus"></i> Add Row</button>
                    <button class="btn btn-sm btn-danger mt-2 ms-2" onclick="removeEMIRow()"><i class="bi bi-dash"></i> Remove Row</button>
                </div>
            </div>

            <!-- CC/GL Details -->
            <div class="card shadow-sm border-0 mt-3">
                <div class="card-header bg-warning"><h6 class="mb-0"><i class="bi bi-credit-card me-1"></i>CC/GL Details</h6></div>
                <div class="card-body p-2">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Bank</th><th>CC/GL</th><th>Total Limit</th><th>Outstanding</th><th>CC-5% GL-1%</th><th>BT</th></tr>
                        </thead>
                        <tbody id="ccTable">
                            <tr>
                                <td><input type="text" class="form-control form-control-sm" oninput="calculateObligations()"></td>
                                <td><select class="form-select form-select-sm" id="ccgl1" onchange="handleCCGLChange(1)"><option value="">Select</option><option value="CC">CC</option><option value="GL">GL</option></select></td>
                                <td><input type="text" class="form-control form-control-sm" oninput="formatNumber(this);calculateObligations()"></td>
                                <td><input type="text" class="form-control form-control-sm" id="ccout1" oninput="formatNumber(this);calculateObligations()"></td>
                                <td><input type="text" class="form-control form-control-sm" id="ccfive1" readonly></td>
                                <td><select class="form-select form-select-sm" id="ccbt1" disabled onchange="calculateObligations()"><option>No</option><option>Yes</option><option>Closed</option></select></td>
                            </tr>
                        </tbody>
                    </table>
                    <button class="btn btn-sm btn-success mt-2" onclick="addCCRow()"><i class="bi bi-plus"></i> Add Row</button>
                    <button class="btn btn-sm btn-danger mt-2 ms-2" onclick="removeCCRow()"><i class="bi bi-dash"></i> Remove Row</button>
                </div>
            </div>
        </div>

        <!-- Obligations & Eligibility -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="bi bi-calculator me-1"></i>Obligations & Eligibility</h6></div>
                <div class="card-body">
                    <div class="row mb-2"><div class="col-6"><strong>Loan Obligations:</strong></div><div class="col-6"><span class="badge bg-primary fs-6" id="loanObl">₹0</span></div></div>
                    <div class="row mb-2"><div class="col-6"><strong>CC Obligations:</strong></div><div class="col-6"><span class="badge bg-warning text-dark fs-6" id="ccObl">₹0</span></div></div>
                    <div class="row mb-2"><div class="col-6"><strong>Total Obligations:</strong></div><div class="col-6"><span class="badge bg-danger fs-6" id="totalObl">₹0</span></div></div>
                    <div class="row mb-2"><div class="col-6"><strong>Total BT Amount:</strong></div><div class="col-6"><span class="badge bg-info fs-6" id="totalBT">₹0</span></div></div>
                    <hr>
                    <div class="mb-2"><strong>Net Salary (₹):</strong></div>
                    <div class="mb-3"><input type="text" class="form-control" id="netSalary" value="0" oninput="formatNumber(this);calculateEligibility()"></div>
                    <h6 class="text-primary fw-bold">Loan Eligibility</h6>
                    <table class="table table-sm table-bordered text-center">
                        <thead class="table-light"><tr><th>Tenure</th><th>&lt;50k (50%)</th><th>60-75K (60%)</th><th>&gt;75k (70%)</th><th>&gt;75K+HL (75%)</th><th>&gt;1L+HL (80%)</th></tr></thead>
                        <tbody>
                            <tr><td><strong>5 Yrs</strong></td><td id="elig5_1">₹0L</td><td id="elig5_2">₹0L</td><td id="elig5_3">₹0L</td><td id="elig5_4">₹0L</td><td id="elig5_5">₹0L</td></tr>
                            <tr><td><strong>6 Yrs</strong></td><td id="elig6_1">₹0L</td><td id="elig6_2">₹0L</td><td id="elig6_3">₹0L</td><td id="elig6_4">₹0L</td><td id="elig6_5">₹0L</td></tr>
                            <tr><td><strong>7 Yrs</strong></td><td id="elig7_1">₹0L</td><td id="elig7_2">₹0L</td><td id="elig7_3">₹0L</td><td id="elig7_4">₹0L</td><td id="elig7_5">₹0L</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== EMI CALCULATOR ===== -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="calc-header text-center"><h4><i class="bi bi-calculator me-2"></i>EMI Calculator</h4></div>
            <div class="row mt-3 justify-content-center">
                <div class="col-md-8">
                    <div class="emi-card">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-semibold">Loan Amount</label>
                                <div class="input-with-rupee"><input type="text" class="form-control" id="emiLoanAmount" value="" placeholder="0" oninput="formatAndCalculate(this)"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold">IRR (%)</label>
                                <input type="number" step="0.01" class="form-control" id="emiIRR" value="0" oninput="calculateEMITable()">
                            </div>
                        </div>
                        <table class="table table-bordered text-center">
                            <thead class="table-light">
                                <tr><th>Months</th><th>12</th><th>24</th><th>36</th><th>48</th><th>60</th><th>72</th><th>84</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><strong>Years</strong></td><td>1</td><td>2</td><td>3</td><td>4</td><td>5</td><td>6</td><td>7</td></tr>
                                <tr><td><strong>EMI</strong></td><td id="emi_12">₹0</td><td id="emi_24">₹0</td><td id="emi_36">₹0</td><td id="emi_48">₹0</td><td id="emi_60">₹0</td><td id="emi_72">₹0</td><td id="emi_84">₹0</td></tr>
                                <tr><td><strong>Total Amount</strong></td><td id="total_12">₹0</td><td id="total_24">₹0</td><td id="total_36">₹0</td><td id="total_48">₹0</td><td id="total_60">₹0</td><td id="total_72">₹0</td><td id="total_84">₹0</td></tr>
                                <tr><td><strong>Principal</strong></td><td id="principal_12">₹0</td><td id="principal_24">₹0</td><td id="principal_36">₹0</td><td id="principal_48">₹0</td><td id="principal_60">₹0</td><td id="principal_72">₹0</td><td id="principal_84">₹0</td></tr>
                                <tr><td><strong>Interest</strong></td><td id="interest_12">₹0</td><td id="interest_24">₹0</td><td id="interest_36">₹0</td><td id="interest_48">₹0</td><td id="interest_60">₹0</td><td id="interest_72">₹0</td><td id="interest_84">₹0</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== BAJAJ EMI CALCULATOR ===== -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="calc-header text-center"><h4><i class="bi bi-calculator me-2"></i>BAJAJ EMI Calculator</h4></div>
            <div class="row mt-3 justify-content-center">
                <div class="col-md-8">
                    <div class="emi-card">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="fw-semibold">Loan Amount</label>
                                <div class="input-with-rupee"><input type="text" class="form-control" id="bajajLoanAmount" value="" placeholder="0" oninput="formatAndCalculateBajaj(this)"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold">IRR (%)</label>
                                <input type="number" step="0.01" class="form-control" id="bajajIRR" value="13.50" oninput="calculateBajajEMI()">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12"><div class="result-box text-center"><h5>Monthly Interest: <span id="monthlyInterest">₹0</span></h5></div></div>
                        </div>
                        <hr>
                        <h6 class="text-center mb-3 fw-bold">Comparison Calculator</h6>
                        <div class="row mb-3">
                            <div class="col-md-4"><label>EMI 1</label><input type="text" class="form-control" id="emi1" value="0" oninput="formatNumber(this);calculateComparison()"></div>
                            <div class="col-md-4"><label>EMI 2</label><input type="text" class="form-control" id="emi2" value="0" oninput="formatNumber(this);calculateComparison()"></div>
                            <div class="col-md-4"><label>Difference</label><input type="text" class="form-control" id="difference" value="0" readonly></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== FORECLOSURE CHARGES ===== -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="calc-header text-center" style="background:linear-gradient(135deg,#8B0000,#c0392b)"><h4><i class="bi bi-percent me-2"></i>Foreclosure Charges Calculator</h4></div>
            <div class="row mt-3 justify-content-center">
                <div class="col-md-8">
                    <div class="emi-card">
                        <div class="text-center mb-2"><small class="text-muted"><i class="bi bi-info-circle me-1"></i>Foreclosure charges include 18% GST</small></div>
                        <table class="table table-bordered text-center">
                            <thead class="table-light">
                                <tr><th>Principal Outstanding</th><th>Charges (%)</th><th>Amount (%+GST)</th><th>Include</th><th>Action</th></tr>
                            </thead>
                            <tbody id="foreclosureTable">
                                <tr data-foreclosure-row="1">
                                    <td><div class="input-with-rupee"><input type="text" class="form-control form-control-sm" id="fcLoan1" placeholder="0" oninput="formatNumber(this);calculateForeclosure()"></div></td>
                                    <td><input type="number" class="form-control form-control-sm" id="fcRate1" value="4" oninput="calculateForeclosure()" min="0"></td>
                                    <td id="fcAmount1" class="fw-bold" style="font-size:1.1rem;color:#8B0000">₹0</td>
                                    <td><select class="form-select form-select-sm" id="fcInclude1" onchange="calculateForeclosure()"><option value="yes">Yes</option><option value="no">No</option></select></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeForeclosureRow(1)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                <tr data-foreclosure-row="2">
                                    <td><div class="input-with-rupee"><input type="text" class="form-control form-control-sm" id="fcLoan2" placeholder="0" oninput="formatNumber(this);calculateForeclosure()"></div></td>
                                    <td><input type="number" class="form-control form-control-sm" id="fcRate2" value="4" oninput="calculateForeclosure()" min="0"></td>
                                    <td id="fcAmount2" class="fw-bold" style="font-size:1.1rem;color:#8B0000">₹0</td>
                                    <td><select class="form-select form-select-sm" id="fcInclude2" onchange="calculateForeclosure()"><option value="yes">Yes</option><option value="no">No</option></select></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeForeclosureRow(2)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                            <tfoot class="table-light">
                                <tr><th colspan="3" class="text-end">Selected Total</th><th colspan="2" id="fcOverallTotal" class="text-start fw-bold" style="font-size:1.1rem;color:#8B0000">₹0</th></tr>
                            </tfoot>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addForeclosureRow()"><i class="bi bi-plus me-1"></i>Add Row</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= defined('BASE_URL') ? BASE_URL : '/bestdealcrm' ?>';
const CSRF_TOKEN = '<?= csrfToken() ?>';
const BANK_POLICIES = <?= json_encode($bankPolicies, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

let emiRowCount = 1, ccRowCount = 1, fcRowCount = 2, currentCode = null;

// ===== Helpers =====
function formatNumber(input) {
    let v = input.value.replace(/,/g, '');
    if (v && !isNaN(v)) input.value = parseFloat(v).toLocaleString('en-IN');
}
function getNumericValue(input) {
    if (typeof input === 'string') return parseFloat(input.replace(/,/g, '')) || 0;
    return parseFloat((input?.value || '0').replace(/,/g, '')) || 0;
}
function formatAndCalculate(input) { formatNumber(input); calculateEMITable(); }
function formatAndCalculateBajaj(input) { formatNumber(input); calculateBajajEMI(); }

// ===== EMI Calculator =====
function calculateEMI(principal, rate, tenure) {
    if (principal === 0 || rate === 0 || tenure === 0) return 0;
    const r = rate / 100 / 12;
    return Math.round(principal * r * Math.pow(1+r, tenure) / (Math.pow(1+r, tenure) - 1));
}
function calculateEMITable() {
    const loan = getNumericValue(document.getElementById('emiLoanAmount'));
    const irr = parseFloat(document.getElementById('emiIRR').value) || 0;
    [12,24,36,48,60,72,84].forEach(t => {
        const emi = calculateEMI(loan, irr, t);
        document.getElementById('emi_'+t).textContent = '₹'+emi.toLocaleString('en-IN');
        document.getElementById('total_'+t).textContent = '₹'+Math.round(emi*t).toLocaleString('en-IN');
        document.getElementById('principal_'+t).textContent = '₹'+Math.round(loan).toLocaleString('en-IN');
        document.getElementById('interest_'+t).textContent = '₹'+Math.round(emi*t-loan).toLocaleString('en-IN');
    });
}

// ===== Bajaj EMI =====
function calculateBajajEMI() {
    const loan = getNumericValue(document.getElementById('bajajLoanAmount'));
    const irr = parseFloat(document.getElementById('bajajIRR').value) || 0;
    const mi = Math.round(loan * irr / 100 / 12);
    document.getElementById('monthlyInterest').textContent = '₹'+mi.toLocaleString('en-IN');
    document.getElementById('emi2').value = mi.toLocaleString('en-IN');
    calculateComparison();
}
function calculateComparison() {
    const d = getNumericValue(document.getElementById('emi1')) - getNumericValue(document.getElementById('emi2'));
    document.getElementById('difference').value = d.toLocaleString('en-IN');
}

// ===== Loan Type / CC GL =====
function handleLoanTypeChange(i) {
    const t = document.getElementById('type'+i)?.value;
    const bt = document.getElementById('bt'+i);
    bt.disabled = t !== 'PL';
    if (t !== 'PL') bt.value = 'No';
    calculateObligations();
}
function handleCCGLChange(i) {
    const ccgl = document.getElementById('ccgl'+i)?.value;
    const bt = document.getElementById('ccbt'+i);
    if (bt) { bt.disabled = !ccgl; if (!ccgl) bt.value = 'No'; }
    calculateObligations();
}

// ===== Obligations =====
function calculateObligations() {
    let loanObl=0, ccObl=0, totalBT=0;
    for (let i=1;i<=emiRowCount;i++) {
        const emi=getNumericValue(document.getElementById('emiamt'+i));
        const out=getNumericValue(document.getElementById('out'+i));
        const bt=document.getElementById('bt'+i)?.value;
        if (bt==='No') loanObl+=emi;
        else if (bt==='Yes') totalBT+=out;
    }
    document.querySelectorAll('#ccTable tr').forEach(row => {
        const sel=row.querySelector('td:nth-child(2) select');
        const out=row.querySelector('td:nth-child(4) input');
        const five=row.querySelector('td:nth-child(5) input');
        const bt=row.querySelector('td:nth-child(6) select');
        if (out&&five&&sel) {
            const o=getNumericValue(out);
            const pct=sel.value==='CC'?0.05:sel.value==='GL'?0.01:0;
            five.value=Math.round(o*pct).toLocaleString('en-IN');
            if (bt?.value==='No') ccObl+=o*pct;
            else if (bt?.value==='Yes') totalBT+=o;
        }
    });
    document.getElementById('loanObl').textContent='₹'+loanObl.toLocaleString('en-IN');
    document.getElementById('ccObl').textContent='₹'+Math.round(ccObl).toLocaleString('en-IN');
    document.getElementById('totalObl').textContent='₹'+Math.round(loanObl+ccObl).toLocaleString('en-IN');
    document.getElementById('totalBT').textContent='₹'+Math.round(totalBT).toLocaleString('en-IN');
    calculateEligibility();
}

// ===== Eligibility =====
function calculateEligibility() {
    const ns=getNumericValue(document.getElementById('netSalary'));
    const tObl=parseFloat(document.getElementById('totalObl').textContent.replace(/[₹,]/g,''))||0;
    const foir50=(ns*.5)-tObl, foir60=(ns*.6)-tObl, foir70=(ns*.7)-tObl, foir75=(ns*.75)-tObl, foir80=(ns*.8)-tObl;
    const calc=(f,d)=>f>0?(f/d).toFixed(2):'0';
    [['elig5_',foir50,2200],['elig6_',foir60,1900],['elig7_',foir70,1650]].forEach(([p,f,d])=>{
        document.getElementById(p+'1').textContent='₹'+calc(f,d)+'L';
        document.getElementById(p+'2').textContent='₹'+calc(foir60,d)+'L';
        document.getElementById(p+'3').textContent='₹'+calc(foir70,d)+'L';
        document.getElementById(p+'4').textContent='₹'+calc(foir75,d)+'L';
        document.getElementById(p+'5').textContent='₹'+calc(foir80,d)+'L';
    });
}

// ===== Add/Remove Rows =====
function addEMIRow() {
    emiRowCount++;
    const row=document.getElementById('emiTable').insertRow();
    row.innerHTML=`<td><input type="text" class="form-control form-control-sm" id="bank${emiRowCount}" oninput="calculateObligations()"></td>
        <td><select class="form-select form-select-sm" id="type${emiRowCount}" onchange="handleLoanTypeChange(${emiRowCount})"><option value="">Select</option><option value="HL">HL</option><option value="PL">PL</option><option value="AL">AL</option><option value="EL">EL</option><option value="PRTY">PRTY</option><option value="CD">CD</option></select></td>
        <td><input type="text" class="form-control form-control-sm" id="total${emiRowCount}" oninput="formatNumber(this);calculateObligations()"></td>
        <td><input type="text" class="form-control form-control-sm" id="out${emiRowCount}" oninput="formatNumber(this);calculateObligations()"></td>
        <td><input type="number" step="0.01" class="form-control form-control-sm" id="irr${emiRowCount}" value="0" oninput="calculateObligations()"></td>
        <td><input type="text" class="form-control form-control-sm" id="emiamt${emiRowCount}" oninput="formatNumber(this);calculateObligations()"></td>
        <td><select class="form-select form-select-sm" id="bt${emiRowCount}" disabled onchange="calculateObligations()"><option>No</option><option>Yes</option><option>Closed</option></select></td>`;
}
function removeEMIRow() { if(emiRowCount>1){document.getElementById('emiTable').deleteRow(-1);emiRowCount--;calculateObligations();} }
function addCCRow() {
    ccRowCount++;
    document.getElementById('ccTable').insertRow().innerHTML=`<td><input type="text" class="form-control form-control-sm" oninput="calculateObligations()"></td>
        <td><select class="form-select form-select-sm" id="ccgl${ccRowCount}" onchange="handleCCGLChange(${ccRowCount})"><option value="">Select</option><option value="CC">CC</option><option value="GL">GL</option></select></td>
        <td><input type="text" class="form-control form-control-sm" oninput="formatNumber(this);calculateObligations()"></td>
        <td><input type="text" class="form-control form-control-sm" oninput="formatNumber(this);calculateObligations()"></td>
        <td><input type="text" class="form-control form-control-sm" readonly></td>
        <td><select class="form-select form-select-sm" id="ccbt${ccRowCount}" disabled onchange="calculateObligations()"><option>No</option><option>Yes</option><option>Closed</option></select></td>`;
}
function removeCCRow() { if(ccRowCount>1){document.getElementById('ccTable').deleteRow(-1);ccRowCount--;calculateObligations();} }

// ===== Foreclosure =====
function addForeclosureRow() {
    fcRowCount++;
    const row=document.getElementById('foreclosureTable').insertRow();
    row.dataset.foreclosureRow=fcRowCount;
    row.innerHTML=`<td><div class="input-with-rupee"><input type="text" class="form-control form-control-sm" id="fcLoan${fcRowCount}" placeholder="0" oninput="formatNumber(this);calculateForeclosure()"></div></td>
        <td><input type="number" class="form-control form-control-sm" id="fcRate${fcRowCount}" value="4" oninput="calculateForeclosure()" min="0"></td>
        <td id="fcAmount${fcRowCount}" class="fw-bold" style="font-size:1.1rem;color:#8B0000">₹0</td>
        <td><select class="form-select form-select-sm" id="fcInclude${fcRowCount}" onchange="calculateForeclosure()"><option value="yes">Yes</option><option value="no">No</option></select></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeForeclosureRow(${fcRowCount})"><i class="bi bi-trash"></i></button></td>`;
    calculateForeclosure();
}
function removeForeclosureRow(n) {
    const row=document.querySelector(`#foreclosureTable tr[data-foreclosure-row="${n}"]`);
    if(row) row.remove();
    calculateForeclosure();
}
function calculateForeclosure() {
    let total=0;
    document.querySelectorAll('#foreclosureTable tr[data-foreclosure-row]').forEach(row=>{
        const n=row.dataset.foreclosureRow;
        const loan=getNumericValue(document.getElementById('fcLoan'+n));
        const rate=parseFloat(document.getElementById('fcRate'+n)?.value)||0;
        const amt=loan>0&&rate>0?loan*(rate/100)*1.18:0;
        document.getElementById('fcAmount'+n).textContent='₹'+Math.round(amt).toLocaleString('en-IN');
        if(document.getElementById('fcInclude'+n)?.value==='yes') total+=amt;
    });
    document.getElementById('fcOverallTotal').textContent='₹'+Math.round(total).toLocaleString('en-IN');
}

// ===== Bank Eligibility (from calculator) =====
function calculateBankEligibility() {
    const el=id=>document.getElementById(id);
    const num=id=>parseFloat((el(id)?.value||'0').replace(/,/g,''))||0;
    const salary=num('netSalary'), loan=num('requestedLoan'), age=num('customerAge'), score=num('cibilScore');
    const profile=el('employerProfile')?.value||'listed', type=el('cibilType')?.value||'normal';
    const total=parseFloat((el('totalObl')?.textContent||'0').replace(/[₹,]/g,''))||0;
    const employment=num('employmentMonths'), experience=num('experienceMonths');
    const enq1=num('enq1'), enq3=num('enq3'), live=num('livePl');
    const payslip=el('payslip')?.value==='available', pg=el('pgAccommodation')?.value==='yes', address=el('addressProof')?.value==='available';
    let eligible=0;
    const results=BANK_POLICIES.map(b=>{
        const bad=[],review=[];
        const minSalary=profile==='listed'?b.salaryListed:b.salaryUnlisted;
        const minCibil=profile==='listed'?b.cibilListed:b.cibilUnlisted;
        const foir=salary?total/salary:0;
        if(!loan||loan<b.min||loan>b.max) bad.push(loan>b.max?'Above max':'Below min');
        if(!salary||salary<minSalary) bad.push('Salary below minimum');
        if(type!=='normal'&&!b.zeroOne) bad.push('CIBIL 0/-1 not allowed');
        if(type==='normal'&&(!score||score<minCibil)) bad.push('CIBIL below minimum');
        if(!age||age<b.ageMin||age>b.ageMax) bad.push('Age outside range');
        if(b.profile==='listed'&&profile!=='listed') bad.push('Unlisted employer');
        if(b.employment&&employment<b.employment) bad.push('Employment below min');
        if(b.experience&&experience<b.experience) bad.push('Experience below min');
        if(foir>b.foir) bad.push('FOIR above limit');
        if(b.enq1!==null&&enq1>b.enq1) bad.push('1M enq above limit');
        if(b.enq3!==null&&enq3>b.enq3) bad.push('3M enq above limit');
        if(b.livePl!==null&&live>b.livePl) bad.push('Live PL above limit');
        if(b.payslip==='required'&&!payslip) bad.push('Payslip required');
        if(b.pg==='not_allowed'&&pg) bad.push('PG not accepted');
        if(b.pg==='review'&&pg) review.push('PA/CPV may be needed');
        if(b.address==='required'&&!address) bad.push('Address proof required');
        const status=bad.length?'NOT ELIGIBLE':(review.length?'REVIEW REQUIRED':'ELIGIBLE');
        if(status==='ELIGIBLE') eligible++;
        return{...b,status,reason:[...bad,...review].join('; ')||'All checks passed'};
    }).sort((a,b)=>({ELIGIBLE:1,'REVIEW REQUIRED':2,'NOT ELIGIBLE':3}[a.status]-{ELIGIBLE:1,'REVIEW REQUIRED':2,'NOT ELIGIBLE':3}[b.status]));
    const body=el('bankEligibilityTable')?.querySelector('tbody');
    if(!body) return;
    body.innerHTML=results.map((b,i)=>`<tr><td>${i+1}</td><td><strong>${b.name}</strong></td><td><span class="badge ${b.status==='ELIGIBLE'?'bg-success':b.status==='REVIEW REQUIRED'?'bg-warning text-dark':'bg-danger'}">${b.status}</span></td><td>${b.reason}</td><td>₹${b.min.toLocaleString('en-IN')} - ₹${b.max.toLocaleString('en-IN')}</td><td>${b.roi}</td><td>${b.tenure}</td><td>${Math.round(b.foir*100)}%</td></tr>`).join('');
    el('eligibleCount').textContent=eligible+' eligible';
}

// ===== Save/Load/Update =====
function getAllData() {
    const data = { customerName: document.getElementById('customerName').value, emiRows: [], ccRows: [],
        netSalary: document.getElementById('netSalary').value,
        emiLoanAmount: document.getElementById('emiLoanAmount').value,
        emiIRR: document.getElementById('emiIRR').value,
        bajajLoanAmount: document.getElementById('bajajLoanAmount').value,
        bajajIRR: document.getElementById('bajajIRR').value,
        emi1: document.getElementById('emi1').value, emi2: document.getElementById('emi2').value,
        foreclosure: [] };
    for (let i=1;i<=emiRowCount;i++) data.emiRows.push({bank:document.getElementById('bank'+i)?.value||'',type:document.getElementById('type'+i)?.value||'',total:document.getElementById('total'+i)?.value||'0',out:document.getElementById('out'+i)?.value||'0',irr:document.getElementById('irr'+i)?.value||'0',emi:document.getElementById('emiamt'+i)?.value||'0',bt:document.getElementById('bt'+i)?.value||'No'});
    document.querySelectorAll('#ccTable tr').forEach(row=>{const cells=row.querySelectorAll('input,select');if(cells.length>=6) data.ccRows.push({bank:cells[0].value||'',ccgl:cells[1].value||'',limit:cells[2].value||'0',out:cells[3].value||'0',five:cells[4].value||'0',bt:cells[5].value||'No'});});
    document.querySelectorAll('#foreclosureTable tr[data-foreclosure-row]').forEach(row=>{const n=row.dataset.foreclosureRow;data.foreclosure.push({loan:document.getElementById('fcLoan'+n)?.value||'',rate:document.getElementById('fcRate'+n)?.value||'4',include:document.getElementById('fcInclude'+n)?.value||'yes'});});
    return data;
}

function setAllData(data) {
    while(emiRowCount>1)removeEMIRow();while(ccRowCount>1)removeCCRow();
    document.getElementById('customerName').value=data.customerName||'';
    (data.emiRows||[]).forEach((row,idx)=>{if(idx>0)addEMIRow();const i=idx+1;document.getElementById('bank'+i).value=row.bank;document.getElementById('type'+i).value=row.type;document.getElementById('total'+i).value=row.total;document.getElementById('out'+i).value=row.out;document.getElementById('irr'+i).value=row.irr||'0';document.getElementById('emiamt'+i).value=row.emi;document.getElementById('bt'+i).value=row.bt;handleLoanTypeChange(i);});
    (data.ccRows||[]).forEach((row,idx)=>{if(idx>0)addCCRow();const cells=document.querySelectorAll('#ccTable tr')[idx]?.querySelectorAll('input,select');if(cells){cells[0].value=row.bank;cells[1].value=row.ccgl;cells[2].value=row.limit;cells[3].value=row.out;cells[4].value=row.five;cells[5].value=row.bt;}});
    document.getElementById('netSalary').value=data.netSalary||'0';
    document.getElementById('emiLoanAmount').value=data.emiLoanAmount||'';
    document.getElementById('emiIRR').value=data.emiIRR||'0';
    document.getElementById('bajajLoanAmount').value=data.bajajLoanAmount||'';
    document.getElementById('bajajIRR').value=data.bajajIRR||'13.50';
    document.getElementById('emi1').value=data.emi1||'0';
    document.getElementById('emi2').value=data.emi2||'0';
    if(data.foreclosure&&data.foreclosure.length){
        const ft=document.getElementById('foreclosureTable');ft.innerHTML='';fcRowCount=0;
        data.foreclosure.forEach(r=>addForeclosureRow());
    }
    calculateObligations();calculateEMITable();calculateBajajEMI();calculateForeclosure();
}

async function saveCalculator() {
    const name = document.getElementById('customerName').value.trim();
    if(!name){showToast('Please enter customer name','warning');return null;}
    try {
        const fd = new FormData();fd.append('data',JSON.stringify(getAllData()));fd.append('save_type','calculator');fd.append('_csrf_token',CSRF_TOKEN);
        const resp=await fetch(BASE_URL+'/tools/api/save',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});
        const result=await resp.json();
        if(result.success){
            currentCode=result.code;document.getElementById('loadCode').value=result.code;document.getElementById('updateBtn').style.display='inline-block';
            showToast('Saved! Code: '+result.code,'success');return result.code;
        } else { showToast(result.message||'Save failed','danger');return null; }
    } catch(e){showToast('Save error: '+e.message,'danger');return null;}
}

async function generateCode() {
    const code=await saveCalculator();
    if(code) showToast('Generated Code: '+code+'\nUse this code to load on any device.','success');
}

async function loadCalculator() {
    const code=document.getElementById('loadCode').value.trim().toUpperCase();
    if(!code){showToast('Please enter a code','warning');return;}
    try {
        const resp=await fetch(BASE_URL+'/tools/api/load?code='+code,{headers:{'X-Requested-With':'XMLHttpRequest'}});
        const result=await resp.json();
        if(result.success){const data=JSON.parse(result.data);setAllData(data);currentCode=code;document.getElementById('updateBtn').style.display='inline-block';showToast('Loaded! Customer: '+(result.customer_name||''),'success');}
        else showToast(result.message||'Load failed','danger');
    } catch(e){showToast('Load error: '+e.message,'danger');}
}

async function updateCalculator() {
    if(!currentCode){showToast('No code loaded','warning');return;}
    try {
        const fd=new FormData();fd.append('code',currentCode);fd.append('data',JSON.stringify(getAllData()));fd.append('_csrf_token',CSRF_TOKEN);
        const resp=await fetch(BASE_URL+'/tools/api/update',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});
        const result=await resp.json();
        showToast(result.success?'Updated!':result.message||'Update failed',result.success?'success':'danger');
    } catch(e){showToast('Update error: '+e.message,'danger');}
}

function showToast(msg,type){
    var c=document.getElementById('toast-container');if(!c){c=document.createElement('div');c.id='toast-container';c.style.cssText='position:fixed;top:20px;right:20px;z-index:9999;max-width:400px;';document.body.appendChild(c);}
    var colors={success:'#22c55e',danger:'#ef4444',warning:'#f59e0b',info:'#3b82f6'};
    var t=document.createElement('div');t.style.cssText='background:'+(colors[type]||colors.info)+';color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:8px;box-shadow:0 4px 12px rgba(0,0,0,.15);font-size:14px;display:flex;justify-content:space-between;align-items:center;animation:fadeIn .3s;';
    t.innerHTML='<span>'+msg+'</span><button onclick="this.parentElement.remove()" style="background:none;border:none;color:#fff;font-size:18px;cursor:pointer;margin-left:10px">&times;</button>';
    c.appendChild(t);setTimeout(()=>t.remove(),5000);
}

// Init
calculateEMITable();calculateObligations();calculateForeclosure();
</script>
</body>
</html>
