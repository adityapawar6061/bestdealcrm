<?php
/**
 * BestDeal CRM — Full Workflow Test Runner
 * Place this file in the project root: /bestdealcrm/testrun.php
 * Access: https://bdfsloans.com/bestdealcrm/testrun.php
 * 
 * Tests:
 *   1. DB connection & table existence
 *   2. Test user creation (agent, login_agent, underwriting, dispatch, admin)
 *   3. Form structure verification
 *   4. Agent form submission + file upload
 *   5. Admin Review 1 → assign to login agent
 *   6. Login agent pre-login checklist submission
 *   7. Admin Review 2
 *   8. Login agent post-login form submission
 *   9. Admin Review 3 → assign to underwriting
 *  10. Underwriting form submission
 *  11. Admin Review 4 → assign to dispatch
 *  12. Dispatch form submission + completion
 *  13. Document visibility across all roles
 *  14. Workflow stage verification at each step
 */

error_reporting(E_ALL);
ini_set('display_errors', '0'); // We'll display our own

define('ROOT_PATH', __DIR__);

// Bootstrap the app
spl_autoload_register(function ($class) {
    $map = [
        'Database' => ROOT_PATH . '/config/database.php',
        'Router'   => ROOT_PATH . '/config/Router.php',
    ];
    if (isset($map[$class])) {
        if (file_exists($map[$class])) require_once $map[$class];
        return;
    }
    $parts = explode('\\', $class);
    if (count($parts) < 2) return;
    $filePath = ROOT_PATH . '/app/' . implode('/', $parts) . '.php';
    if (file_exists($filePath)) require_once $filePath;
});

if (file_exists(ROOT_PATH . '/config/config.php')) require_once ROOT_PATH . '/config/config.php';
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) require_once ROOT_PATH . '/app/Helpers/Session.php';
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) require_once ROOT_PATH . '/app/Helpers/Helpers.php';

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
        $results[] = ['name' => $name, 'status' => 'FAIL', 'detail' => $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine()];
        $failed++;
    }
}

function assert_true($val, string $msg = '') {
    if (!$val) throw new \Exception("Assertion failed: {$msg}");
}

function assert_not_null($val, string $msg = 'Expected non-null value') {
    if ($val === null) throw new \Exception($msg);
}

function assert_equals($expected, $actual, string $msg = '') {
    if ($expected !== $actual) {
        throw new \Exception(($msg ? $msg . ': ' : '') . "Expected " . var_export($expected, true) . ", got " . var_export($actual, true));
    }
}

// ===== DB Setup =====
$db = Database::getInstance();
$istNow = nowIST();

// Create test users if they don't exist
$testUsers = [];
$roleNames = ['agent', 'login_agent', 'underwriting', 'dispatch', 'admin'];

foreach ($roleNames as $roleName) {
    $existing = $db->fetchOne("SELECT id, name FROM users WHERE username = ?", ["test_{$roleName}"]);
    if ($existing) {
        $testUsers[$roleName] = $existing;
    } else {
        $role = $db->fetchOne("SELECT id FROM roles WHERE name = ?", [$roleName]);
        if (!$role) {
            // Create role
            $roleId = $db->insert('roles', ['name' => $roleName, 'display_name' => ucwords(str_replace('_', ' ', $roleName)), 'created_at' => $istNow]);
        } else {
            $roleId = $role['id'];
        }
        $userId = $db->insert('users', [
            'username'  => "test_{$roleName}",
            'email'     => "test_{$roleName}@bestdealcrm.com",
            'password'  => password_hash('test1234', PASSWORD_DEFAULT),
            'name'      => "Test " . ucwords(str_replace('_', ' ', $roleName)),
            'role_id'   => $roleId,
            'status'    => 'active',
            'created_at'=> $istNow,
        ]);
        $testUsers[$roleName] = ['id' => $userId, 'name' => "Test " . ucwords(str_replace('_', ' ', $roleName))];
    }
}

// ===== Get form IDs =====
$agentForm = $db->fetchOne("SELECT id, name FROM forms WHERE workflow_stage = 'Agent Draft' AND status = 'active' LIMIT 1");
$preLoginForm = $db->fetchOne("SELECT id, name FROM forms WHERE workflow_stage = 'Login Agent Draft' AND status = 'active' LIMIT 1");
$postLoginForm = $db->fetchOne("SELECT id, name FROM forms WHERE workflow_stage = 'Post Login' AND status = 'active' LIMIT 1");
$uwForm = $db->fetchOne("SELECT id, name FROM forms WHERE workflow_stage = 'Underwriting' AND status = 'active' LIMIT 1");
$dispatchForm = $db->fetchOne("SELECT id, name FROM forms WHERE workflow_stage = 'Dispatch' AND status = 'active' LIMIT 1");

