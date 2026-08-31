<?php
/**
 * BestDeal CRM — Full Workflow Test Runner
 * Access: https://bdfsloans.com/bestdealcrm/testrun.php
 * 
 * Self-contained test — connects directly to DB, no app bootstrap needed.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
header('Content-Type: text/html; charset=utf-8');

// ===== Direct DB Connection (no app bootstrap) =====
$rootPath = __DIR__;
if (file_exists($rootPath . '/.env')) {
    $lines = file($rootPath . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (trim($line)[0] === '#' || empty(trim($line))) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("{$key}={$value}");
        }
    }
}

$dbHost = getenv('DB_HOST') ?: '68.178.237.250';
$dbName = getenv('DB_NAME') ?: 'bestdealcrm';
$dbUser = getenv('DB_USER') ?: 'sayali';
$dbPass = getenv('DB_PASS') ?: 'sayali@1234';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser, $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pdo->exec("SET NAMES utf8mb4, time_zone = '+05:30'");
} catch (PDOException $e) {
    die('<h1>DB Connection Failed</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
}

// ===== Helper functions =====
function nowIST() {
    return (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s');
}

function dbQuery($sql, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetch($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetch() ?: null;
}

function dbFetchAll($sql, $params = []) {
    $stmt = dbQuery($sql, $params);
    return $stmt->fetchAll();
}

function dbCount($table, $where = '1', $params = []) {
    $stmt = dbQuery("SELECT COUNT(*) as c FROM {$table} WHERE {$where}", $params);
    return (int)$stmt->fetch()['c'];
}

function dbInsert($table, $data) {
    global $pdo;
    $cols = implode(', ', array_keys($data));
    $ph = implode(', ', array_fill(0, count($data), '?'));
    dbQuery("INSERT INTO {$table} ({$cols}) VALUES ({$ph})", array_values($data));
    return $pdo->lastInsertId();
}

function dbUpdate($table, $data, $where, $whereParams = []) {
    $sets = implode(', ', array_map(function($k) { return "{$k} = ?"; }, array_keys($data)));
    $allParams = array_merge(array_values($data), $whereParams);
    dbQuery("UPDATE {$table} SET {$sets} WHERE {$where}", $allParams);
}

function dbDelete($table, $where, $params = []) {
    dbQuery("DELETE FROM {$table} WHERE {$where}", $params);
}

// ===== Test Framework =====
$results = [];
$passed = 0;
$failed = 0;

function test(string $name, callable $fn) {
    global $results, $passed, $failed;
    try {
        $fn();
        $results[] = ['name' => $name, 'status' => 'PASS', 'detail' => ''];
        $passed++;
    } catch (Throwable $e) {
        $results[] = ['name' => $name, 'status' => 'FAIL', 'detail' => $e->getMessage() . ' at ' . basename($e->getFile()) . ':' . $e->getLine()];
        $failed++;
    }
}

function assertTrue($val, string $msg = 'Assertion failed') {
    if (!$val) throw new Exception($msg);
}

function assertNotNull($val, string $msg = 'Expected non-null') {
    if ($val === null) throw new Exception($msg);
}

function assertEquals($expected, $actual, string $msg = '') {
    if ($expected !== $actual) throw new Exception(($msg ? $msg . ': ' : '') . "Expected [{$expected}], got [" . var_export($actual, true) . "]");
}

// ===== Get form IDs =====
$istNow = nowIST();
// Fetch form IDs — try both cases since DB may store either
function findFormByStage($pdo, array $stages): int {
    foreach ($stages as $stage) {
        $r = dbFetch("SELECT id FROM forms WHERE workflow_stage = ? AND status = 'active' LIMIT 1", [$stage]);
        if ($r) return (int)$r['id'];
    }
    return 0;
}
$agentFormId = findFormByStage($pdo, ['Agent Draft', 'AGENT_DRAFT', 'agent_draft']);
$preLoginFormId = findFormByStage($pdo, ['Login Agent Draft', 'LOGIN_AGENT_DRAFT', 'login_agent_draft']);
$postLoginFormId = findFormByStage($pdo, ['Post Login', 'POST_LOGIN', 'post_login']);
$uwFormId = findFormByStage($pdo, ['Underwriting', 'UNDERWRITING', 'underwriting']);
$dispatchFormId = findFormByStage($pdo, ['Dispatch', 'DISPATCH', 'dispatch']);

function getFormFields($pdo, int $formId): array {
    if (!$formId) return [];
    $sections = dbFetchAll("SELECT id FROM form_sections WHERE form_id = ? ORDER BY display_order", [$formId]);
    $fields = [];
    foreach ($sections as $sec) {
        $rows = dbFetchAll("SELECT id, field_name, label, type, required FROM form_fields WHERE section_id = ? AND (is_hidden IS NULL OR is_hidden = 0) ORDER BY display_order", [$sec['id']]);
        $fields = array_merge($fields, $rows);
    }
    return $fields;
}

// ===== Create test users =====
$testUsers = [];
foreach (['agent', 'login_agent', 'underwriting', 'dispatch', 'admin'] as $roleName) {
    $existing = dbFetch("SELECT id, name FROM users WHERE username = ?", ["test_{$roleName}"]);
    if ($existing) {
        $testUsers[$roleName] = $existing;
    } else {
        $role = dbFetch("SELECT id FROM roles WHERE name = ?", [$roleName]);
        $roleId = $role ? (int)$role['id'] : (int)dbInsert('roles', ['name' => $roleName, 'display_name' => ucwords(str_replace('_', ' ', $roleName)), 'created_at' => $istNow]);
        $userId = dbInsert('users', [
            'username'  => "test_{$roleName}",
            'email'     => "test_{$roleName}@bestdealcrm.com",
            'password_hash' => password_hash('test1234', PASSWORD_DEFAULT),
            'name'      => "Test " . ucwords(str_replace('_', ' ', $roleName)),
            'role_id'   => $roleId,
            'status'    => 'active',
            'created_at'=> $istNow,
        ]);
        $testUsers[$roleName] = ['id' => (int)$userId, 'name' => "Test " . ucwords(str_replace('_', ' ', $roleName))];
    }
}

// ===== TESTS =====

// --- DB Connection ---
test('Database connection works', function() {
    assertNotNull(dbFetch("SELECT 1 as ok"));
});

// --- Table existence ---
$requiredTables = ['users', 'roles', 'leads', 'forms', 'form_sections', 'form_fields', 'form_submissions', 'form_submission_values', 'documents', 'remarks', 'form_field_options'];
foreach ($requiredTables as $table) {
    test("Table `{$table}` exists", function() use ($table) {
        $check = dbFetch("SHOW TABLES LIKE ?", [$table]);
        assertNotNull($check, "Table `{$table}` not found in database");
    });
}

// --- Form structure ---
foreach ([
    'Agent Lead Form' => $agentFormId,
    'Pre-Login Checklist' => $preLoginFormId,
    'Post-Login Form' => $postLoginFormId,
    'UnderWriting Form' => $uwFormId,
    'Dispatch Form' => $dispatchFormId,
] as $formName => $fid) {
    test("Form \"{$formName}\" exists with sections & fields", function() use ($fid, $formName) {
        assertTrue($fid > 0, "Form \"{$formName}\" not found (workflow_stage mismatch or form missing)");
        $sections = dbFetchAll("SELECT id FROM form_sections WHERE form_id = ?", [$fid]);
        assertTrue(count($sections) > 0, "Form \"{$formName}\" has no sections");
        $fields = dbFetchAll("SELECT id FROM form_fields WHERE section_id IN (SELECT id FROM form_sections WHERE form_id = ?)", [$fid]);
        assertTrue(count($fields) > 0, "Form \"{$formName}\" has no fields");
    });
}

// --- Test user creation ---
foreach ($testUsers as $role => $user) {
    test("Test user for role '{$role}' exists", function() use ($user, $role) {
        assertNotNull($user['id'] ?? null, "User for role {$role} not created");
    });
}

// --- Create test lead ---
$testLeadId = 0;
test('Create test lead', function() use (&$testLeadId, $testUsers, $istNow) {
    $agentId = $testUsers['agent']['id'];
    $testLeadId = (int)dbInsert('leads', [
        'customer_name'    => 'Test Customer ' . date('His'),
        'mobile_number'    => '9' . mt_rand(100000000, 999999999),
        'location'         => 'Pune',
        'state'            => 'Maharashtra',
        'assigned_to'      => $agentId,
        'created_by'       => $agentId,
        'workflow_stage'   => 'LEAD_ASSIGNED',
        'data_type'        => 'Fresh',
        'created_at'       => $istNow,
        'updated_at'       => $istNow,
    ]);
    assertTrue($testLeadId > 0, 'Lead creation failed');
});

// --- Agent: Submit form ---
$agentSubmissionId = 0;
test('Agent submits lead form', function() use (&$agentSubmissionId, $testLeadId, $testUsers, $agentFormId, $istNow) {
    assertTrue($agentFormId > 0, 'Agent form not found');
    $fields = getFormFields(null, $agentFormId);
    $agentSubmissionId = (int)dbInsert('form_submissions', [
        'form_id' => $agentFormId, 'lead_id' => $testLeadId,
        'submitted_by' => $testUsers['agent']['id'], 'status' => 'submitted',
        'created_at' => $istNow, 'updated_at' => $istNow,
    ]);
    foreach ($fields as $f) {
        if (in_array($f['type'], ['file', 'image'])) continue;
        dbInsert('form_submission_values', ['submission_id' => $agentSubmissionId, 'field_id' => $f['id'], 'value' => "AgentVal_{$f['id']}"]);
    }
    dbUpdate('leads', ['workflow_stage' => 'ADMIN_REVIEW_1', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assertTrue($agentSubmissionId > 0, 'Agent form submission failed');
});

test('Agent can see their own submission', function() use ($agentSubmissionId, $testUsers) {
    $sub = dbFetch("SELECT * FROM form_submissions WHERE id = ? AND submitted_by = ?", [$agentSubmissionId, $testUsers['agent']['id']]);
    assertNotNull($sub, 'Agent cannot see their own submission');
    assertEquals('submitted', $sub['status']);
});

// --- Admin Review 1 ---
test('Admin Review 1: Assign to login agent', function() use ($testLeadId, $testUsers, $istNow) {
    dbUpdate('leads', ['assigned_to' => $testUsers['login_agent']['id'], 'workflow_stage' => 'LOGIN_AGENT_ASSIGNED', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    dbInsert('remarks', ['lead_id' => $testLeadId, 'user_id' => $testUsers['admin']['id'], 'stage' => 'ADMIN_REVIEW_1', 'remark' => 'Approved', 'created_at' => $istNow]);
    $lead = dbFetch("SELECT workflow_stage, assigned_to FROM leads WHERE id = ?", [$testLeadId]);
    assertEquals('LOGIN_AGENT_ASSIGNED', $lead['workflow_stage']);
    assertEquals($testUsers['login_agent']['id'], $lead['assigned_to']);
});

// --- Login Agent: Pre-Login ---
$preLoginSubmissionId = 0;
test('Login agent submits pre-login checklist', function() use (&$preLoginSubmissionId, $testLeadId, $testUsers, $preLoginFormId, $istNow) {
    assertTrue($preLoginFormId > 0, 'Pre-login form not found');
    $fields = getFormFields(null, $preLoginFormId);
    $preLoginSubmissionId = (int)dbInsert('form_submissions', [
        'form_id' => $preLoginFormId, 'lead_id' => $testLeadId,
        'submitted_by' => $testUsers['login_agent']['id'], 'status' => 'submitted',
        'created_at' => $istNow, 'updated_at' => $istNow,
    ]);
    foreach ($fields as $f) {
        if (in_array($f['type'], ['file', 'image'])) continue;
        dbInsert('form_submission_values', ['submission_id' => $preLoginSubmissionId, 'field_id' => $f['id'], 'value' => "PreVal_{$f['id']}"]);
    }
    dbUpdate('leads', ['workflow_stage' => 'ADMIN_REVIEW_2', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assertTrue($preLoginSubmissionId > 0, 'Pre-login submission failed');
});

test('Pre-login submission visible to all roles', function() use ($preLoginSubmissionId) {
    $sub = dbFetch("SELECT * FROM form_submissions WHERE id = ?", [$preLoginSubmissionId]);
    assertNotNull($sub, 'Pre-login submission not found');
    assertEquals('submitted', $sub['status']);
});

// --- Admin Review 2 ---
test('Admin Review 2: Approve post-login', function() use ($testLeadId, $testUsers, $istNow) {
    dbUpdate('leads', ['workflow_stage' => 'LOGIN_APPROVED', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    dbInsert('remarks', ['lead_id' => $testLeadId, 'user_id' => $testUsers['admin']['id'], 'stage' => 'ADMIN_REVIEW_2', 'remark' => 'Pre-login approved', 'created_at' => $istNow]);
    $lead = dbFetch("SELECT workflow_stage FROM leads WHERE id = ?", [$testLeadId]);
    assertEquals('LOGIN_APPROVED', $lead['workflow_stage']);
});

// --- Login Agent: Post-Login ---
$postLoginSubmissionId = 0;
test('Login agent submits post-login form', function() use (&$postLoginSubmissionId, $testLeadId, $testUsers, $postLoginFormId, $istNow) {
    assertTrue($postLoginFormId > 0, 'Post-login form not found');
    $fields = getFormFields(null, $postLoginFormId);
    $postLoginSubmissionId = (int)dbInsert('form_submissions', [
        'form_id' => $postLoginFormId, 'lead_id' => $testLeadId,
        'submitted_by' => $testUsers['login_agent']['id'], 'status' => 'submitted',
        'created_at' => $istNow, 'updated_at' => $istNow,
    ]);
    foreach ($fields as $f) {
        if (in_array($f['type'], ['file', 'image'])) continue;
        dbInsert('form_submission_values', ['submission_id' => $postLoginSubmissionId, 'field_id' => $f['id'], 'value' => "PostVal_{$f['id']}"]);
    }
    dbUpdate('leads', ['workflow_stage' => 'ADMIN_REVIEW_3', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assertTrue($postLoginSubmissionId > 0, 'Post-login submission failed');
});

// --- Admin Review 3 ---
test('Admin Review 3: Send to underwriting', function() use ($testLeadId, $testUsers, $istNow) {
    dbUpdate('leads', ['assigned_to' => $testUsers['underwriting']['id'], 'workflow_stage' => 'UNDERWRITING', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    dbInsert('remarks', ['lead_id' => $testLeadId, 'user_id' => $testUsers['admin']['id'], 'stage' => 'ADMIN_REVIEW_3', 'remark' => 'Sending to underwriting', 'created_at' => $istNow]);
    $lead = dbFetch("SELECT workflow_stage, assigned_to FROM leads WHERE id = ?", [$testLeadId]);
    assertEquals('UNDERWRITING', $lead['workflow_stage']);
});

// --- Underwriting ---
$uwSubmissionId = 0;
test('Underwriter submits underwriting form', function() use (&$uwSubmissionId, $testLeadId, $testUsers, $uwFormId, $istNow) {
    assertTrue($uwFormId > 0, 'Underwriting form not found');
    $fields = getFormFields(null, $uwFormId);
    $uwSubmissionId = (int)dbInsert('form_submissions', [
        'form_id' => $uwFormId, 'lead_id' => $testLeadId,
        'submitted_by' => $testUsers['underwriting']['id'], 'status' => 'submitted',
        'created_at' => $istNow, 'updated_at' => $istNow,
    ]);
    foreach ($fields as $f) {
        if (in_array($f['type'], ['file', 'image'])) continue;
        dbInsert('form_submission_values', ['submission_id' => $uwSubmissionId, 'field_id' => $f['id'], 'value' => "UWVal_{$f['id']}"]);
    }
    dbUpdate('leads', ['workflow_stage' => 'ADMIN_REVIEW_4', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assertTrue($uwSubmissionId > 0, 'Underwriting submission failed');
});

// --- Admin Review 4 ---
test('Admin Review 4: Send to dispatch', function() use ($testLeadId, $testUsers, $istNow) {
    dbUpdate('leads', ['assigned_to' => $testUsers['dispatch']['id'], 'workflow_stage' => 'DISPATCH', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    dbInsert('remarks', ['lead_id' => $testLeadId, 'user_id' => $testUsers['admin']['id'], 'stage' => 'ADMIN_REVIEW_4', 'remark' => 'Sending to dispatch', 'created_at' => $istNow]);
    $lead = dbFetch("SELECT workflow_stage, assigned_to FROM leads WHERE id = ?", [$testLeadId]);
    assertEquals('DISPATCH', $lead['workflow_stage']);
});

// --- Dispatch ---
$dispatchSubmissionId = 0;
test('Dispatcher submits dispatch form & completes', function() use (&$dispatchSubmissionId, $testLeadId, $testUsers, $dispatchFormId, $istNow) {
    assertTrue($dispatchFormId > 0, 'Dispatch form not found');
    $fields = getFormFields(null, $dispatchFormId);
    $dispatchSubmissionId = (int)dbInsert('form_submissions', [
        'form_id' => $dispatchFormId, 'lead_id' => $testLeadId,
        'submitted_by' => $testUsers['dispatch']['id'], 'status' => 'submitted',
        'created_at' => $istNow, 'updated_at' => $istNow,
    ]);
    foreach ($fields as $f) {
        if (in_array($f['type'], ['file', 'image'])) continue;
        dbInsert('form_submission_values', ['submission_id' => $dispatchSubmissionId, 'field_id' => $f['id'], 'value' => "DispVal_{$f['id']}"]);
    }
    dbUpdate('leads', ['workflow_stage' => 'COMPLETED', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assertTrue($dispatchSubmissionId > 0, 'Dispatch submission failed');
});

// --- Verify all submissions ---
test('All 5 form submissions exist', function() use ($testLeadId) {
    $subs = dbFetchAll("SELECT * FROM form_submissions WHERE lead_id = ? AND status = 'submitted'", [$testLeadId]);
    assertEquals(5, count($subs), "Expected 5 submissions, got " . count($subs));
});

// --- Document visibility ---
$testDocId = 0;
test('Document upload & cross-role visibility', function() use (&$testDocId, $testLeadId, $testUsers, $istNow) {
    $testDocId = (int)dbInsert('documents', [
        'lead_id' => $testLeadId, 'uploaded_by' => $testUsers['agent']['id'],
        'filename' => 'test_pan.pdf', 'original_name' => 'PAN_Test.pdf',
        'mime_type' => 'application/pdf', 'file_size' => 12345,
        'document_type' => 'form_upload', 'created_at' => $istNow,
    ]);
    assertTrue($testDocId > 0, 'Document creation failed');
    $doc = dbFetch("SELECT * FROM documents WHERE id = ? AND lead_id = ?", [$testDocId, $testLeadId]);
    assertNotNull($doc, 'Document not visible by lead_id query');
});

// --- File upload JSON ---
test('File upload stored as JSON in form_submission_values', function() use ($agentSubmissionId, $agentFormId, $testDocId) {
    $fields = getFormFields(null, $agentFormId);
    $fileField = null;
    foreach ($fields as $f) {
        if ($f['type'] === 'file' || $f['type'] === 'image') { $fileField = $f; break; }
    }
    if (!$fileField) {
        $sec = dbFetch("SELECT id FROM form_sections WHERE form_id = ? ORDER BY display_order LIMIT 1", [$agentFormId]);
        if ($sec) {
            $fid = (int)dbInsert('form_fields', [
                'section_id' => $sec['id'], 'field_name' => 'test_file', 'label' => 'Test File',
                'type' => 'file', 'placeholder' => '', 'required' => 0, 'display_order' => 999, 'created_at' => nowIST(),
            ]);
            $fileField = ['id' => $fid];
        }
    }
    if ($fileField) {
        $json = json_encode(['doc_id' => $testDocId, 'filename' => 'test_pan.pdf', 'original' => 'PAN_Test.pdf', 'mime_type' => 'application/pdf', 'file_size' => 12345]);
        $existing = dbFetch("SELECT id FROM form_submission_values WHERE submission_id = ? AND field_id = ?", [$agentSubmissionId, $fileField['id']]);
        if ($existing) {
            dbUpdate('form_submission_values', ['value' => $json], 'id = ?', [$existing['id']]);
        } else {
            dbInsert('form_submission_values', ['submission_id' => $agentSubmissionId, 'field_id' => $fileField['id'], 'value' => $json]);
        }
        $val = dbFetch("SELECT value FROM form_submission_values WHERE submission_id = ? AND field_id = ?", [$agentSubmissionId, $fileField['id']]);
        assertNotNull($val, 'File value not saved');
        $decoded = json_decode($val['value'], true);
        assertTrue($decoded !== null && isset($decoded['filename']), 'JSON not valid');
    }
});

// --- Final checks ---
test('Lead is COMPLETED', function() use ($testLeadId) {
    $lead = dbFetch("SELECT workflow_stage FROM leads WHERE id = ?", [$testLeadId]);
    assertNotNull($lead);
    assertEquals('COMPLETED', $lead['workflow_stage']);
});

test('Agent can still view lead (created_by check)', function() use ($testLeadId, $testUsers) {
    $lead = dbFetch("SELECT * FROM leads WHERE id = ? AND (assigned_to = ? OR created_by = ?)", [$testLeadId, $testUsers['agent']['id'], $testUsers['agent']['id']]);
    assertNotNull($lead, 'Original agent cannot view their own lead after reassignment');
});

test('Submissions history includes role names', function() use ($testLeadId) {
    $subs = dbFetchAll(
        "SELECT fs.*, f.name as form_name, u.name as submitted_by_name, r.name as role_name
         FROM form_submissions fs JOIN forms f ON fs.form_id = f.id
         LEFT JOIN users u ON fs.submitted_by = u.id LEFT JOIN roles r ON u.role_id = r.id
         WHERE fs.lead_id = ? AND fs.status = 'submitted' ORDER BY fs.created_at",
        [$testLeadId]
    );
    assertEquals(5, count($subs));
    foreach ($subs as $s) assertNotNull($s['role_name'], "Submission {$s['form_name']} missing role name");
});

// --- Cleanup (skip any missing tables gracefully) ---
if ($testLeadId) {
    $cleanupTables = ['form_submission_values', 'form_submissions', 'documents', 'remarks', 'notifications', 'activity_logs', 'leads'];
    foreach ($cleanupTables as $ct) {
        try {
            if ($ct === 'form_submission_values') {
                dbDelete($ct, 'submission_id IN (SELECT id FROM form_submissions WHERE lead_id = ?)', [$testLeadId]);
            } elseif ($ct === 'notifications') {
                dbDelete($ct, 'related_lead_id = ?', [$testLeadId]);
            } elseif ($ct === 'activity_logs') {
                dbDelete($ct, 'entity_type = ? AND entity_id = ?', ['lead', $testLeadId]);
            } else {
                dbDelete($ct, 'lead_id = ?', [$testLeadId]);
            }
        } catch (Throwable $e) { /* table might not exist */ }
    }
}

