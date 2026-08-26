<?php
require_once 'config.php';
$bankPolicies = require __DIR__ . '/bank_policies.php';

// Load calculator config
$calculatorConfigFile = __DIR__ . '/calculator_config.php';
$calculatorConfig = [];
if (file_exists($calculatorConfigFile)) {
    $calculatorConfig = require $calculatorConfigFile;
}
if (!is_array($calculatorConfig)) {
    $calculatorConfig = [];
}

// Check IP restriction for calculator page
if (!empty($calculatorConfig['ip_restricted'])) {
    function getCalculatorUserIP() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    $userIp = getCalculatorUserIP();
    $allowedIPs = $pdo->query("SELECT ip_address FROM allowed_ips")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array($userIp, $allowedIPs)) {
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .denied-card {
            background: #fff;
            border-radius: 20px;
            padding: 3rem 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 90%;
            text-align: center;
        }
        .denied-card .icon-circle {
            width: 80px;
            height: 80px;
            background: #dc3545;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .denied-card .icon-circle i {
            font-size: 2rem;
            color: #fff;
        }
        .denied-card h3 {
            font-weight: 700;
            color: #333;
        }
        .denied-card .subtitle {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="denied-card">
        <div class="icon-circle">
            <i class="fas fa-ban"></i>
        </div>
        <h3>Access Denied</h3>
        <p class="subtitle">This calculator can only be accessed from office network. Your IP address (' . htmlspecialchars($userIp) . ') is not allowed.</p>
        <a href="javascript:history.back()" class="btn btn-primary">Go Back</a>
    </div>
</body>
</html>';
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMI Comparison Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        .calc-header { background: #003366; color: white; padding: 15px; }
        .emi-card { background: #f8f9fa; border: 2px solid #003366; padding: 15px; margin-bottom: 10px; }
        .result-box { background: #e3f2fd; padding: 10px; border-radius: 5px; font-weight: bold; }
        input[type="number"], select { font-size: 14px; }
        .table-sm td, .table-sm th { padding: 0.3rem; font-size: 13px; }
        .table-sm td:last-child select { min-width: 60px; }
        .input-with-rupee { position: relative; }
        .input-with-rupee input { padding-left: 25px; }
        .bank-checker-heading { background: linear-gradient(135deg, #003366 0%, #0066a1 100%); color: #fff; border-radius: 12px; padding: 18px 22px; margin: 18px 0 14px; box-shadow: 0 5px 16px rgba(0,51,102,.2); }
        .bank-checker-heading h4 { margin: 0; font-weight: 700; }
        .bank-checker-heading p { margin: 4px 0 0; opacity: .85; font-size: 13px; }
        .bank-checker-card { border: 2px solid #0066a1; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,.08); overflow: hidden; }
        .bank-checker-card .table thead th { background: #eaf4fb; color: #003366; white-space: nowrap; }
        .bank-checker-card .table tbody tr:hover { background: #f4fbff; }
        .bank-inputs-card { background: #fff; border: 1px solid #c9dbe8; border-radius: 12px; padding: 16px; margin-top: 14px; box-shadow: 0 3px 12px rgba(0,51,102,.08); }
        .bank-inputs-title { color: #003366; font-size: 17px; font-weight: 700; border-bottom: 2px solid #eaf4fb; padding-bottom: 10px; margin-bottom: 12px; }
        .bank-inputs-card label { color: #38566b; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
        .input-with-rupee::before { content: '₹'; position: absolute; left: 10px; top: 50%; transform: translateY(-50%); z-index: 10; }
    </style>
</head>
<body class="bg-light">
    <div class="text-center mt-2 mb-2">
        <input type="text" id="customerName" placeholder="Customer Name" class="form-control d-inline-block me-2" style="width:200px;">
        <input type="text" id="loadCode" placeholder="Enter Code" class="form-control d-inline-block me-2" style="width:150px;">
        <button class="btn btn-primary" onclick="loadCalculator()"><i class="fas fa-download me-2"></i>Load</button>
        <button class="btn btn-success ms-2" onclick="saveCalculator()"><i class="fas fa-save me-2"></i>Save</button>
        <button class="btn btn-info ms-2" onclick="generateCode()"><i class="fas fa-key me-2"></i>Generate Code</button>
        <button class="btn btn-warning ms-2" id="updateBtn" onclick="updateCalculator()" style="display:none;"><i class="fas fa-sync me-2"></i>Update</button>
    </div>
    <div class="container-fluid mt-3" style="transform: scale(0.8); transform-origin: top left; width: 125%;" id="pdfContent">
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white"><h6 class="mb-0">Existing EMI Details</h6></div>
                    <div class="card-body p-2">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bank</th>
                                    <th>Loan Type</th>
                                    <th>Total Loan</th>
                                    <th>Outstanding</th>
                                    <th>IRR</th>
                                    <th>EMI</th>
                                    <th>BT</th>
                                </tr>
                            </thead>
                            <tbody id="emiTable">
                                <tr>
                                    <td><input type="text" class="form-control form-control-sm" id="bank1" oninput="calculateObligations()"></td>
                                    <td><select class="form-select form-select-sm" id="type1" onchange="handleLoanTypeChange(1)"><option value="">Select</option><option value="HL">HL</option><option value="PL">PL</option><option value="AL">AL</option><option value="EL">EL</option><option value="PRTY">PRTY</option><option value="CD">CD</option></select></td>
                                    <td><input type="text" class="form-control form-control-sm" id="total1" value="0" oninput="formatNumber(this); calculateObligations()"></td>
                                    <td><input type="text" class="form-control form-control-sm" id="out1" value="0" oninput="formatNumber(this); calculateObligations()"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" id="irr1" value="0" oninput="calculateObligations()"></td>
                                    <td><input type="text" class="form-control form-control-sm" id="emiamt1" value="0" oninput="formatNumber(this); calculateObligations()"></td>
                                    <td><select class="form-select form-select-sm" id="bt1" disabled onchange="calculateObligations()"><option>No</option><option>Yes</option><option>Closed</option></select></td>
                                </tr>
                            </tbody>
                        </table>
                        <button class="btn btn-sm btn-success mt-2" onclick="addEMIRow()"><i class="fas fa-plus"></i> Add Row</button>
                        <button class="btn btn-sm btn-danger mt-2 ms-2" onclick="removeEMIRow()"><i class="fas fa-minus"></i> Remove Row</button>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header bg-warning"><h6 class="mb-0">CC/GL Details</h6></div>
                    <div class="card-body p-2">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bank</th>
                                    <th>CC/GL</th>
                                    <th>Total Limit</th>
                                    <th>Outstanding</th>
                                    <th>CC-5% GL-1%</th>
                                    <th>BT</th>
                                </tr>
                            </thead>
                            <tbody id="ccTable">
                                <tr>
                                    <td><input type="text" class="form-control form-control-sm" oninput="calculateObligations()"></td>
                                    <td><select class="form-select form-select-sm" id="ccgl1" onchange="handleCCGLChange(1)"><option value="">Select</option><option value="CC">CC</option><option value="GL">GL</option></select></td>
                                    <td><input type="text" class="form-control form-control-sm" oninput="formatNumber(this); calculateObligations()"></td>
                                    <td><input type="text" class="form-control form-control-sm" id="ccout1" oninput="formatNumber(this); calculateObligations()"></td>
                                    <td><input type="text" class="form-control form-control-sm" id="ccfive1" readonly></td>
                                    <td><select class="form-select form-select-sm" id="ccbt1" disabled onchange="calculateObligations()"><option>No</option><option>Yes</option><option>Closed</option></select></td>
                                </tr>
                            </tbody>
                        </table>
                        <button class="btn btn-sm btn-success mt-2" onclick="addCCRow()"><i class="fas fa-plus"></i> Add Row</button>
                        <button class="btn btn-sm btn-danger mt-2 ms-2" onclick="removeCCRow()"><i class="fas fa-minus"></i> Remove Row</button>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white"><h6 class="mb-0">Obligations & Eligibility</h6></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6"><strong>Loan Obligations:</strong></div>
                            <div class="col-6"><span class="badge bg-primary" id="loanObl">₹0</span></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6"><strong>CC Obligations:</strong></div>
                            <div class="col-6"><span class="badge bg-warning" id="ccObl">₹0</span></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6"><strong>Total Obligations:</strong></div>
                            <div class="col-6"><span class="badge bg-danger" id="totalObl">₹0</span></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6"><strong>Total BT Amount:</strong></div>
                            <div class="col-6"><span class="badge bg-info" id="totalBT">₹0</span></div>
                        </div>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-12"><strong>Net Salary (₹):</strong></div>
                            <div class="col-12"><input type="text" class="form-control" id="netSalary" value="0" oninput="formatNumber(this); calculateEligibility()"></div>
                        </div>
                        <hr>
                        <h6>Loan Eligibility</h6>
                        <table class="table table-sm table-bordered">
                            <tr><th>Tenure</th><th>&lt;50k (50%)</th><th>60-75K (60%)</th><th>&gt;75k (70%)</th><th>&gt;75K + HL (75%)</th><th>&gt;1 Lakhs + HL (80%)</th></tr>
                            <tr><td>5 Years</td><td id="elig5_1">₹0L</td><td id="elig5_2">₹0L</td><td id="elig5_3">₹0L</td><td id="elig5_4">₹0L</td><td id="elig5_5">₹0L</td></tr>
                            <tr><td>6 Years</td><td id="elig6_1">₹0L</td><td id="elig6_2">₹0L</td><td id="elig6_3">₹0L</td><td id="elig6_4">₹0L</td><td id="elig6_5">₹0L</td></tr>
                            <tr><td>7 Years</td><td id="elig7_1">₹0L</td><td id="elig7_2">₹0L</td><td id="elig7_3">₹0L</td><td id="elig7_4">₹0L</td><td id="elig7_5">₹0L</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="calc-header text-center">
                    <h4><i class="fas fa-calculator me-2"></i>EMI Calculator</h4>
                </div>
                <div class="row mt-3 justify-content-center">
                    <div class="col-md-8">
                        <div class="emi-card">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Loan Amount</label>
                                    <div class="input-with-rupee">
                                        <input type="text" class="form-control" id="emiLoanAmount" value="" placeholder="0" oninput="formatAndCalculate(this)">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>IRR (%)</label>
                                    <input type="number" step="0.01" class="form-control" id="emiIRR" value="0" oninput="calculateEMITable()">
                                </div>
                            </div>
                            <table class="table table-bordered text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Months</th>
                                        <th>12</th>
                                        <th>24</th>
                                        <th>36</th>
                                        <th>48</th>
                                        <th>60</th>
                                        <th>72</th>
                                        <th>84</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Years</strong></td>
                                        <td>1</td>
                                        <td>2</td>
                                        <td>3</td>
                                        <td>4</td>
                                        <td>5</td>
                                        <td>6</td>
                                        <td>7</td>
                                    </tr>
                                    <tr>
                                        <td><strong>EMI</strong></td>
                                        <td id="emi_12">₹0</td>
                                        <td id="emi_24">₹0</td>
                                        <td id="emi_36">₹0</td>
                                        <td id="emi_48">₹0</td>
                                        <td id="emi_60">₹0</td>
                                        <td id="emi_72">₹0</td>
                                        <td id="emi_84">₹0</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Amount</strong></td>
                                        <td id="total_12">₹0</td>
                                        <td id="total_24">₹0</td>
                                        <td id="total_36">₹0</td>
                                        <td id="total_48">₹0</td>
                                        <td id="total_60">₹0</td>
                                        <td id="total_72">₹0</td>
                                        <td id="total_84">₹0</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Principal Amount</strong></td>
                                        <td id="principal_12">₹0</td>
                                        <td id="principal_24">₹0</td>
                                        <td id="principal_36">₹0</td>
                                        <td id="principal_48">₹0</td>
                                        <td id="principal_60">₹0</td>
                                        <td id="principal_72">₹0</td>
                                        <td id="principal_84">₹0</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Interest Amount</strong></td>
                                        <td id="interest_12">₹0</td>
                                        <td id="interest_24">₹0</td>
                                        <td id="interest_36">₹0</td>
                                        <td id="interest_48">₹0</td>
                                        <td id="interest_60">₹0</td>
                                        <td id="interest_72">₹0</td>
                                        <td id="interest_84">₹0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="calc-header text-center">
                    <h4><i class="fas fa-calculator me-2"></i>BAJAJ EMI Calculator</h4>
                </div>
                <div class="row mt-3 justify-content-center">
                    <div class="col-md-8">
                        <div class="emi-card">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Loan Amount</label>
                                    <div class="input-with-rupee">
                                        <input type="text" class="form-control" id="bajajLoanAmount" value="" placeholder="0" oninput="formatAndCalculateBajaj(this)">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label>IRR (%)</label>
                                    <input type="number" step="0.01" class="form-control" id="bajajIRR" value="13.50" oninput="calculateBajajEMI()">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="result-box text-center">
                                        <h5>Monthly Interest: <span id="monthlyInterest">₹0</span></h5>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <h6 class="text-center mb-3">Comparison Calculator</h6>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label>EMI 1</label>
                                    <input type="text" class="form-control" id="emi1" value="0" oninput="formatNumber(this); calculateComparison()">
                                </div>
                                <div class="col-md-4">
                                    <label>EMI 2</label>
                                    <input type="text" class="form-control" id="emi2" value="0" oninput="formatNumber(this); calculateComparison()">
                                </div>
                                <div class="col-md-4">
                                    <label>Difference</label>
                                    <input type="text" class="form-control" id="difference" value="0" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="calc-header text-center" style="background: #8B0000;">
                    <h4><i class="fas fa-percent me-2"></i>Foreclosure Charges Calculator</h4>
                </div>
                <div class="row mt-3 justify-content-center">
                    <div class="col-md-8">
                        <div class="emi-card">
                            <div class="text-center mb-2">
                                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Foreclosure charges include 18% GST</small>
                            </div>
                            <table class="table table-bordered text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Current Principal Outstanding Amount</th>
                                        <th>Foreclosure Charges (%)</th>
                                        <th>Amount <small class="text-muted">(% + 18% GST)</small></th>
                                        <th>Include in Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="foreclosureTable">
                                    <tr>
                                        <td>
                                            <div class="input-with-rupee">
                                                <input type="text" class="form-control form-control-sm" id="fcLoan1" placeholder="0" oninput="formatNumber(this); calculateForeclosure()">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" id="fcRate1" value="4" oninput="calculateForeclosure()" min="0">
                                        </td>
                                        <td id="fcAmount1" class="fw-bold" style="font-size: 1.1rem; color: #8B0000;">₹0</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="input-with-rupee">
                                                <input type="text" class="form-control form-control-sm" id="fcLoan2" placeholder="0" oninput="formatNumber(this); calculateForeclosure()">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" id="fcRate2" value="4" oninput="calculateForeclosure()" min="0">
                                        </td>
                                        <td id="fcAmount2" class="fw-bold" style="font-size: 1.1rem; color: #8B0000;">₹0</td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addForeclosureRow()"><i class="fas fa-plus me-1"></i>Add Another Calculation</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const BANK_POLICIES = <?= json_encode($bankPolicies, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        let emiRowCount = 1;
        let ccRowCount = 1;
        let fcRowCount = 2;

        function calculateEMI(principal, rate, tenure) {
            if (principal === 0 || rate === 0 || tenure === 0) return 0;
            const r = rate / 100 / 12;
            const emi = principal * r * Math.pow(1 + r, tenure) / (Math.pow(1 + r, tenure) - 1);
            return Math.round(emi);
        }

        function formatNumber(input) {
            let value = input.value.replace(/,/g, '');
            if (value && !isNaN(value)) {
                input.value = parseFloat(value).toLocaleString('en-IN');
            }
        }

        function getNumericValue(input) {
            if (typeof input === 'string') return parseFloat(input.replace(/,/g, '')) || 0;
            return parseFloat(input?.value?.replace(/,/g, '')) || 0;
        }

        function formatAndCalculate(input) {
            formatNumber(input);
            calculateEMITable();
        }

        function calculateEMITable() {
            const loanAmountStr = document.getElementById('emiLoanAmount').value.replace(/,/g, '');
            const loanAmount = parseFloat(loanAmountStr) || 0;
            const irr = parseFloat(document.getElementById('emiIRR').value) || 0;
            
            const tenures = [12, 24, 36, 48, 60, 72, 84];
            tenures.forEach(tenure => {
                const emi = calculateEMI(loanAmount, irr, tenure);
                const totalAmount = emi * tenure;
                const interestAmount = totalAmount - loanAmount;
                
                document.getElementById('emi_' + tenure).textContent = '₹' + emi.toLocaleString('en-IN');
                document.getElementById('total_' + tenure).textContent = '₹' + Math.round(totalAmount).toLocaleString('en-IN');
                document.getElementById('principal_' + tenure).textContent = '₹' + Math.round(loanAmount).toLocaleString('en-IN');
                document.getElementById('interest_' + tenure).textContent = '₹' + Math.round(interestAmount).toLocaleString('en-IN');
            });
        }

        function calculate() {
            calculateEMITable();
        }

        function handleLoanTypeChange(rowNum) {
            const typeSelect = document.getElementById('type' + rowNum);
            const btSelect = document.getElementById('bt' + rowNum);
            
            if (typeSelect.value === 'PL') {
                btSelect.disabled = false;
            } else if (typeSelect.value) {
                btSelect.disabled = true;
                btSelect.value = 'No';
            } else {
                btSelect.disabled = true;
            }
            calculateObligations();
        }

        function handleCCGLChange(rowNum) {
            const ccglSelect = document.getElementById('ccgl' + rowNum);
            const btSelect = document.getElementById('ccbt' + rowNum);
            
            if (ccglSelect.value) {
                btSelect.disabled = false;
            } else {
                btSelect.disabled = true;
            }
            calculateObligations();
        }

        function calculateObligations() {
            let loanObl = 0;
            let ccObl = 0;
            let totalBT = 0;

            for (let i = 1; i <= emiRowCount; i++) {
                const emiAmt = getNumericValue(document.getElementById('emiamt' + i));
                const outAmt = getNumericValue(document.getElementById('out' + i));
                const bt = document.getElementById('bt' + i)?.value;
                
                // Only consider EMI in obligations if BT is 'No'
                // If BT is 'Closed', don't add to obligations or BT amount
                if (bt === 'No') {
                    loanObl += emiAmt;
                } else if (bt === 'Yes') {
                    totalBT += outAmt;
                }
                // If bt === 'Closed', don't add to either loanObl or totalBT
            }

            const ccRows = document.querySelectorAll('#ccTable tr');
            ccRows.forEach((row, idx) => {
                const ccglSelect = row.querySelector('td:nth-child(2) select');
                const outInput = row.querySelector('td:nth-child(4) input');
                const fiveInput = row.querySelector('td:nth-child(5) input');
                const btSelect = row.querySelector('td:nth-child(6) select');
                
                if (outInput && fiveInput && ccglSelect) {
                    const out = getNumericValue(outInput);
                    const percentage = ccglSelect.value === 'CC' ? 0.05 : ccglSelect.value === 'GL' ? 0.01 : 0;
                    const five = out * percentage;
                    fiveInput.value = Math.round(five).toLocaleString('en-IN');
                    
                    // Only consider CC/GL in obligations if BT is 'No'
                    // If BT is 'Closed', don't add to obligations or BT amount
                    if (btSelect?.value === 'No') {
                        ccObl += five;
                    } else if (btSelect?.value === 'Yes') {
                        totalBT += out;
                    }
                    // If btSelect?.value === 'Closed', don't add to either ccObl or totalBT
                }
            });

            document.getElementById('loanObl').textContent = '₹' + loanObl.toLocaleString('en-IN');
            document.getElementById('ccObl').textContent = '₹' + Math.round(ccObl).toLocaleString('en-IN');
            document.getElementById('totalObl').textContent = '₹' + Math.round(loanObl + ccObl).toLocaleString('en-IN');
            document.getElementById('totalBT').textContent = '₹' + Math.round(totalBT).toLocaleString('en-IN');
            
            calculateEligibility();
        }

        function calculateEligibility() {
            const netSalary = getNumericValue(document.getElementById('netSalary'));
            const totalObl = parseFloat(document.getElementById('totalObl').textContent.replace(/[₹,]/g, '')) || 0;
            
            const foir50 = (netSalary * 0.5) - totalObl;
            const foir60 = (netSalary * 0.6) - totalObl;
            const foir70 = (netSalary * 0.7) - totalObl;
            const foir75 = (netSalary * 0.75) - totalObl;
            const foir80 = (netSalary * 0.8) - totalObl;

            // 5 Years eligibility
            const elig5_50 = foir50 > 0 ? (foir50 / 2200).toFixed(2) : 0;
            const elig5_60 = foir60 > 0 ? (foir60 / 2200).toFixed(2) : 0;
            const elig5_70 = foir70 > 0 ? (foir70 / 2200).toFixed(2) : 0;
            const elig5_75 = foir75 > 0 ? (foir75 / 2200).toFixed(2) : 0;
            const elig5_80 = foir80 > 0 ? (foir80 / 2200).toFixed(2) : 0;
            
            // 6 Years eligibility
            const elig6_50 = foir50 > 0 ? (foir50 / 1900).toFixed(2) : 0;
            const elig6_60 = foir60 > 0 ? (foir60 / 1900).toFixed(2) : 0;
            const elig6_70 = foir70 > 0 ? (foir70 / 1900).toFixed(2) : 0;
            const elig6_75 = foir75 > 0 ? (foir75 / 1900).toFixed(2) : 0;
            const elig6_80 = foir80 > 0 ? (foir80 / 1900).toFixed(2) : 0;
            
            // 7 Years eligibility
            const elig7_50 = foir50 > 0 ? (foir50 / 1650).toFixed(2) : 0;
            const elig7_60 = foir60 > 0 ? (foir60 / 1650).toFixed(2) : 0;
            const elig7_70 = foir70 > 0 ? (foir70 / 1650).toFixed(2) : 0;
            const elig7_75 = foir75 > 0 ? (foir75 / 1650).toFixed(2) : 0;
            const elig7_80 = foir80 > 0 ? (foir80 / 1650).toFixed(2) : 0;

            // Update 5 Years row
            document.getElementById('elig5_1').textContent = '₹' + elig5_50 + 'L';
            document.getElementById('elig5_2').textContent = '₹' + elig5_60 + 'L';
            document.getElementById('elig5_3').textContent = '₹' + elig5_70 + 'L';
            document.getElementById('elig5_4').textContent = '₹' + elig5_75 + 'L';
            document.getElementById('elig5_5').textContent = '₹' + elig5_80 + 'L';
            
            // Update 6 Years row
            document.getElementById('elig6_1').textContent = '₹' + elig6_50 + 'L';
            document.getElementById('elig6_2').textContent = '₹' + elig6_60 + 'L';
            document.getElementById('elig6_3').textContent = '₹' + elig6_70 + 'L';
            document.getElementById('elig6_4').textContent = '₹' + elig6_75 + 'L';
            document.getElementById('elig6_5').textContent = '₹' + elig6_80 + 'L';
            
            // Update 7 Years row
            document.getElementById('elig7_1').textContent = '₹' + elig7_50 + 'L';
            document.getElementById('elig7_2').textContent = '₹' + elig7_60 + 'L';
            document.getElementById('elig7_3').textContent = '₹' + elig7_70 + 'L';
            document.getElementById('elig7_4').textContent = '₹' + elig7_75 + 'L';
            document.getElementById('elig7_5').textContent = '₹' + elig7_80 + 'L';
        }

        function calculateBankEligibility() {
            const el = id => document.getElementById(id);
            const num = id => parseFloat((el(id)?.value || '0').replace(/,/g, '')) || 0;
            const salary=num('netSalary'), loan=num('requestedLoan'), age=num('customerAge'), score=num('cibilScore');
            const profile=el('employerProfile')?.value||'listed', type=el('cibilType')?.value||'normal';
            const total=parseFloat((el('totalObl')?.textContent||'0').replace(/[₹,]/g,''))||0, foir=salary?total/salary:0;
            const employment=num('employmentMonths'), experience=num('experienceMonths'), enq1=num('enq1'), enq3=num('enq3'), live=num('livePl');
            const payslip=el('payslip')?.value==='available', pg=el('pgAccommodation')?.value==='yes', address=el('addressProof')?.value==='available';
            let eligible=0;
            const results=BANK_POLICIES.map(b=>{const bad=[], review=[]; const minSalary=profile==='listed'?b.salaryListed:b.salaryUnlisted, minCibil=profile==='listed'?b.cibilListed:b.cibilUnlisted;
                if(!loan||loan<b.min||loan>b.max) bad.push(loan>b.max?'Requested loan above bank maximum':'Requested loan below bank minimum');
                if(!salary||salary<minSalary) bad.push('Salary below policy minimum');
                if(type!=='normal'&&!b.zeroOne) bad.push('CIBIL 0/-1 not allowed');
                if(type==='normal'&&(!score||score<minCibil)) bad.push('CIBIL score below policy minimum');
                if(!age||age<b.ageMin||age>b.ageMax) bad.push('Age outside policy range');
                if(b.profile==='listed'&&profile!=='listed') bad.push('Unlisted employer not accepted');
                if(b.employment&&employment<b.employment) bad.push('Current employment below minimum');
                if(b.experience&&experience<b.experience) bad.push('Total experience below minimum');
                if(foir>b.foir) bad.push('FOIR above policy limit');
                if(b.enq1!==null&&enq1>b.enq1) bad.push('1-month enquiries above limit'); if(b.enq3!==null&&enq3>b.enq3) bad.push('3-month enquiries above limit');
                if(b.livePl!==null&&live>b.livePl) bad.push('Live PL count above limit'); if(b.payslip==='required'&&!payslip) bad.push('Payslip required');
                if(b.pg==='not_allowed'&&pg) bad.push('PG accommodation not accepted'); if(b.pg==='review'&&pg) review.push('PA/CPV may be required for PG'); if(b.address==='required'&&!address) bad.push('Current address proof required');
                const status=bad.length?'NOT ELIGIBLE':(review.length?'REVIEW REQUIRED':'ELIGIBLE'); if(status==='ELIGIBLE') eligible++; return {...b,status,reason:[...bad,...review].join('; ')||'All entered checks passed'};
            }).sort((a,b)=>({ELIGIBLE:1,'REVIEW REQUIRED':2,'NOT ELIGIBLE':3}[a.status]-({ELIGIBLE:1,'REVIEW REQUIRED':2,'NOT ELIGIBLE':3}[b.status])));
            const body=el('bankEligibilityTable')?.querySelector('tbody'); if(!body)return; body.innerHTML=results.map((b,i)=>`<tr><td>${i+1}</td><td><strong>${b.name}</strong></td><td><span class="badge ${b.status==='ELIGIBLE'?'bg-success':b.status==='REVIEW REQUIRED'?'bg-warning text-dark':'bg-danger'}">${b.status}</span></td><td>${b.reason}</td><td>₹${b.min.toLocaleString('en-IN')} - ₹${b.max.toLocaleString('en-IN')}</td><td>${b.roi}</td><td>${b.tenure}</td><td>${Math.round(b.foir*100)}%</td></tr>`).join(''); el('eligibleCount').textContent=eligible+' eligible';
        }

        function addEMIRow() {
            emiRowCount++;
            const table = document.getElementById('emiTable');
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" class="form-control form-control-sm" id="bank${emiRowCount}" oninput="calculateObligations()"></td>
                <td><select class="form-select form-select-sm" id="type${emiRowCount}" onchange="handleLoanTypeChange(${emiRowCount})"><option value="">Select</option><option value="HL">HL</option><option value="PL">PL</option><option value="AL">AL</option><option value="EL">EL</option><option value="PRTY">PRTY</option><option value="CD">CD</option></select></td>
                <td><input type="text" class="form-control form-control-sm" id="total${emiRowCount}" oninput="formatNumber(this); calculateObligations()"></td>
                <td><input type="text" class="form-control form-control-sm" id="out${emiRowCount}" oninput="formatNumber(this); calculateObligations()"></td>
                <td><input type="number" step="0.01" class="form-control form-control-sm" id="irr${emiRowCount}" value="0" oninput="calculateObligations()"></td>
                <td><input type="text" class="form-control form-control-sm" id="emiamt${emiRowCount}" oninput="formatNumber(this); calculateObligations()"></td>
                <td><select class="form-select form-select-sm" id="bt${emiRowCount}" disabled onchange="calculateObligations()"><option>No</option><option>Yes</option><option>Closed</option></select></td>
            `;
        }

        function addCCRow() {
            ccRowCount++;
            const table = document.getElementById('ccTable');
            const row = table.insertRow();
            row.innerHTML = `
                <td><input type="text" class="form-control form-control-sm" oninput="calculateObligations()"></td>
                <td><select class="form-select form-select-sm" id="ccgl${ccRowCount}" onchange="handleCCGLChange(${ccRowCount})"><option value="">Select</option><option value="CC">CC</option><option value="GL">GL</option></select></td>
                <td><input type="text" class="form-control form-control-sm" oninput="formatNumber(this); calculateObligations()"></td>
                <td><input type="text" class="form-control form-control-sm" oninput="formatNumber(this); calculateObligations()"></td>
                <td><input type="text" class="form-control form-control-sm" readonly></td>
                <td><select class="form-select form-select-sm" id="ccbt${ccRowCount}" disabled onchange="calculateObligations()"><option>No</option><option>Yes</option><option>Closed</option></select></td>
            `;
        }

        function removeEMIRow() {
            if (emiRowCount > 1) {
                const table = document.getElementById('emiTable');
                table.deleteRow(-1);
                emiRowCount--;
                calculateObligations();
            }
        }

        function removeCCRow() {
            if (ccRowCount > 1) {
                const table = document.getElementById('ccTable');
                table.deleteRow(-1);
                ccRowCount--;
                calculateObligations();
            }
        }

        calculate();
        calculateObligations();
        calculateBankEligibility();

        function formatAndCalculateBajaj(input) {
            formatNumber(input);
            calculateBajajEMI();
        }

        function calculateBajajEMI() {
            const loanAmountStr = document.getElementById('bajajLoanAmount').value.replace(/,/g, '');
            const loanAmount = parseFloat(loanAmountStr) || 0;
            const irr = parseFloat(document.getElementById('bajajIRR').value) || 0;
            
            // Calculate monthly interest
            const monthlyInterestRate = irr / 100 / 12;
            const monthlyInterest = loanAmount * monthlyInterestRate;
            
            document.getElementById('monthlyInterest').textContent = '₹' + Math.round(monthlyInterest).toLocaleString('en-IN');
            
            // Set EMI 2 to monthly interest
            document.getElementById('emi2').value = Math.round(monthlyInterest).toLocaleString('en-IN');
            
            calculateComparison();
        }

        function calculateForeclosure() {
            const rows = document.querySelectorAll('#foreclosureTable tr');
            if (rows.length) {
                let overallTotal = 0;
                rows.forEach(row => {
                    const rowNumber = row.dataset.foreclosureRow;
                    const loan = getNumericValue(document.getElementById('fcLoan' + rowNumber));
                    const rate = parseFloat(document.getElementById('fcRate' + rowNumber).value) || 0;
                    const totalAmount = loan > 0 && rate > 0 ? loan * (rate / 100) * 1.18 : 0;
                    document.getElementById('fcAmount' + rowNumber).textContent = String.fromCharCode(8377) + Math.round(totalAmount).toLocaleString('en-IN');
                    if (document.getElementById('fcInclude' + rowNumber).value === 'yes') overallTotal += totalAmount;
                });
                document.getElementById('fcOverallTotal').textContent = String.fromCharCode(8377) + Math.round(overallTotal).toLocaleString('en-IN');
                return;
            }
            for (let i = 1; i <= 2; i++) {
                const loanStr = document.getElementById('fcLoan' + i).value.replace(/,/g, '');
                const loan = parseFloat(loanStr) || 0;
                const rate = parseFloat(document.getElementById('fcRate' + i).value) || 0;
                
                if (loan > 0 && rate > 0) {
                    const foreclosureCharge = loan * (rate / 100);
                    const gst = foreclosureCharge * 0.18;
                    const totalAmount = foreclosureCharge + gst;
                    document.getElementById('fcAmount' + i).textContent = '₹' + Math.round(totalAmount).toLocaleString('en-IN');
                } else {
                    document.getElementById('fcAmount' + i).textContent = '₹0';
                }
            }
        }

        function setupForeclosureTable() {
            const table = document.getElementById('foreclosureTable');
            const footer = table.closest('table').createTFoot();
            footer.className = 'table-light';
            footer.innerHTML = '<tr><th colspan="3" class="text-end">Selected Calculations Total</th><th colspan="2" id="fcOverallTotal" class="text-start fw-bold" style="font-size: 1.1rem; color: #8B0000;">&#8377;0</th></tr>';

            table.querySelectorAll('tr').forEach((row, index) => {
                const rowNumber = index + 1;
                row.dataset.foreclosureRow = rowNumber;
                row.insertAdjacentHTML('beforeend', '<td><select class="form-select form-select-sm" id="fcInclude' + rowNumber + '" onchange="calculateForeclosure()"><option value="yes" selected>Yes</option><option value="no">No</option></select></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeForeclosureRow(' + rowNumber + ')" aria-label="Remove calculation"><i class="fas fa-trash"></i></button></td>');
            });
            fcRowCount = table.querySelectorAll('tr').length;
        }

        function addForeclosureRow(rowData = {}) {
            fcRowCount++;
            const table = document.getElementById('foreclosureTable');
            const row = table.insertRow();
            row.dataset.foreclosureRow = fcRowCount;
            row.innerHTML = '<td><div class="input-with-rupee"><input type="text" class="form-control form-control-sm" id="fcLoan' + fcRowCount + '" placeholder="0" oninput="formatNumber(this); calculateForeclosure()"></div></td><td><input type="number" class="form-control form-control-sm" id="fcRate' + fcRowCount + '" value="4" oninput="calculateForeclosure()" min="0"></td><td id="fcAmount' + fcRowCount + '" class="fw-bold" style="font-size: 1.1rem; color: #8B0000;">&#8377;0</td><td><select class="form-select form-select-sm" id="fcInclude' + fcRowCount + '" onchange="calculateForeclosure()"><option value="yes">Yes</option><option value="no">No</option></select></td><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeForeclosureRow(' + fcRowCount + ')" aria-label="Remove calculation"><i class="fas fa-trash"></i></button></td>';
            document.getElementById('fcLoan' + fcRowCount).value = rowData.loan || '';
            document.getElementById('fcRate' + fcRowCount).value = rowData.rate || '4';
            document.getElementById('fcInclude' + fcRowCount).value = rowData.include === 'no' ? 'no' : 'yes';
            calculateForeclosure();
        }

        function removeForeclosureRow(rowNumber) {
            const row = document.querySelector('#foreclosureTable tr[data-foreclosure-row="' + rowNumber + '"]');
            if (row) row.remove();
            calculateForeclosure();
        }

        setupForeclosureTable();
        calculateForeclosure();

        function calculateComparison() {
            const emi1 = getNumericValue(document.getElementById('emi1'));
            const emi2 = getNumericValue(document.getElementById('emi2'));
            
            const difference = emi1 - emi2;
            
            document.getElementById('difference').value = difference.toLocaleString('en-IN');
        }

        let currentCode = null;

        function getAllData() {
            const data = {
                customerName: document.getElementById('customerName').value,
                emiRows: [],
                ccRows: [],
                netSalary: document.getElementById('netSalary').value,
                emiLoanAmount: document.getElementById('emiLoanAmount').value,
                emiIRR: document.getElementById('emiIRR').value,
                bajajLoanAmount: document.getElementById('bajajLoanAmount').value,
                bajajIRR: document.getElementById('bajajIRR').value,
                emi1: document.getElementById('emi1').value,
                emi2: document.getElementById('emi2').value,
                foreclosure: []
            };
            data.foreclosure = Array.from(document.querySelectorAll('#foreclosureTable tr')).map(row => {
                const rowNumber = row.dataset.foreclosureRow;
                return {
                    loan: document.getElementById('fcLoan' + rowNumber).value,
                    rate: document.getElementById('fcRate' + rowNumber).value,
                    include: document.getElementById('fcInclude' + rowNumber).value
                };
            });
            
            for (let i = 1; i <= emiRowCount; i++) {
                data.emiRows.push({
                    bank: document.getElementById('bank' + i)?.value || '',
                    type: document.getElementById('type' + i)?.value || '',
                    total: document.getElementById('total' + i)?.value || '0',
                    out: document.getElementById('out' + i)?.value || '0',
                    irr: document.getElementById('irr' + i)?.value || '0',
                    emi: document.getElementById('emiamt' + i)?.value || '0',
                    bt: document.getElementById('bt' + i)?.value || 'No'
                });
            }
            
            const ccRows = document.querySelectorAll('#ccTable tr');
            ccRows.forEach((row, idx) => {
                const cells = row.querySelectorAll('input, select');
                if (cells.length >= 6) {
                    data.ccRows.push({
                        bank: cells[0].value || '',
                        ccgl: cells[1].value || '',
                        limit: cells[2].value || '0',
                        out: cells[3].value || '0',
                        five: cells[4].value || '0',
                        bt: cells[5].value || 'No'
                    });
                }
            });
            
            return data;
        }

        function setAllData(data) {
            // Clear existing rows
            while (emiRowCount > 1) removeEMIRow();
            while (ccRowCount > 1) removeCCRow();
            
            document.getElementById('customerName').value = data.customerName || '';
            
            // Set EMI rows
            data.emiRows.forEach((row, idx) => {
                if (idx > 0) addEMIRow();
                const i = idx + 1;
                document.getElementById('bank' + i).value = row.bank;
                document.getElementById('type' + i).value = row.type;
                document.getElementById('total' + i).value = row.total;
                document.getElementById('out' + i).value = row.out;
                document.getElementById('irr' + i).value = row.irr || '0';
                document.getElementById('emiamt' + i).value = row.emi;
                document.getElementById('bt' + i).value = row.bt;
                handleLoanTypeChange(i);
            });
            
            // Set CC rows
            data.ccRows.forEach((row, idx) => {
                if (idx > 0) addCCRow();
                const ccRow = document.querySelectorAll('#ccTable tr')[idx];
                const cells = ccRow.querySelectorAll('input, select');
                cells[0].value = row.bank;
                cells[1].value = row.ccgl;
                cells[2].value = row.limit;
                cells[3].value = row.out;
                cells[4].value = row.five;
                cells[5].value = row.bt;
                if (idx === 0) handleCCGLChange(1);
            });
            
            document.getElementById('netSalary').value = data.netSalary;
            document.getElementById('emiLoanAmount').value = data.emiLoanAmount;
            document.getElementById('emiIRR').value = data.emiIRR;
            document.getElementById('bajajLoanAmount').value = data.bajajLoanAmount;
            document.getElementById('bajajIRR').value = data.bajajIRR;
            document.getElementById('emi1').value = data.emi1;
            document.getElementById('emi2').value = data.emi2 || '0';
            
            // Set foreclosure data
            if (data.foreclosure && data.foreclosure.length) {
                const foreclosureTable = document.getElementById('foreclosureTable');
                foreclosureTable.innerHTML = '';
                fcRowCount = 0;
                data.foreclosure.forEach(row => addForeclosureRow(row));
            }
            
            calculateObligations();
            calculateBankEligibility();
            calculateEMITable();
            calculateBajajEMI();
            calculateForeclosure();
        }

        async function saveCalculator() {
            const customerName = document.getElementById('customerName').value.trim();
            if (!customerName) {
                alert('Please enter customer name');
                return;
            }
            const data = getAllData();
            const response = await fetch('calculator_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=save&data=' + encodeURIComponent(JSON.stringify(data))
            });
            const result = await response.json();
            if (result.success) {
                currentCode = result.code;
                document.getElementById('loadCode').value = result.code;
                document.getElementById('updateBtn').style.display = 'inline-block';
                alert('Saved successfully! Code: ' + result.code);
                return result.code;
            } else {
                alert('Error saving: ' + result.message);
            }
            return null;
        }

        async function generateCode() {
            const code = await saveCalculator();
            if (code) {
                alert('Generated Code: ' + code + '\n\nThis code has been saved and can be used to load this calculator.');
            }
        }

        async function loadCalculator() {
            const code = document.getElementById('loadCode').value.trim().toUpperCase();
            if (!code) {
                alert('Please enter a code');
                return;
            }
            
            const response = await fetch('calculator_api.php?action=load&code=' + code);
            const result = await response.json();
            
            if (result.success) {
                const data = JSON.parse(result.data);
                setAllData(data);
                currentCode = code;
                document.getElementById('updateBtn').style.display = 'inline-block';
                alert('Calculator loaded successfully!');
            } else {
                alert('Error: ' + result.message);
            }
        }

        async function updateCalculator() {
            if (!currentCode) {
                alert('No code loaded');
                return;
            }
            
            const data = getAllData();
            const response = await fetch('calculator_api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=update&code=' + currentCode + '&data=' + encodeURIComponent(JSON.stringify(data))
            });
            const result = await response.json();
            
            if (result.success) {
                alert('Calculator updated successfully!');
            } else {
                alert('Error: ' + result.message);
            }
        }

        async function downloadPDF() {
            const code = await saveCalculator();
            if (!code) {
                alert('Error saving calculator data');
                return;
            }
            
            const filename = prompt('Enter PDF filename:', 'EMI_Calculator');
            if (filename) {
                const element = document.getElementById('pdfContent');
                html2canvas(element, { scale: 2 }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const imgWidth = 210;
                    const imgHeight = (canvas.height * imgWidth) / canvas.width;
                    pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
                    
                    // Add code to PDF
                    pdf.setFontSize(12);
                    pdf.setTextColor(255, 0, 0);
                    pdf.text('Code: ' + code, 10, imgHeight + 10);
                    
                    pdf.save(filename + '.pdf');
                    alert('PDF downloaded with code: ' + code);
                });
            }
        }

        function downloadCSV() {
            const filename = prompt('Enter CSV filename:', 'EMI_Calculator');
            if (!filename) return;
            
            let csv = [];
            
            // Helper function to clean text
            const clean = (text) => {
                if (!text) return '';
                return text.toString().replace(/₹/g, 'Rs.').replace(/,/g, '');
            };
            
            // EMI Details
            csv.push(['EMI Details']);
            csv.push(['Bank', 'Loan Type', 'Total Loan', 'Outstanding', 'EMI', 'BT']);
            for (let i = 1; i <= emiRowCount; i++) {
                const bank = document.getElementById('bank' + i)?.value || '';
                const type = document.getElementById('type' + i)?.value || '';
                const total = document.getElementById('total' + i)?.value || '0';
                const out = document.getElementById('out' + i)?.value || '0';
                const emi = document.getElementById('emiamt' + i)?.value || '0';
                const bt = document.getElementById('bt' + i)?.value || 'No';
                csv.push([bank, type, total, out, emi, bt]);
            }
            csv.push([]);
            
            // CC/GL Details
            csv.push(['CC/GL Details']);
            csv.push(['Bank', 'CC/GL', 'Total Limit', 'Outstanding', 'CC-5% GL-1%', 'BT']);
            const ccRows = document.querySelectorAll('#ccTable tr');
            ccRows.forEach(row => {
                const cells = row.querySelectorAll('input, select');
                if (cells.length >= 6) {
                    csv.push([cells[0].value || '', cells[1].value || '', cells[2].value || '0', cells[3].value || '0', cells[4].value || '0', cells[5].value || 'No']);
                }
            });
            csv.push([]);
            
            // Obligations
            csv.push(['Obligations & Eligibility']);
            csv.push(['Loan Obligations', clean(document.getElementById('loanObl').textContent)]);
            csv.push(['CC Obligations', clean(document.getElementById('ccObl').textContent)]);
            csv.push(['Total Obligations', clean(document.getElementById('totalObl').textContent)]);
            csv.push(['Total BT Amount', clean(document.getElementById('totalBT').textContent)]);
            csv.push(['Net Salary', document.getElementById('netSalary').value]);
            csv.push([]);
            
            // Eligibility
            csv.push(['Loan Eligibility']);
            csv.push(['Tenure', '<50k (50%)', '60-75K (60%)', '>75k (70%)', '>75K + HL (75%)', '>1 Lakhs + HL (80%)']);
            csv.push(['5 Years', clean(document.getElementById('elig5_1').textContent), clean(document.getElementById('elig5_2').textContent), clean(document.getElementById('elig5_3').textContent), clean(document.getElementById('elig5_4').textContent), clean(document.getElementById('elig5_5').textContent)]);
            csv.push(['6 Years', clean(document.getElementById('elig6_1').textContent), clean(document.getElementById('elig6_2').textContent), clean(document.getElementById('elig6_3').textContent), clean(document.getElementById('elig6_4').textContent), clean(document.getElementById('elig6_5').textContent)]);
            csv.push(['7 Years', clean(document.getElementById('elig7_1').textContent), clean(document.getElementById('elig7_2').textContent), clean(document.getElementById('elig7_3').textContent), clean(document.getElementById('elig7_4').textContent), clean(document.getElementById('elig7_5').textContent)]);
            csv.push([]);
            
            // EMI Calculator
            csv.push(['EMI Calculator']);
            csv.push(['Loan Amount', document.getElementById('emiLoanAmount').value]);
            csv.push(['IRR (%)', document.getElementById('emiIRR').value]);
            csv.push(['Months', '12', '24', '36', '48', '60', '72', '84']);
            csv.push(['Years', '1', '2', '3', '4', '5', '6', '7']);
            csv.push(['EMI', clean(document.getElementById('emi_12').textContent), clean(document.getElementById('emi_24').textContent), clean(document.getElementById('emi_36').textContent), clean(document.getElementById('emi_48').textContent), clean(document.getElementById('emi_60').textContent), clean(document.getElementById('emi_72').textContent), clean(document.getElementById('emi_84').textContent)]);
            csv.push(['Total Amount', clean(document.getElementById('total_12').textContent), clean(document.getElementById('total_24').textContent), clean(document.getElementById('total_36').textContent), clean(document.getElementById('total_48').textContent), clean(document.getElementById('total_60').textContent), clean(document.getElementById('total_72').textContent), clean(document.getElementById('total_84').textContent)]);
            csv.push(['Principal Amount', clean(document.getElementById('principal_12').textContent), clean(document.getElementById('principal_24').textContent), clean(document.getElementById('principal_36').textContent), clean(document.getElementById('principal_48').textContent), clean(document.getElementById('principal_60').textContent), clean(document.getElementById('principal_72').textContent), clean(document.getElementById('principal_84').textContent)]);
            csv.push(['Interest Amount', clean(document.getElementById('interest_12').textContent), clean(document.getElementById('interest_24').textContent), clean(document.getElementById('interest_36').textContent), clean(document.getElementById('interest_48').textContent), clean(document.getElementById('interest_60').textContent), clean(document.getElementById('interest_72').textContent), clean(document.getElementById('interest_84').textContent)]);
            csv.push([]);
            
            // BAJAJ EMI Calculator
            csv.push(['BAJAJ EMI Calculator']);
            csv.push(['Loan Amount', document.getElementById('bajajLoanAmount').value]);
            csv.push(['IRR (%)', document.getElementById('bajajIRR').value]);
            csv.push(['Monthly Interest', clean(document.getElementById('monthlyInterest').textContent)]);
            csv.push(['EMI 1', document.getElementById('emi1').value]);
            csv.push(['EMI 2', document.getElementById('emi2').value]);
            csv.push(['Difference', document.getElementById('difference').value]);
            csv.push([]);
            
            // Foreclosure Charges
            csv.push(['Foreclosure Charges']);
            csv.push(['Current Principal Outstanding Amount', 'Foreclosure Charges (%)', 'Amount (% + 18% GST)', 'Include in Total']);
            document.querySelectorAll('#foreclosureTable tr').forEach(row => {
                const rowNumber = row.dataset.foreclosureRow;
                csv.push([
                    document.getElementById('fcLoan' + rowNumber).value,
                    document.getElementById('fcRate' + rowNumber).value,
                    clean(document.getElementById('fcAmount' + rowNumber).textContent),
                    document.getElementById('fcInclude' + rowNumber).value === 'yes' ? 'Yes' : 'No'
                ]);
            });
            csv.push(['Selected Calculations Total', '', clean(document.getElementById('fcOverallTotal').textContent), '']);
            
            // Convert to CSV string
            const csvContent = csv.map(row => row.join(',')).join('\n');
            
            // Download with UTF-8 BOM for proper encoding
            const BOM = '\uFEFF';
            const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