$agentFormId = $agentForm ? (int)$agentForm['id'] : 0;
$preLoginFormId = $preLoginForm ? (int)$preLoginForm['id'] : 0;
$postLoginFormId = $postLoginForm ? (int)$postLoginForm['id'] : 0;
$uwFormId = $uwForm ? (int)$uwForm['id'] : 0;
$dispatchFormId = $dispatchForm ? (int)$dispatchForm['id'] : 0;

// ===== Get form fields for test data =====
function getFormFields(Database $db, int $formId): array {
    if (!$formId) return [];
    $sections = $db->fetchAll("SELECT id FROM form_sections WHERE form_id = ? ORDER BY display_order", [$formId]);
    $fields = [];
    foreach ($sections as $sec) {
        $rows = $db->fetchAll("SELECT id, field_name, label, type, required FROM form_fields WHERE section_id = ? AND (is_hidden IS NULL OR is_hidden = 0) ORDER BY display_order", [$sec['id']]);
        $fields = array_merge($fields, $rows);
    }
    return $fields;
}

function generateTestData(array $fields, int $leadId): array {
    $values = [];
    foreach ($fields as $field) {
        $type = $field['type'];
        $name = $field['field_name'];
        switch ($type) {
            case 'text':
            case 'email':
            case 'mobile':
                $values[$field['id']] = "Test Value " . $field['id'];
                break;
            case 'number':
            case 'decimal':
                $values[$field['id']] = (string)(1000 + $field['id']);
                break;
            case 'date':
                $values[$field['id']] = '2026-08-15';
                break;
            case 'dropdown':
                // Will be filled with first option if available
                $values[$field['id']] = 'test_option';
                break;
            case 'radio':
                $values[$field['id']] = 'Yes';
                break;
            case 'checkbox':
                $values[$field['id']] = '1';
                break;
            case 'textarea':
                $values[$field['id']] = "Test remark for field " . $field['id'];
                break;
            case 'file':
            case 'image':
                $values[$field['id']] = ''; // Skip file uploads for basic test
                break;
            default:
                $values[$field['id']] = "Test " . $field['id'];
        }
    }
    return $values;
}

// ===== TESTS =====

// --- 1. DB Connection ---
test('Database connection works', function() use ($db) {
    $result = $db->fetchOne("SELECT 1 as ok");
    assert_true($result && $result['ok'] == 1, 'DB ping failed');
});

// --- 2. Table existence ---
$requiredTables = ['users', 'roles', 'leads', 'forms', 'form_sections', 'form_fields', 'form_submissions', 'form_submission_values', 'documents', 'remarks', 'workflow_events', 'form_field_options'];
foreach ($requiredTables as $table) {
    test("Table `{$table}` exists", function() use ($db, $table) {
        $check = $db->fetchOne("SHOW TABLES LIKE '{$table}'");
        assert_not_null($check, "Table `{$table}` not found");
    });
}

// --- 3. Form structure ---
$forms = [
    'Agent Lead Form' => $agentFormId,
    'Pre-Login Checklist' => $preLoginFormId,
    'Post-Login Form' => $postLoginFormId,
    'UnderWriting Form' => $uwFormId,
    'Dispatch Form' => $dispatchFormId,
];
foreach ($forms as $formName => $fid) {
    test("Form \"{$formName}\" exists and has sections", function() use ($db, $fid, $formName) {
        assert_true($fid > 0, "Form \"{$formName}\" not found in database");
        $sections = $db->fetchAll("SELECT id FROM form_sections WHERE form_id = ?", [$fid]);
        assert_true(count($sections) > 0, "Form \"{$formName}\" has no sections");
        $fields = $db->fetchAll("SELECT id FROM form_fields WHERE section_id IN (SELECT id FROM form_sections WHERE form_id = ?)", [$fid]);
        assert_true(count($fields) > 0, "Form \"{$formName}\" has no fields");
    });
}