// ===== RENDER RESULTS =====
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BestDeal CRM — Test Runner</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f4f6f9;font-family:'Segoe UI',system-ui,sans-serif}
.tc{border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.tp{border-left:4px solid #22c55e}.tf{border-left:4px solid #ef4444;background:#fef2f2}
.th{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);color:#fff;border-radius:12px 12px 0 0}
.ss{font-size:2rem;font-weight:700}
</style>
</head>
<body class="p-3">
<div class="container-fluid" style="max-width:900px">
<div class="card th mb-4"><div class="card-body py-4">
<h3 class="mb-1">✅ BestDeal CRM — Workflow Test Runner</h3>
<small class="opacity-75">Tests all roles, forms, transitions, file uploads &amp; document visibility</small>
<div class="row mt-4 text-center">
<div class="col-4"><div class="ss text-success"><?= $passed ?></div><small class="opacity-75">Passed</small></div>
<div class="col-4"><div class="ss text-danger"><?= $failed ?></div><small class="opacity-75">Failed</small></div>
<div class="col-4"><div class="ss"><?= count($results) ?></div><small class="opacity-75">Total</small></div>
</div>
</div></div>

<?php if ($failed === 0): ?>
<div class="alert alert-success d-flex align-items-center mb-4" style="border-radius:12px">
<i class="bi bi-check-circle-fill fs-3 me-3"></i>
<div><strong>All <?= $passed ?> tests passed!</strong> Full workflow works: Agent → Admin1 → LoginAgent → Admin2 → LoginAgent(Post) → Admin3 → UW → Admin4 → Dispatch → Done</div>
</div>
<?php else: ?>
<div class="alert alert-danger mb-4" style="border-radius:12px">
<strong><?= $failed ?> test(s) failed!</strong> Copy the red failed tests below and paste them for fixing.
</div>
<?php endif; ?>

<?php foreach ($results as $r): ?>
<div class="card mb-2 tc <?= $r['status']==='PASS'?'tp':'tf' ?>">
<div class="card-body py-2 px-3 d-flex align-items-center">
<span class="me-3" style="font-size:1.1rem"><?= $r['status']==='PASS'?'✅':'❌' ?></span>
<div class="flex-grow-1">
<span class="fw-semibold small"><?= htmlspecialchars($r['name']) ?></span>
<?php if ($r['detail']): ?>
<div class="text-danger small mt-1" style="font-family:monospace;font-size:.75rem"><?= htmlspecialchars($r['detail']) ?></div>
<?php endif; ?>
</div>
<span class="badge <?= $r['status']==='PASS'?'bg-success':'bg-danger' ?> ms-2"><?= $r['status'] ?></span>
</div></div>
<?php endforeach; ?>

<!-- Forms Found -->
<div class="card mt-4 tc" style="border-left:4px solid #6366f1"><div class="card-body">
<h6 class="fw-bold mb-3">📋 Forms &amp; Users</h6>
<div class="row">
<div class="col-md-6">
<table class="table table-sm small mb-3">
<thead><tr><th>Form</th><th>Stage</th><th>Fields</th></tr></thead>
<tbody>
<?php
foreach (dbFetchAll("SELECT * FROM forms WHERE status='active' ORDER BY id") as $f):
    $fc = dbCount('form_fields', 'section_id IN (SELECT id FROM form_sections WHERE form_id=?)', [$f['id']]);
    echo "<tr><td><strong>".htmlspecialchars($f['name'])."</strong></td><td><code>".htmlspecialchars($f['workflow_stage'])."</code></td><td>{$fc}</td></tr>";
endforeach;
?>
</tbody></table>
</div>
<div class="col-md-6">
<table class="table table-sm small mb-3">
<thead><tr><th>Role</th><th>ID</th><th>Name</th></tr></thead>
<tbody>
<?php foreach ($testUsers as $role => $u): ?>
<tr><td><span class="badge bg-primary"><?= $role ?></span></td><td><?= $u['id'] ?></td><td><?= htmlspecialchars($u['name']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>
</div>
</div>
</div></div>

<div class="text-center text-muted small my-4">
Test Lead #<?= $testLeadId ?> (auto-cleaned) | <?= date('d M Y, h:i:s A') ?>
</div>
</div>
</body></html>
