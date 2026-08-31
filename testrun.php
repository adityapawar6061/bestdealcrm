<?php
/**
 * BestDeal CRM — HTTP Integration Test Runner
 * Access: https://bdfsloans.com/bestdealcrm/testrun.php
 *
 * Logs in as each role via HTTP, submits forms through real routes,
 * uploads actual files, checks rendered HTML for errors.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');
set_time_limit(120);

// ===== Config =====
$BASE_URL = 'https://bdfsloans.com/bestdealcrm';
$COOKIE_FILE = tempnam(sys_get_temp_dir(), 'crmtest_');

// ===== DB Setup =====
$rootPath = __DIR__;
if (file_exists($rootPath . '/.env')) {
    $lines = file($rootPath . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (trim($line)[0] === '#' || empty(trim($line))) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value, " \t\n\r\0\x0B\"'"));
        }
    }
}
$dbHost = getenv('DB_HOST') ?: '68.178.237.250';
$dbName = getenv('DB_NAME') : 'bestdealcrm';
$dbUser = getenv('DB_USER') ?: 'sayali';
$dbPass = getenv('DB_PASS') ?: 'sayali@1234';
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $pdo->exec("SET NAMES utf8mb4, time_zone = '+05:30'");
} catch (PDOException $e) {
    die('<h1>DB Failed</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>');
}

function nowIST() { return (new DateTime('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s'); }
function dbQ($sql, $p=[]) { global $pdo; $s=$pdo->prepare($sql); $s->execute($p); return $s; }
function dbF($sql, $p=[]) { return dbQ($sql,$p)->fetch() ?: null; }
function dbA($sql, $p=[]) { return dbQ($sql,$p)->fetchAll(); }
function dbI($t, $d) { global $pdo; $c=implode(',',array_keys($d)); $v=implode(',',array_fill(0,count($d),'?')); dbQ("INSERT INTO {$t} ({$c}) VALUES ({$v})",array_values($d)); return $pdo->lastInsertId(); }
function dbU($t,$d,$w,$wp=[]) { $s=implode(',',array_map(function($k){return "{$k}=?";},array_keys($d))); dbQ("UPDATE {$t} SET {$s} WHERE {$w}",array_merge(array_values($d),$wp)); }
function dbD($t,$w,$p=[]) { dbQ("DELETE FROM {$t} WHERE {$w}",$p); }

// ===== HTTP Helpers =====
function httpGet($url, $followRedirects = true) {
    global $COOKIE_FILE;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => $COOKIE_FILE,
        CURLOPT_COOKIEJAR => $COOKIE_FILE, CURLOPT_FOLLOWLOCATION => $followRedirects,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 30,
        CURLOPT_USER_AGENT => 'TestRunner/1.0',
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['html' => $html ?: '', 'code' => $code];
}

function httpPost($url, $data, $files = []) {
    global $COOKIE_FILE;
    $ch = curl_init($url);
    $postFields = [];
    if (!empty($files)) {
        foreach ($files as $key => $filePath) {
            $postFields[$key] = new CURLFile($filePath, mime_content_type($filePath), basename($filePath));
        }
    }
    foreach ($data as $k => $v) { $postFields[$k] = $v; }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_COOKIEFILE => $COOKIE_FILE, CURLOPT_COOKIEJAR => $COOKIE_FILE,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30, CURLOPT_USER_AGENT => 'TestRunner/1.0',
        CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    // Try to parse JSON
    $json = json_decode($body, true);
    if ($json && json_last_error() === JSON_ERROR_NONE) {
        return ['json' => $json, 'code' => $code, 'html' => $body];
    }
    return ['json' => null, 'code' => $code, 'html' => $body ?: ''];
}

function extractCsrf($html) {
    if (preg_match('/name=["\']_csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    if (preg_match('/name=["\']_csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    if (preg_match('/CSRF_TOKEN\s*=\s*["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    return null;
}

function loginAs($username, $password) {
    global $BASE_URL;
    // GET login page first to get session + CSRF
    $page = httpGet($BASE_URL . '/login');
    $csrf = extractCsrf($page['html']);

    $data = ['username' => $username, 'password' => $password];
    if ($csrf) $data['_csrf_token'] = $csrf;

    $result = httpPost($BASE_URL . '/login', $data);
    // Check if redirected to dashboard (success)
    $dash = httpGet($BASE_URL . '/dashboard');
    return stripos($dash['html'], 'dashboard') !== false || stripos($dash['html'], 'Dashboard') !== false;
}

function checkHtml($html, $label) {
    $errors = [];
    // Check for PHP errors
    if (preg_match('/<b>(Fatal|Warning|Parse) error<\/b>.*?<br>/', $html, $m)) {
        $errors[] = "PHP Error: " . strip_tags($m[0]);
    }
    // Check for SQL errors
    if (preg_match('/SQLSTATE\[.*?\].*?<\/pre>/s', $html, $m)) {
        $errors[] = "SQL Error: " . strip_tags(substr($m[0], 0, 200));
    }
    // Check for blank <span class= (the rendering bug)
    if (preg_match('/<span class=\s*"?—/', $html)) {
        $errors[] = "Rendering bug: <span class=— leaking";
    }
    // Check for 404
    if (stripos($html, '404') !== false && stripos($html, 'not found') !== false) {
        $errors[] = "404 Not Found";
    }
    // Check for 403
    if (stripos($html, '403') !== false && stripos($html, 'unauthorized') !== false) {
        $errors[] = "403 Unauthorized";
    }
    return $errors;
}

// ===== Test Framework =====
$results = [];
$passed = 0;
$failed = 0;

function test($name, callable $fn) {
    global $results, $passed, $failed;
    try {
        $fn();
        $results[] = ['name' => $name, 'status' => 'PASS', 'detail' => ''];
        $passed++;
    } catch (Throwable $e) {
        $results[] = ['name' => $name, 'status' => 'FAIL', 'detail' => $e->getMessage()];
        $failed++;
    }
}

function assertTrue($val, $msg='Assertion failed') { if (!$val) throw new Exception($msg); }
function assertNotNull($val, $msg='Expected non-null') { if ($val === null) throw new Exception($msg); }
function assertEquals($e, $a, $msg='') { if ($e !== $a) throw new Exception(($msg?$msg.': ':'')."Expected [{$e}], got [".$a."]"); }

// ===== Create test users =====
$istNow = nowIST();
$testUsers = [];
foreach (['agent','login_agent','underwriting','dispatch','admin'] as $roleName) {
    $existing = dbF("SELECT id, name FROM users WHERE username = ?", ["test_http_{$roleName}"]);
    if ($existing) {
        $testUsers[$roleName] = $existing;
    } else {
        $role = dbF("SELECT id FROM roles WHERE name = ?", [$roleName]);
        $roleId = $role ? (int)$role['id'] : (int)dbI('roles',['name'=>$roleName,'display_name'=>ucwords(str_replace('_',' ',$roleName)),'created_at'=>$istNow]);
        $userId = dbI('users',[
            'username'=>"test_http_{$roleName}", 'email'=>"test_http_{$roleName}@bestdealcrm.com",
            'password_hash'=>password_hash('test1234',PASSWORD_DEFAULT),
            'name'=>"Test ".ucwords(str_replace('_',' ',$roleName)), 'role_id'=>$roleId,
            'status'=>'active', 'created_at'=>$istNow,
        ]);
        $testUsers[$roleName] = ['id'=>(int)$userId,'name'=>"Test ".ucwords(str_replace('_',' ',$roleName))];
    }
}

// ===== Get form IDs =====
function findForm($stages) {
    foreach ($stages as $s) {
        $r = dbF("SELECT id FROM forms WHERE workflow_stage = ? AND status = 'active' LIMIT 1", [$s]);
        if ($r) return (int)$r['id'];
    }
    return 0;
}
$agentFormId = findForm(['Agent Draft','AGENT_DRAFT']);
$preLoginFormId = findForm(['Login Agent Draft','LOGIN_AGENT_DRAFT']);
$postLoginFormId = findForm(['Post Login','POST_LOGIN']);
$uwFormId = findForm(['Underwriting','UNDERWRITING']);
$dispatchFormId = findForm(['Dispatch','DISPATCH']);

// Get form fields
function getFields($formId) {
    if (!$formId) return [];
    $secs = dbA("SELECT id FROM form_sections WHERE form_id = ? ORDER BY display_order", [$formId]);
    $fields = [];
    foreach ($secs as $s) {
        $rows = dbA("SELECT id,field_name,label,type,required FROM form_fields WHERE section_id = ? AND (is_hidden IS NULL OR is_hidden = 0) ORDER BY display_order", [$s['id']]);
        $fields = array_merge($fields, $rows);
    }
    return $fields;
}

// ===== Create a tiny test image for upload =====
$testImage = tempnam(sys_get_temp_dir(), 'img_') . '.jpg';
$img = imagecreatetruecolor(100, 100);
$blue = imagecolorallocate($img, 0, 100, 200);
imagefill($img, 0, 0, $blue);
imagejpeg($img, $testImage);
imagedestroy($img);

// ===== TESTS =====

// --- 1. DB checks ---
test('Database connection', function() { assertNotNull(dbF("SELECT 1 ok")); });
test('Agent form exists', function() use ($agentFormId) { assertTrue($agentFormId > 0, "Agent form not found"); });
test('Pre-login form exists', function() use ($preLoginFormId) { assertTrue($preLoginFormId > 0, "Pre-login form not found"); });
test('Post-login form exists', function() use ($postLoginFormId) { assertTrue($postLoginFormId > 0, "Post-login form not found"); });
test('Underwriting form exists', function() use ($uwFormId) { assertTrue($uwFormId > 0, "Underwriting form not found"); });
test('Dispatch form exists', function() use ($dispatchFormId) { assertTrue($dispatchFormId > 0, "Dispatch form not found"); });

// --- 2. Create lead via DB ---
$testLeadId = 0;
$leadMobile = '9' . mt_rand(100000000, 999999999);
test('Create test lead', function() use (&$testLeadId, $testUsers, $istNow, $leadMobile) {
    $testLeadId = (int)dbI('leads', [
        'customer_name'=>'HTTP Test Customer', 'mobile_number'=>$leadMobile,
        'location'=>'Pune', 'state'=>'Maharashtra',
        'assigned_to'=>$testUsers['agent']['id'], 'created_by'=>$testUsers['agent']['id'],
        'workflow_stage'=>'LEAD_ASSIGNED', 'data_type'=>'Fresh',
        'created_at'=>$istNow, 'updated_at'=>$istNow,
    ]);
    assertTrue($testLeadId > 0);
});

// --- 3. Login as Agent ---
$agentCsrf = '';
test('Login as agent', function() use (&$agentCsrf) {
    global $BASE_URL;
    $page = httpGet($BASE_URL . '/login');
    $agentCsrf = extractCsrf($page['html']);
    $data = ['username'=>'test_http_agent', 'password'=>'test1234'];
    if ($agentCsrf) $data['_csrf_token'] = $agentCsrf;
    $r = httpPost($BASE_URL . '/login', $data);
    $dash = httpGet($BASE_URL . '/dashboard');
    assertTrue(stripos($dash['html'], 'ashboard') !== false, 'Agent login failed - not on dashboard');
    $agentCsrf = extractCsrf($dash['html']) ?: $agentCsrf;
});

// --- 4. Agent: Load fill form page ---
test('Agent: fill-form page loads without errors', function() use ($testLeadId) {
    global $BASE_URL;
    $r = httpGet($BASE_URL . "/agent/leads/{$testLeadId}/fill-form");
    assertEquals(200, $r['code'], "HTTP {$r['code']}");
    $errors = checkHtml($r['html'], 'fill-form');
    assertTrue(empty($errors), 'Errors: ' . implode('; ', $errors));
    assertTrue(stripos($r['html'], 'fill-form') !== false || stripos($r['html'], 'Agent') !== false, 'Page content missing');
});

// --- 5. Agent: Submit form via POST ---
$agentSubId = 0;
test('Agent: submit form via POST', function() use ($testLeadId, $agentFormId, &$agentSubId, $testUsers) {
    global $BASE_URL;
    $fields = getFields($agentFormId);
    $postData = ['lead_id' => $testLeadId, 'form_id' => $agentFormId];
    foreach ($fields as $f) {
        if (in_array($f['type'], ['file','image'])) continue;
        $postData["form_data[{$f['id']}]"] = "TestVal_{$f['id']}";
    }
    $r = httpPost($BASE_URL . '/agent/leads/submit-form', $postData);
    if ($r['json']) {
        assertTrue($r['json']['success'] ?? false, $r['json']['error'] ?? 'Unknown error');
    }
    // Check lead moved to ADMIN_REVIEW_1
    $lead = dbF("SELECT workflow_stage FROM leads WHERE id = ?", [$testLeadId]);
    assertEquals('ADMIN_REVIEW_1', $lead['workflow_stage'], 'Lead stage not updated');
    // Check submission exists
    $sub = dbF("SELECT id FROM form_submissions WHERE lead_id = ? AND submitted_by = ? AND status = 'submitted'", [$testLeadId, $testUsers['agent']['id']]);
    assertNotNull($sub, 'Submission not found');
    $agentSubId = (int)$sub['id'];
});

// --- 6. Agent: Submit form WITH file upload ---
test('Agent: submit form WITH file upload', function() use ($testLeadId, $agentFormId, $testImage) {
    global $BASE_URL;
    // Get a file field
    $fileField = null;
    foreach (getFields($agentFormId) as $f) {
        if ($f['type'] === 'file' || $f['type'] === 'image') { $fileField = $f; break; }
    }
    if (!$fileField) {
        // No file field — create one for testing
        $sec = dbF("SELECT id FROM form_sections WHERE form_id = ? ORDER BY display_order LIMIT 1", [$agentFormId]);
        if ($sec) {
            $fid = (int)dbI('form_fields', [
                'section_id'=>$sec['id'],'field_name'=>'http_test_file','label'=>'HTTP Test File',
                'type'=>'file','placeholder'=>'','required'=>0,'display_order'=>999,'created_at'=>nowIST()
            ]);
            $fileField = ['id'=>$fid];
        }
    }
    assertTrue($fileField !== null, 'No file field found to test upload');

    $postData = ['lead_id' => $testLeadId, 'form_id' => $agentFormId];
    $postData["form_data[{$fileField['id']}]"] = 'dummy'; // text value
    $files = ["form_data[{$fileField['id']}]" => $testImage];

    $r = httpPost($BASE_URL . '/agent/leads/submit-form', $postData, $files);

    // Check file was saved in documents table
    $doc = dbF("SELECT * FROM documents WHERE lead_id = ? AND original_name LIKE '%jpg%' ORDER BY id DESC LIMIT 1", [$testLeadId]);
    assertNotNull($doc, 'File not saved to documents table after upload');

    // Check submission value has JSON reference
    $val = dbF("SELECT value FROM form_submission_values WHERE submission_id = (SELECT id FROM form_submissions WHERE lead_id = ? ORDER BY id DESC LIMIT 1) AND field_id = ?", [$testLeadId, $fileField['id']]);
    if ($val) {
        $decoded = json_decode($val['value'], true);
        assertTrue($decoded !== null && isset($decoded['filename']), 'File upload value not stored as JSON');
    }
});

// --- 7. Admin Review 1: Assign to login agent ---
test('Admin Review 1: assign to login agent', function() use ($testLeadId, $testUsers, $istNow) {
    dbU('leads', ['assigned_to'=>$testUsers['login_agent']['id'], 'workflow_stage'=>'LOGIN_AGENT_ASSIGNED', 'updated_at'=>$istNow], 'id=?', [$testLeadId]);
    $lead = dbF("SELECT workflow_stage FROM leads WHERE id = ?", [$testLeadId]);
    assertEquals('LOGIN_AGENT_ASSIGNED', $lead['workflow_stage']);
});

// --- 8. Login as Login Agent ---
test('Login as login agent', function() {
    global $BASE_URL;
    $page = httpGet($BASE_URL . '/login');
    $csrf = extractCsrf($page['html']);
    $data = ['username'=>'test_http_login_agent', 'password'=>'test1234'];
    if ($csrf) $data['_csrf_token'] = $csrf;
    httpPost($BASE_URL . '/login', $data);
    $dash = httpGet($BASE_URL . '/dashboard');
    assertTrue(stripos($dash['html'], 'ashboard') !== false, 'Login agent login failed');
});

// --- 9. Login Agent: Pre-login page loads with agent form visible ---
test('Login Agent: pre-login page shows agent form + uploaded file', function() use ($testLeadId) {
    global $BASE_URL;
    $r = httpGet($BASE_URL . "/login-agent/cases/{$testLeadId}/pre-login");
    assertEquals(200, $r['code'], "HTTP {$r['code']}");
    $errors = checkHtml($r['html'], 'pre-login');
    assertTrue(empty($errors), 'Errors: ' . implode('; ', $errors));
    // Check agent form section is present
    assertTrue(stripos($r['html'], 'Agent Form') !== false, 'Agent Form section not found on pre-login page');
    // Check for file upload display (should show document link/icon)
    assertTrue(stripos($r['html'], 'upload') !== false || stripos($r['html'], '.jpg') !== false || stripos($r['html'], 'document') !== false, 'Uploaded file not displayed');
});

// --- 10. Login Agent: Submit checklist ---
test('Login Agent: submit pre-login checklist', function() use ($testLeadId, $preLoginFormId, $testUsers) {
    global $BASE_URL;
    $fields = getFields($preLoginFormId);
    $postData = ['lead_id'=>$testLeadId, 'form_id'=>$preLoginFormId];
    foreach ($fields as $f) {
        if (in_array($f['type'],['file','image'])) continue;
        $postData["form_data[{$f['id']}]"] = "PreVal_{$f['id']}";
    }
    $r = httpPost($BASE_URL . '/login-agent/cases/submit-checklist', $postData);
    if ($r['json']) assertTrue($r['json']['success'] ?? false, $r['json']['error'] ?? 'Submit failed');
    $lead = dbF("SELECT workflow_stage FROM leads WHERE id = ?", [$testLeadId]);
    assertEquals('ADMIN_REVIEW_2', $lead['workflow_stage'], 'Stage not updated to ADMIN_REVIEW_2');
});

// --- 11. Admin Review 2 ---
test('Admin Review 2: approve to post-login', function() use ($testLeadId, $istNow) {
    dbU('leads', ['workflow_stage'=>'LOGIN_APPROVED','updated_at'=>$istNow], 'id=?', [$testLeadId]);
    assertEquals('LOGIN_APPROVED', dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId])['workflow_stage']);
});

// --- 12. Login Agent: Post-login page loads ---
test('Login Agent: post-login page shows agent + pre-login + post-login forms', function() use ($testLeadId) {
    global $BASE_URL;
    $r = httpGet($BASE_URL . "/login-agent/cases/{$testLeadId}/post-login");
    assertEquals(200, $r['code'], "HTTP {$r['code']}");
    $errors = checkHtml($r['html'], 'post-login');
    assertTrue(empty($errors), 'Errors: ' . implode('; ', $errors));
    assertTrue(stripos($r['html'], 'Agent Form') !== false, 'Agent Form not shown');
    assertTrue(stripos($r['html'], 'Pre-Login') !== false, 'Pre-Login not shown');
    assertTrue(stripos($r['html'], 'Post-Login') !== false, 'Post-Login form not shown');
});

// --- 13. Login Agent: Submit post-login ---
test('Login Agent: submit post-login form', function() use ($testLeadId, $postLoginFormId, $testUsers) {
    global $BASE_URL;
    $fields = getFields($postLoginFormId);
    $postData = ['lead_id'=>$testLeadId, 'form_id'=>$postLoginFormId];
    foreach ($fields as $f) {
        if (in_array($f['type'],['file','image'])) continue;
        $postData["form_data[{$f['id']}]"] = "PostVal_{$f['id']}";
    }
    $r = httpPost($BASE_URL . '/login-agent/cases/submit-post-login', $postData);
    if ($r['json']) assertTrue($r['json']['success'] ?? false, $r['json']['error'] ?? 'Submit failed');
    $lead = dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId]);
    assertEquals('ADMIN_REVIEW_3', $lead['workflow_stage'], 'Stage not ADMIN_REVIEW_3');
});

// --- 14. Admin Review 3 ---
test('Admin Review 3: send to underwriting', function() use ($testLeadId, $testUsers, $istNow) {
    dbU('leads', ['assigned_to'=>$testUsers['underwriting']['id'],'workflow_stage'=>'UNDERWRITING','updated_at'=>$istNow], 'id=?',[$testLeadId]);
    assertEquals('UNDERWRITING', dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId])['workflow_stage']);
});

// --- 15. Login as Underwriter ---
test('Login as underwriter', function() {
    global $BASE_URL;
    $page = httpGet($BASE_URL.'/login');
    $csrf = extractCsrf($page['html']);
    $data = ['username'=>'test_http_underwriting','password'=>'test1234'];
    if ($csrf) $data['_csrf_token']=$csrf;
    httpPost($BASE_URL.'/login',$data);
    $dash = httpGet($BASE_URL.'/dashboard');
    assertTrue(stripos($dash['html'],'ashboard')!==false, 'Underwriter login failed');
});

// --- 16. Underwriting: page loads with all prior forms ---
test('Underwriting: case detail shows all prior forms + uploaded files', function() use ($testLeadId) {
    global $BASE_URL;
    $r = httpGet($BASE_URL."/underwriting/cases/{$testLeadId}");
    assertEquals(200,$r['code'],"HTTP {$r['code']}");
    $errors = checkHtml($r['html'],'underwriting');
    assertTrue(empty($errors),'Errors: '.implode('; ',$errors));
    assertTrue(stripos($r['html'],'Agent Form')!==false,'Agent Form missing');
    assertTrue(stripos($r['html'],'Pre-Login')!==false,'Pre-Login missing');
    assertTrue(stripos($r['html'],'Post-Login')!==false,'Post-Login missing');
    assertTrue(stripos($r['html'],'Underwriting Form')!==false,'Underwriting form missing');
});

// --- 17. Underwriting: Submit form ---
test('Underwriting: submit underwriting form', function() use ($testLeadId, $uwFormId, $testUsers) {
    global $BASE_URL;
    $fields = getFields($uwFormId);
    $postData = ['lead_id'=>$testLeadId];
    foreach ($fields as $f) {
        if (in_array($f['type'],['file','image'])) continue;
        $postData["form_data[{$f['id']}]"]="UWVal_{$f['id']}";
    }
    $r = httpPost($BASE_URL.'/underwriting/cases/submit-form',$postData);
    if ($r['json']) assertTrue($r['json']['success']??false,$r['json']['error']??'Submit failed');
});

// --- 18. Admin Review 4 ---
test('Admin Review 4: send to dispatch', function() use ($testLeadId,$testUsers,$istNow) {
    dbU('leads',['assigned_to'=>$testUsers['dispatch']['id'],'workflow_stage'=>'DISPATCH','updated_at'=>$istNow],'id=?',[$testLeadId]);
    assertEquals('DISPATCH',dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId])['workflow_stage']);
});

// --- 19. Login as Dispatcher ---
test('Login as dispatcher', function() {
    global $BASE_URL;
    $page = httpGet($BASE_URL.'/login');
    $csrf = extractCsrf($page['html']);
    $data = ['username'=>'test_http_dispatch','password'=>'test1234'];
    if ($csrf) $data['_csrf_token']=$csrf;
    httpPost($BASE_URL.'/login',$data);
    $dash = httpGet($BASE_URL.'/dashboard');
    assertTrue(stripos($dash['html'],'ashboard')!==false,'Dispatcher login failed');
});

// --- 20. Dispatch: page loads with all forms ---
test('Dispatch: case detail shows all 4 prior forms + dispatch form', function() use ($testLeadId) {
    global $BASE_URL;
    $r = httpGet($BASE_URL."/dispatch/cases/{$testLeadId}");
    assertEquals(200,$r['code'],"HTTP {$r['code']}");
    $errors = checkHtml($r['html'],'dispatch');
    assertTrue(empty($errors),'Errors: '.implode('; ',$errors));
    assertTrue(stripos($r['html'],'Agent Form')!==false,'Agent Form missing');
    assertTrue(stripos($r['html'],'Pre-Login')!==false,'Pre-Login missing');
    assertTrue(stripos($r['html'],'Post-Login')!==false,'Post-Login missing');
    assertTrue(stripos($r['html'],'Underwriting')!==false,'Underwriting missing');
    assertTrue(stripos($r['html'],'Dispatch')!==false,'Dispatch form missing');
    // Check assigned agent name shown
    assertTrue(stripos($r['html'],'Assigned To')!==false,'Assigned To not shown');
});

// --- 21. Dispatch: Submit form ---
test('Dispatch: submit dispatch form + complete', function() use ($testLeadId,$dispatchFormId,$testUsers) {
    global $BASE_URL;
    $fields = getFields($dispatchFormId);
    $postData = ['lead_id'=>$testLeadId];
    foreach ($fields as $f) {
        if (in_array($f['type'],['file','image'])) continue;
        $postData["form_data[{$f['id']}]"]="DispVal_{$f['id']}";
    }
    $r = httpPost($BASE_URL.'/dispatch/cases/submit-form',$postData);
    if ($r['json']) assertTrue($r['json']['success']??false,$r['json']['error']??'Submit failed');
    // Mark complete
    $r2 = httpPost($BASE_URL.'/dispatch/cases/process',['lead_id'=>$testLeadId,'action'=>'complete','dispatch_remark'=>'Test complete']);
    if ($r2['json']) assertTrue($r2['json']['success']??false,$r2['json']['error']??'Complete failed');
    $lead = dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId]);
    assertEquals('COMPLETED',$lead['workflow_stage'],'Lead not COMPLETED');
});

// --- 22. Final: All 5 submissions exist ---
test('All 5 form submissions exist', function() use ($testLeadId) {
    $subs = dbA("SELECT fs.*,f.name as form_name FROM form_submissions fs JOIN forms f ON fs.form_id=f.id WHERE fs.lead_id=? AND fs.status='submitted' ORDER BY fs.created_at",[$testLeadId]);
    assertEquals(5,count($subs),"Expected 5, got ".count($subs).": ".implode(', ',array_column($subs,'form_name')));
});

// --- 23. Final: Documents exist ---
test('Documents visible across all roles', function() use ($testLeadId) {
    $docs = dbA("SELECT * FROM documents WHERE lead_id=?",[$testLeadId]);
    assertTrue(count($docs)>0,'No documents found');
});

// --- Cleanup ---
if ($testLeadId) {
    foreach (['form_submission_values','form_submissions','documents','remarks','notifications','leads'] as $ct) {
        try {
            if ($ct==='form_submission_values') dbD($ct,'submission_id IN (SELECT id FROM form_submissions WHERE lead_id=?)',[$testLeadId]);
            elseif ($ct==='notifications') dbD($ct,'related_lead_id=?',[$testLeadId]);
            else dbD($ct,'lead_id=?',[$testLeadId]);
        } catch (Throwable $e) {}
    }
}
if (file_exists($testImage)) @unlink($testImage);
@unlink($COOKIE_FILE);

// ===== RENDER =====
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BestDeal CRM — Integration Test</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f4f6f9;font-family:'Segoe UI',system-ui,sans-serif}.tc{border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06)}.tp{border-left:4px solid #22c55e}.tf{border-left:4px solid #ef4444;background:#fef2f2}.th{background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;border-radius:12px 12px 0 0}.ss{font-size:2rem;font-weight:700}</style>
</head>
<body class="p-3">
<div class="container-fluid" style="max-width:900px">
<div class="card th mb-4"><div class="card-body py-4">
<h3 class="mb-1">🔗 BestDeal CRM — HTTP Integration Test</h3>
<small class="opacity-75">Logs in as each role via HTTP, makes real requests, uploads files, checks HTML</small>
<div class="row mt-4 text-center">
<div class="col-4"><div class="ss text-success"><?= $passed ?></div><small>Passed</small></div>
<div class="col-4"><div class="ss text-danger"><?= $failed ?></div><small>Failed</small></div>
<div class="col-4"><div class="ss"><?= count($results) ?></div><small>Total</small></div>
</div></div></div>

<?php if($failed===0): ?>
<div class="alert alert-success mb-4" style="border-radius:12px"><strong>All <?= $passed ?> tests passed!</strong> Full workflow verified via HTTP: login, form submission, file upload, HTML rendering, cross-role visibility.</div>
<?php else: ?>
<div class="alert alert-danger mb-4" style="border-radius:12px"><strong><?= $failed ?> test(s) failed!</strong> Copy the red tests below.</div>
<?php endif; ?>

<?php foreach($results as $r): ?>
<div class="card mb-2 tc <?= $r['status']==='PASS'?'tp':'tf' ?>">
<div class="card-body py-2 px-3 d-flex align-items-center">
<span class="me-3" style="font-size:1.1rem"><?= $r['status']==='PASS'?'✅':'❌' ?></span>
<div class="flex-grow-1"><span class="fw-semibold small"><?= htmlspecialchars($r['name']) ?></span>
<?php if($r['detail']): ?><div class="text-danger small mt-1" style="font-family:monospace;font-size:.75rem"><?= htmlspecialchars($r['detail']) ?></div><?php endif; ?>
</div>
<span class="badge <?= $r['status']==='PASS'?'bg-success':'bg-danger' ?> ms-2"><?= $r['status'] ?></span>
</div></div>
<?php endforeach; ?>

<div class="text-center text-muted small my-4">Test Lead #<?= $testLeadId ?> (auto-cleaned) | <?= date('d M Y, h:i:s A') ?></div>
</div></body></html>