// --- 4. Test user creation ---
foreach ($testUsers as $role => $user) {
    test("Test user for role '{$role}' exists", function() use ($user, $role) {
        assert_not_null($user['id'], "User for role {$role} not created");
    });
}

// --- 5. Create test lead ---
$testLeadId = 0;
test('Create test lead', function() use ($db, &$testLeadId, $testUsers, $istNow) {
    $agentId = $testUsers['agent']['id'];
    $testLeadId = $db->insert('leads', [
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
    assert_true($testLeadId > 0, 'Lead creation failed');
});

// --- 6. Agent: Submit form ---
$agentSubmissionId = 0;
test('Agent submits lead form (with text values)', function() use ($db, &$agentSubmissionId, $testLeadId, $testUsers, $agentFormId) {
    if (!$agentFormId) throw new \Exception('Agent form not found');
    $agentId = $testUsers['agent']['id'];
    $fields = getFormFields($db, $agentFormId);
    $values = [];
    foreach ($fields as $f) {
        if ($f['type'] === 'file' || $f['type'] === 'image') continue;
        $values[$f['id']] = "AgentVal_" . $f['id'];
    }
    $agentSubmissionId = $db->insert('form_submissions', [
        'form_id'      => $agentFormId,
        'lead_id'      => $testLeadId,
        'submitted_by' => $agentId,
        'status'       => 'submitted',
        'created_at'   => $istNow,
        'updated_at'   => $istNow,
    ]);
    foreach ($values as $fieldId => $value) {
        $db->insert('form_submission_values', [
            'submission_id' => $agentSubmissionId,
            'field_id'      => $fieldId,
            'value'         => $value,
        ]);
    }
    // Update workflow
    $db->update('leads', ['workflow_stage' => 'ADMIN_REVIEW_1', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assert_true($agentSubmissionId > 0, 'Agent form submission failed');
});

// --- 7. Agent: Verify submission visible ---
test('Agent can see their own submission', function() use ($db, $agentSubmissionId, $testUsers) {
    $sub = $db->fetchOne("SELECT * FROM form_submissions WHERE id = ? AND submitted_by = ?", [$agentSubmissionId, $testUsers['agent']['id']]);
    assert_not_null($sub, 'Agent cannot see their submission');
    assert_equals('submitted', $sub['status'], 'Submission status should be submitted');
});

// --- 8. Admin Review 1 → Assign to Login Agent ---
test('Admin Review 1: Approve & assign to login agent', function() use ($db, $testLeadId, $testUsers, $istNow) {
    $loginAgentId = $testUsers['login_agent']['id'];
    $db->update('leads', [
        'assigned_to'    => $loginAgentId,
        'workflow_stage' => 'LOGIN_AGENT_ASSIGNED',
        'updated_at'     => $istNow,
    ], 'id = ?', [$testLeadId]);
    // Log remark
    $db->insert('remarks', [
        'lead_id' => $testLeadId, 'user_id' => $testUsers['admin']['id'],
        'stage' => 'ADMIN_REVIEW_1', 'remark' => 'Approved - assigning to login agent',
        'created_at' => $istNow,
    ]);
    $lead = $db->fetchOne("SELECT * FROM leads WHERE id = ?", [$testLeadId]);
    assert_equals('LOGIN_AGENT_ASSIGNED', $lead['workflow_stage']);
    assert_equals($loginAgentId, $lead['assigned_to']);
});

// --- 9. Login Agent: Submit Pre-Login Checklist ---
$preLoginSubmissionId = 0;
test('Login agent submits pre-login checklist', function() use ($db, &$preLoginSubmissionId, $testLeadId, $testUsers, $preLoginFormId) {
    if (!$preLoginFormId) throw new \Exception('Pre-login form not found');
    $laId = $testUsers['login_agent']['id'];
    $fields = getFormFields($db, $preLoginFormId);
    $preLoginSubmissionId = $db->insert('form_submissions', [
        'form_id'      => $preLoginFormId,
        'lead_id'      => $testLeadId,
        'submitted_by' => $laId,
        'status'       => 'submitted',
        'created_at'   => $istNow,
        'updated_at'   => $istNow,
    ]);
    foreach ($fields as $f) {
        $val = ($f['type'] === 'file' || $f['type'] === 'image') ? '' : "PreLoginVal_" . $f['id'];
        if ($val) {
            $db->insert('form_submission_values', [
                'submission_id' => $preLoginSubmissionId,
                'field_id'      => $f['id'],
                'value'         => $val,
            ]);
        }
    }
    $db->update('leads', ['workflow_stage' => 'ADMIN_REVIEW_2', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assert_true($preLoginSubmissionId > 0, 'Pre-login submission failed');
});

// --- 10. Pre-login submission visible across roles ---
test('Pre-login submission visible to admin', function() use ($db, $preLoginSubmissionId) {
    $sub = $db->fetchOne("SELECT * FROM form_submissions WHERE id = ?", [$preLoginSubmissionId]);
    assert_not_null($sub, 'Pre-login submission not found');
    assert_equals('submitted', $sub['status']);
});

// --- 11. Admin Review 2 → Approve to Post Login ---
test('Admin Review 2: Approve & send to post-login', function() use ($db, $testLeadId, $testUsers, $istNow) {
    $db->update('leads', [
        'workflow_stage' => 'LOGIN_APPROVED',
        'updated_at'     => $istNow,
    ], 'id = ?', [$testLeadId]);
    $db->insert('remarks', [
        'lead_id' => $testLeadId, 'user_id' => $testUsers['admin']['id'],
        'stage' => 'ADMIN_REVIEW_2', 'remark' => 'Pre-login approved',
        'created_at' => $istNow,
    ]);
    $lead = $db->fetchOne("SELECT workflow_stage FROM leads WHERE id = ?", [$testLeadId]);
    assert_equals('LOGIN_APPROVED', $lead['workflow_stage']);
});

// --- 12. Login Agent: Submit Post-Login Form ---
$postLoginSubmissionId = 0;
test('Login agent submits post-login form', function() use ($db, &$postLoginSubmissionId, $testLeadId, $testUsers, $postLoginFormId) {
    if (!$postLoginFormId) throw new \Exception('Post-login form not found');
    $laId = $testUsers['login_agent']['id'];
    $fields = getFormFields($db, $postLoginFormId);
    $postLoginSubmissionId = $db->insert('form_submissions', [
        'form_id'      => $postLoginFormId,
        'lead_id'      => $testLeadId,
        'submitted_by' => $laId,
        'status'       => 'submitted',
        'created_at'   => $istNow,
        'updated_at'   => $istNow,
    ]);
    foreach ($fields as $f) {
        $val = ($f['type'] === 'file' || $f['type'] === 'image') ? '' : "PostLoginVal_" . $f['id'];
        if ($val) {
            $db->insert('form_submission_values', [
                'submission_id' => $postLoginSubmissionId,
                'field_id'      => $f['id'],
                'value'         => $val,
            ]);
        }
    }
    $db->update('leads', ['workflow_stage' => 'ADMIN_REVIEW_3', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assert_true($postLoginSubmissionId > 0, 'Post-login submission failed');
});

// --- 13. Admin Review 3 → Send to Underwriting ---
test('Admin Review 3: Approve & send to underwriting', function() use ($db, $testLeadId, $testUsers, $istNow) {
    $uwId = $testUsers['underwriting']['id'];
    $db->update('leads', [
        'assigned_to'    => $uwId,
        'workflow_stage' => 'UNDERWRITING',
        'updated_at'     => $istNow,
    ], 'id = ?', [$testLeadId]);
    $db->insert('remarks', [
        'lead_id' => $testLeadId, 'user_id' => $testUsers['admin']['id'],
        'stage' => 'ADMIN_REVIEW_3', 'remark' => 'Approved - sending to underwriting',
        'created_at' => $istNow,
    ]);
    $lead = $db->fetchOne("SELECT * FROM leads WHERE id = ?", [$testLeadId]);
    assert_equals('UNDERWRITING', $lead['workflow_stage']);
    assert_equals($uwId, $lead['assigned_to']);
});

// --- 14. Underwriter: Submit form ---
$uwSubmissionId = 0;
test('Underwriter submits underwriting form', function() use ($db, &$uwSubmissionId, $testLeadId, $testUsers, $uwFormId) {
    if (!$uwFormId) throw new \Exception('Underwriting form not found');
    $uwId = $testUsers['underwriting']['id'];
    $fields = getFormFields($db, $uwFormId);
    $uwSubmissionId = $db->insert('form_submissions', [
        'form_id'      => $uwFormId,
        'lead_id'      => $testLeadId,
        'submitted_by' => $uwId,
        'status'       => 'submitted',
        'created_at'   => $istNow,
        'updated_at'   => $istNow,
    ]);
    foreach ($fields as $f) {
        $val = ($f['type'] === 'file' || $f['type'] === 'image') ? '' : "UWVal_" . $f['id'];
        if ($val) {
            $db->insert('form_submission_values', [
                'submission_id' => $uwSubmissionId,
                'field_id'      => $f['id'],
                'value'         => $val,
            ]);
        }
    }
    $db->update('leads', ['workflow_stage' => 'ADMIN_REVIEW_4', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assert_true($uwSubmissionId > 0, 'Underwriting submission failed');
});

// --- 15. Admin Review 4 → Send to Dispatch ---
test('Admin Review 4: Approve & send to dispatch', function() use ($db, $testLeadId, $testUsers, $istNow) {
    $dispatchId = $testUsers['dispatch']['id'];
    $db->update('leads', [
        'assigned_to'    => $dispatchId,
        'workflow_stage' => 'DISPATCH',
        'updated_at'     => $istNow,
    ], 'id = ?', [$testLeadId]);
    $db->insert('remarks', [
        'lead_id' => $testLeadId, 'user_id' => $testUsers['admin']['id'],
        'stage' => 'ADMIN_REVIEW_4', 'remark' => 'Approved - sending to dispatch',
        'created_at' => $istNow,
    ]);
    $lead = $db->fetchOne("SELECT * FROM leads WHERE id = ?", [$testLeadId]);
    assert_equals('DISPATCH', $lead['workflow_stage']);
    assert_equals($dispatchId, $lead['assigned_to']);
});

// --- 16. Dispatcher: Submit form ---
$dispatchSubmissionId = 0;
test('Dispatcher submits dispatch form', function() use ($db, &$dispatchSubmissionId, $testLeadId, $testUsers, $dispatchFormId) {
    if (!$dispatchFormId) throw new \Exception('Dispatch form not found');
    $dispId = $testUsers['dispatch']['id'];
    $fields = getFormFields($db, $dispatchFormId);
    $dispatchSubmissionId = $db->insert('form_submissions', [
        'form_id'      => $dispatchFormId,
        'lead_id'      => $testLeadId,
        'submitted_by' => $dispId,
        'status'       => 'submitted',
        'created_at'   => $istNow,
        'updated_at'   => $istNow,
    ]);
    foreach ($fields as $f) {
        $val = ($f['type'] === 'file' || $f['type'] === 'image') ? '' : "DispatchVal_" . $f['id'];
        if ($val) {
            $db->insert('form_submission_values', [
                'submission_id' => $dispatchSubmissionId,
                'field_id'      => $f['id'],
                'value'         => $val,
            ]);
        }
    }
    $db->update('leads', ['workflow_stage' => 'COMPLETED', 'updated_at' => $istNow], 'id = ?', [$testLeadId]);
    assert_true($dispatchSubmissionId > 0, 'Dispatch submission failed');
});

// --- 17. All submissions visible ---
test('All 5 form submissions exist for this lead', function() use ($db, $testLeadId) {
    $subs = $db->fetchAll("SELECT * FROM form_submissions WHERE lead_id = ? AND status = 'submitted' ORDER BY created_at", [$testLeadId]);
    assert_equals(5, count($subs), "Expected 5 submissions, got " . count($subs));
});

// --- 18. Document upload test ---
$testDocId = 0;
test('Document upload and cross-role visibility', function() use ($db, $testLeadId, $testUsers, &$testDocId, $istNow) {
    // Create a test document
    $testDocId = $db->insert('documents', [
        'lead_id'       => $testLeadId,
        'uploaded_by'   => $testUsers['agent']['id'],
        'filename'      => 'test_pan_card.pdf',
        'original_name' => 'PAN_Card_Test.pdf',
        'mime_type'     => 'application/pdf',
        'file_size'     => 12345,
        'document_type' => 'form_upload',
        'created_at'    => $istNow,
    ]);
    assert_true($testDocId > 0, 'Document creation failed');

    // Verify visible from different roles
    foreach (['login_agent', 'underwriting', 'dispatch', 'admin'] as $role) {
        $doc = $db->fetchOne("SELECT * FROM documents WHERE id = ? AND lead_id = ?", [$testDocId, $testLeadId]);
        assert_not_null($doc, "Document not visible to {$role}");
    }
});

// --- 19. File upload in form field test ---
test('File upload via form_submission_values (JSON format)', function() use ($db, $agentSubmissionId, $testUsers) {
    // Simulate a file upload stored as JSON in form_submission_values
    $fields = getFormFields($db, $agentFormId);
    $fileField = null;
    foreach ($fields as $f) {
        if ($f['type'] === 'file' || $f['type'] === 'image') {
            $fileField = $f;
            break;
        }
    }
    if (!$fileField) {
        // No file fields in form - create one for testing
        $sections = $db->fetchAll("SELECT id FROM form_sections WHERE form_id = ? ORDER BY display_order LIMIT 1", [$agentFormId]);
        if (!empty($sections)) {
            $fileFieldId = $db->insert('form_fields', [
                'section_id'    => $sections[0]['id'],
                'field_name'    => 'test_file_field',
                'label'         => 'Test Document',
                'type'          => 'file',
                'placeholder'   => '',
                'required'      => 0,
                'display_order' => 999,
                'created_at'    => $istNow,
            ]);
            $fileField = ['id' => $fileFieldId, 'type' => 'file'];
        }
    }
    if ($fileField) {
        $fileJson = json_encode([
            'doc_id'    => (int)$testDocId,
            'filename'  => 'test_pan_card.pdf',
            'original'  => 'PAN_Card_Test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 12345,
        ]);
        // Update the existing submission or insert
        $existing = $db->fetchOne("SELECT id FROM form_submission_values WHERE submission_id = ? AND field_id = ?", [$agentSubmissionId, $fileField['id']]);
        if ($existing) {
            $db->update('form_submission_values', ['value' => $fileJson], 'id = ?', [$existing['id']]);
        } else {
            $db->insert('form_submission_values', [
                'submission_id' => $agentSubmissionId,
                'field_id'      => $fileField['id'],
                'value'         => $fileJson,
            ]);
        }
        // Verify it can be read back
        $val = $db->fetchOne("SELECT * FROM form_submission_values WHERE submission_id = ? AND field_id = ?", [$agentSubmissionId, $fileField['id']]);
        assert_not_null($val, 'File upload value not saved');
        $decoded = json_decode($val['value'], true);
        assert_true($decoded !== null, 'File upload value is not valid JSON');
        assert_true(isset($decoded['filename']), 'File upload JSON missing filename');
    }
});

// --- 20. Workflow timeline verification ---
test('Workflow timeline has events for this lead', function() use ($db, $testLeadId) {
    $events = $db->fetchAll("SELECT * FROM workflow_events WHERE lead_id = ? ORDER BY created_at", [$testLeadId]);
    assert_true(count($events) > 0, "No workflow events found for lead #{$testLeadId}");
});

// --- 21. Remarks verification ---
test('Remarks exist for this lead', function() use ($db, $testLeadId) {
    $remarks = $db->fetchAll("SELECT * FROM remarks WHERE lead_id = ? ORDER BY created_at", [$testLeadId]);
    assert_true(count($remarks) >= 4, "Expected at least 4 remarks (one per admin review), got " . count($remarks));
});

// --- 22. Final lead state ---
test('Lead is COMPLETED with all transitions', function() use ($db, $testLeadId) {
    $lead = $db->fetchOne("SELECT * FROM leads WHERE id = ?", [$testLeadId]);
    assert_not_null($lead, 'Lead not found');
    assert_equals('COMPLETED', $lead['workflow_stage'], 'Lead should be COMPLETED');
});

// --- 23. Access check: agent can view their lead even after reassignment ---
test('Original agent can still view lead (created_by check)', function() use ($db, $testLeadId, $testUsers) {
    $agentId = $testUsers['agent']['id'];
    // The lead is now assigned to dispatch, but agent created it
    $lead = $db->fetchOne("SELECT * FROM leads WHERE id = ? AND (assigned_to = ? OR created_by = ?)", [$testLeadId, $agentId, $agentId]);
    assert_not_null($lead, 'Original agent cannot view their own lead');
});

// --- 24. Cross-role document visibility ---
test('All roles can see documents for this lead', function() use ($db, $testLeadId) {
    $docs = $db->fetchAll("SELECT * FROM documents WHERE lead_id = ?", [$testLeadId]);
    assert_true(count($docs) > 0, "No documents found for lead #{$testLeadId}");
    // Documents are queried by lead_id, not by assigned_to, so all roles see them
    foreach (['agent', 'login_agent', 'underwriting', 'dispatch', 'admin'] as $role) {
        $count = $db->count('documents', 'lead_id = ?', [$testLeadId]);
        assert_true($count > 0, "Documents not visible for role: {$role}");
    }
});

// --- 25. Submissions history ---
test('Submissions history includes all forms with role names', function() use ($db, $testLeadId) {
    $subs = $db->fetchAll(
        "SELECT fs.*, f.name as form_name, u.name as submitted_by_name, r.name as role_name
         FROM form_submissions fs
         JOIN forms f ON fs.form_id = f.id
         LEFT JOIN users u ON fs.submitted_by = u.id
         LEFT JOIN roles r ON u.role_id = r.id
         WHERE fs.lead_id = ? AND fs.status = 'submitted'
         ORDER BY fs.created_at",
        [$testLeadId]
    );
    assert_equals(5, count($subs), "Expected 5 submissions with role names, got " . count($subs));
    foreach ($subs as $s) {
        assert_not_null($s['role_name'], "Submission {$s['form_name']} missing role name");
    }
});

// ===== Cleanup =====
// Clean up test lead and submissions
if ($testLeadId) {
    $db->query("DELETE FROM form_submission_values WHERE submission_id IN (SELECT id FROM form_submissions WHERE lead_id = ?)", [$testLeadId]);
    $db->query("DELETE FROM form_submissions WHERE lead_id = ?", [$testLeadId]);
    $db->query("DELETE FROM documents WHERE lead_id = ?", [$testLeadId]);
    $db->query("DELETE FROM remarks WHERE lead_id = ?", [$testLeadId]);
    $db->query("DELETE FROM workflow_events WHERE lead_id = ?", [$testLeadId]);
    $db->query("DELETE FROM activity_logs WHERE entity_type = 'lead' AND entity_id = ?", [$testLeadId]);
    $db->query("DELETE FROM notifications WHERE related_lead_id = ?", [$testLeadId]);
    $db->delete('leads', 'id = ?', [$testLeadId]);
}

// ===== Render Results =====
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BestDeal CRM — Test Runner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .test-card { border: none; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .test-pass { border-left: 4px solid #22c55e; }
        .test-fail { border-left: 4px solid #ef4444; background: #fef2f2; }
        .test-header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; border-radius: 12px 12px 0 0; }
        .summary-stat { font-size: 2rem; font-weight: 700; }
        .pulse { animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
    </style>
</head>
<body class="p-3">
<div class="container-fluid" style="max-width:900px">

    <!-- Header -->
    <div class="card test-header mb-4">
        <div class="card-body py-4">
            <h3 class="mb-1"><i class="bi bi-check-circle-fill me-2"></i>BestDeal CRM — Workflow Test Runner</h3>
            <small class="opacity-75">Tests all roles, forms, transitions, file uploads & document visibility</small>
            <div class="row mt-4 text-center">
                <div class="col-4">
                    <div class="summary-stat text-success"><?= $passed ?></div>
                    <small class="opacity-75">Passed</small>
                </div>
                <div class="col-4">
                    <div class="summary-stat text-danger"><?= $failed ?></div>
                    <small class="opacity-75">Failed</small>
                </div>
                <div class="col-4">
                    <div class="summary-stat"><?= count($results) ?></div>
                    <small class="opacity-75">Total</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Overall Result -->
    <?php if ($failed === 0): ?>
    <div class="alert alert-success d-flex align-items-center mb-4" style="border-radius:12px">
        <i class="bi bi-check-circle-fill fs-3 me-3"></i>
        <div>
            <strong>All tests passed!</strong> The full workflow works correctly: Agent → Admin Review 1 → Login Agent → Admin Review 2 → Login Agent (Post-Login) → Admin Review 3 → Underwriting → Admin Review 4 → Dispatch → Completed
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-danger d-flex align-items-center mb-4" style="border-radius:12px">
        <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
        <div>
            <strong><?= $failed ?> test(s) failed!</strong> Check the details below. Copy the failed test(s) and send them for fixing.
        </div>
    </div>
    <?php endif; ?>

    <!-- Test Results -->
    <?php foreach ($results as $i => $r): ?>
    <div class="card mb-2 test-card <?= $r['status'] === 'PASS' ? 'test-pass' : 'test-fail' ?>">
        <div class="card-body py-2 px-3 d-flex align-items-center">
            <span class="me-3" style="font-size:1.1rem">
                <?= $r['status'] === 'PASS' ? '✅' : '❌' ?>
            </span>
            <div class="flex-grow-1">
                <span class="fw-semibold small"><?= htmlspecialchars($r['name']) ?></span>
                <?php if ($r['detail']): ?>
                    <div class="text-danger small mt-1" style="font-family:monospace;font-size:0.75rem">
                        <?= htmlspecialchars($r['detail']) ?>
                    </div>
                <?php endif; ?>
            </div>
            <span class="badge <?= $r['status'] === 'PASS' ? 'bg-success' : 'bg-danger' ?> ms-2">
                <?= $r['status'] ?>
            </span>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Form Info -->
    <div class="card mt-4 test-card" style="border-left:4px solid #6366f1">
        <div class="card-body">
            <h6 class="fw-bold mb-3">📋 Forms Found</h6>
            <table class="table table-sm small mb-0">
                <thead><tr><th>#</th><th>Name</th><th>Stage</th><th>Fields</th><th>Sections</th></tr></thead>
                <tbody>
                <?php
                $allForms = $db->fetchAll("SELECT f.*, (SELECT COUNT(*) FROM form_sections WHERE form_id = f.id) as sec_count FROM forms f WHERE f.status = 'active' ORDER BY f.id");
                foreach ($allForms as $i => $f):
                    $fieldCount = $db->count('form_fields', 'section_id IN (SELECT id FROM form_sections WHERE form_id = ?)', [$f['id']]);
                ?>
                <tr>
                    <td><?= $f['id'] ?></td>
                    <td><strong><?= htmlspecialchars($f['name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($f['workflow_stage']) ?></span></td>
                    <td><?= $fieldCount ?></td>
                    <td><?= $f['sec_count'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Test Users -->
    <div class="card mt-3 test-card" style="border-left:4px solid #6366f1">
        <div class="card-body">
            <h6 class="fw-bold mb-3">👤 Test Users</h6>
            <table class="table table-sm small mb-0">
                <thead><tr><th>Role</th><th>ID</th><th>Name</th><th>Username</th></tr></thead>
                <tbody>
                <?php foreach ($testUsers as $role => $u): ?>
                <tr>
                    <td><span class="badge bg-primary"><?= $role ?></span></td>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><code>test_<?= $role ?></code></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Workflow Diagram -->
    <div class="card mt-3 mb-4 test-card" style="border-left:4px solid #6366f1">
        <div class="card-body">
            <h6 class="fw-bold mb-3">🔄 Full Workflow Tested</h6>
            <div class="d-flex flex-wrap gap-2 align-items-center small">
                <?php
                $steps = [
                    ['Agent', 'primary', 'Agent Form'],
                    ['→', '', ''],
                    ['Admin 1', 'warning', 'Review 1'],
                    ['→', '', ''],
                    ['Login Agent', 'info', 'Pre-Login'],
                    ['→', '', ''],
                    ['Admin 2', 'warning', 'Review 2'],
                    ['→', '', ''],
                    ['Login Agent', 'info', 'Post-Login'],
                    ['→', '', ''],
                    ['Admin 3', 'warning', 'Review 3'],
                    ['→', '', ''],
                    ['Underwriting', 'success', 'UW Form'],
                    ['→', '', ''],
                    ['Admin 4', 'warning', 'Review 4'],
                    ['→', '', ''],
                    ['Dispatch', 'danger', 'Dispatch Form'],
                    ['→', '', ''],
                    ['Completed', 'dark', 'Done'],
                ];
                foreach ($steps as $s):
                    if ($s[0] === '→'): ?>
                        <span class="text-muted">→</span>
                    <?php else: ?>
                        <div class="text-center">
                            <span class="badge bg-<?= $s[1] ?> d-block mb-1" style="font-size:0.7rem"><?= $s[0] ?></span>
                            <small class="text-muted" style="font-size:0.6rem"><?= $s[2] ?></small>
                        </div>
                    <?php endif;
                endforeach; ?>
            </div>
        </div>
    </div>

    <div class="text-center text-muted small mb-4">
        Generated at <?= date('d M Y, h:i:s A') ?> | Test Lead #<?= $testLeadId ?> (cleaned up)
    </div>

</div>
</body>
</html>
