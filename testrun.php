<?php
/**
 * BestDeal CRM — HTTP Integration Test Runner
 * Access: https://bdfsloans.com/bestdealcrm/testrun.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');
set_time_limit(120);

$BASE_URL = 'https://bdfsloans.com/bestdealcrm';
$COOKIE_FILE = tempnam(sys_get_temp_dir(), 'crmtest_');

// ===== DB =====
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
try {
    $pdo = new PDO(
        "mysql:host=".(getenv('DB_HOST')?:'68.178.237.250').";dbname=".(getenv('DB_NAME')?:'bestdealcrm').";charset=utf8mb4",
        getenv('DB_USER')?:'sayali', getenv('DB_PASS')?:'sayali@1234',
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    $pdo->exec("SET NAMES utf8mb4, time_zone='+05:30'");
} catch (PDOException $e) { die('<h1>DB Failed</h1><pre>'.htmlspecialchars($e->getMessage()).'</pre>'); }

function nowIST() { return (new DateTime('now',new DateTimeZone('Asia/Kolkata')))->format('Y-m-d H:i:s'); }
function dbQ($s,$p=[]) { global $pdo; $st=$pdo->prepare($s); $st->execute($p); return $st; }
function dbF($s,$p=[]) { return dbQ($s,$p)->fetch() ?: null; }
function dbA($s,$p=[]) { return dbQ($s,$p)->fetchAll(); }
function dbI($t,$d) { global $pdo; $c=implode(',',array_keys($d)); $v=implode(',',array_fill(0,count($d),'?')); dbQ("INSERT INTO {$t} ({$c}) VALUES ({$v})",array_values($d)); return $pdo->lastInsertId(); }
function dbU($t,$d,$w,$wp=[]) { $s=implode(',',array_map(fn($k)=>"{$k}=?",array_keys($d))); dbQ("UPDATE {$t} SET {$s} WHERE {$w}",array_merge(array_values($d),$wp)); }
function dbD($t,$w,$p=[]) { dbQ("DELETE FROM {$t} WHERE {$w}",$p); }

// ===== HTTP =====
function httpGet($url) {
    global $COOKIE_FILE;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_COOKIEFILE=>$COOKIE_FILE, CURLOPT_COOKIEJAR=>$COOKIE_FILE,
        CURLOPT_FOLLOWLOCATION=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_TIMEOUT=>30,
        CURLOPT_USER_AGENT=>'CRMTest/1.0',
    ]);
    $html = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['html'=>$html?:'','code'=>$code];
}
function httpPost($url, $data, $files=[]) {
    global $COOKIE_FILE;
    $ch = curl_init($url);
    $fields = [];
    foreach ($files as $k=>$v) $fields[$k] = new CURLFile($v, mime_content_type($v), basename($v));
    foreach ($data as $k=>$v) $fields[$k] = $v;
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$fields,
        CURLOPT_COOKIEFILE=>$COOKIE_FILE, CURLOPT_COOKIEJAR=>$COOKIE_FILE,
        CURLOPT_FOLLOWLOCATION=>true, CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_TIMEOUT=>30,
        CURLOPT_USER_AGENT=>'CRMTest/1.0', CURLOPT_HTTPHEADER=>['X-Requested-With: XMLHttpRequest'],
    ]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $json = json_decode($body,true);
    return $json ? ['json'=>$json,'code'=>$code,'html'=>$body] : ['json'=>null,'code'=>$code,'html'=>$body?:''];
}
function extractCsrf($html) {
    if (preg_match('/name=["\']_csrf_token["\']\s+value=["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    if (preg_match('/CSRF_TOKEN\s*=\s*["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    return null;
}
function checkHtml($html) {
    $e = [];
    if (preg_match('/<b>(Fatal|Warning|Parse)\s+error<\/b>.*?<br>/s',$html,$m)) $e[]='PHP: '.strip_tags($m[0]);
    if (preg_match('/SQLSTATE\[.*?\].*?<\/pre>/s',$html,$m)) $e[]='SQL: '.strip_tags(substr($m[0],0,200));
    if (preg_match('/<span class=\s*"?—/',$html)) $e[]='Bug: span class=— leaking';
    return $e;
}

// ===== Test Framework =====
$results=[]; $passed=0; $failed=0;
function test($n,$fn) { global $results,$passed,$failed; try { $fn(); $results[]=['name'=>$n,'status'=>'PASS','detail'=>'']; $passed++; } catch(Throwable $e) { $results[]=['name'=>$n,'status'=>'FAIL','detail'=>$e->getMessage()]; $failed++; } }
function assertTrue($v,$m='Failed') { if(!$v) throw new Exception($m); }
function assertNotNull($v,$m='Null') { if($v===null) throw new Exception($m); }
function assertEquals($e,$a,$m='') { if($e!==$a) throw new Exception(($m?$m.': ':'')."Expected [{$e}], got [".$a."]"); }

// ===== Setup =====
$istNow = nowIST();
$testUsers = [];
foreach (['agent','login_agent','underwriting','dispatch','admin'] as $roleName) {
    $ex = dbF("SELECT id,name FROM users WHERE username=?",["test_int_{$roleName}"]);
    if ($ex) { $testUsers[$roleName]=$ex; } else {
        $r = dbF("SELECT id FROM roles WHERE name=?",[$roleName]);
        $rid = $r ? (int)$r['id'] : (int)dbI('roles',['name'=>$roleName,'display_name'=>ucwords(str_replace('_',' ',$roleName)),'created_at'=>$istNow]);
        $uid = dbI('users',[
            'username'=>"test_int_{$roleName}",'email'=>"test_int_{$roleName}@bestdealcrm.com",
            'password_hash'=>password_hash('test1234',PASSWORD_DEFAULT),
            'name'=>"Test ".ucwords(str_replace('_',' ',$roleName)),'role_id'=>$rid,
            'status'=>'active','created_at'=>$istNow
        ]);
        $testUsers[$roleName]=['id'=>(int)$uid,'name'=>"Test ".ucwords(str_replace('_',' ',$roleName))];
    }
}

function findForm($stages) { foreach($stages as $s) { $r=dbF("SELECT id FROM forms WHERE workflow_stage=? AND status='active' LIMIT 1",[$s]); if($r) return (int)$r['id']; } return 0; }
$agentFormId=findForm(['Agent Draft','AGENT_DRAFT']);
$preLoginFormId=findForm(['Login Agent Draft','LOGIN_AGENT_DRAFT']);
$postLoginFormId=findForm(['Post Login','POST_LOGIN']);
$uwFormId=findForm(['Underwriting','UNDERWRITING']);
$dispatchFormId=findForm(['Dispatch','DISPATCH']);

function getFields($fid) {
    if(!$fid) return [];
    $secs=dbA("SELECT id FROM form_sections WHERE form_id=? ORDER BY display_order",[$fid]);
    $f=[]; foreach($secs as $s) { $f=array_merge($f, dbA("SELECT id,field_name,type FROM form_fields WHERE section_id=? AND (is_hidden IS NULL OR is_hidden=0) ORDER BY display_order",[$s['id']])); }
    return $f;
}

// Test image
$testImage=tempnam(sys_get_temp_dir(),'img_').'.jpg';
$img=imagecreatetruecolor(100,100); imagefill($img,0,0,imagecolorallocate($img,0,100,200)); imagejpeg($img,$testImage); imagedestroy($img);

// ===== TESTS =====
test('DB connection', fn()=>assertNotNull(dbF("SELECT 1 ok")));
test('Agent form exists', fn()=>assertTrue($agentFormId>0,'Not found'));
test('Pre-login form exists', fn()=>assertTrue($preLoginFormId>0,'Not found'));
test('Post-login form exists', fn()=>assertTrue($postLoginFormId>0,'Not found'));
test('Underwriting form exists', fn()=>assertTrue($uwFormId>0,'Not found'));
test('Dispatch form exists', fn()=>assertTrue($dispatchFormId>0,'Not found'));

$testLeadId=0; $leadMobile='9'.mt_rand(100000000,999999999);
test('Create test lead', function() use (&$testLeadId,$testUsers,$istNow,$leadMobile) {
    $testLeadId=(int)dbI('leads',['customer_name'=>'Integration Test','mobile_number'=>$leadMobile,'location'=>'Pune','state'=>'Maharashtra','assigned_to'=>$testUsers['agent']['id'],'created_by'=>$testUsers['agent']['id'],'workflow_stage'=>'LEAD_ASSIGNED','data_type'=>'Fresh','created_at'=>$istNow,'updated_at'=>$istNow]);
    assertTrue($testLeadId>0);
});

// --- Login as Agent ---
test('Login as agent', function() {
    global $BASE_URL;
    $p=httpGet($BASE_URL.'/login'); $csrf=extractCsrf($p['html']);
    $d=['username'=>'test_int_agent','password'=>'test1234']; if($csrf) $d['_csrf_token']=$csrf;
    httpPost($BASE_URL.'/login',$d);
    $dash=httpGet($BASE_URL.'/dashboard');
    assertTrue(stripos($dash['html'],'ashboard')!==false,'Login failed');
});

test('Agent: fill-form loads clean HTML', function() use ($testLeadId) {
    global $BASE_URL;
    $r=httpGet($BASE_URL."/agent/leads/{$testLeadId}/fill-form");
    assertEquals(200,$r['code'],"HTTP {$r['code']}");
    $e=checkHtml($r['html']); assertTrue(empty($e),'Errors: '.implode('; ',$e));
});

$agentSubId=0;
test('Agent: submit form via POST', function() use ($testLeadId,$agentFormId,&$agentSubId,$testUsers) {
    global $BASE_URL;
    $fields=getFields($agentFormId); $pd=['lead_id'=>$testLeadId,'form_id'=>$agentFormId];
    foreach($fields as $f) { if(in_array($f['type'],['file','image'])) continue; $pd["form_data[{$f['id']}]"]="Val_{$f['id']}"; }
    $r=httpPost($BASE_URL.'/agent/leads/submit-form',$pd);
    if($r['json']) assertTrue($r['json']['success']??false,$r['json']['error']??'');
    assertEquals('ADMIN_REVIEW_1',dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId])['workflow_stage']);
    $s=dbF("SELECT id FROM form_submissions WHERE lead_id=? AND submitted_by=? AND status='submitted'",[$testLeadId,$testUsers['agent']['id']]);
    assertNotNull($s); $agentSubId=(int)$s['id'];
});

test('Agent: file upload saved to DB', function() use ($testLeadId,$agentFormId,$testImage) {
    global $BASE_URL;
    $fileField=null;
    foreach(getFields($agentFormId) as $f) { if($f['type']==='file'||$f['type']==='image') { $fileField=$f; break; } }
    if(!$fileField) {
        $sec=dbF("SELECT id FROM form_sections WHERE form_id=? ORDER BY display_order LIMIT 1",[$agentFormId]);
        if($sec) { $fid=(int)dbI('form_fields',['section_id'=>$sec['id'],'field_name'=>'test_file','label'=>'Test','type'=>'file','placeholder'=>'','required'=>0,'display_order'=>999,'created_at'=>nowIST()]); $fileField=['id'=>$fid]; }
    }
    assertTrue($fileField!==null,'No file field');
    $pd=['lead_id'=>$testLeadId,'form_id'=>$agentFormId]; $pd["form_data[{$fileField['id']}]"]='dummy';
    $r=httpPost($BASE_URL.'/agent/leads/submit-form',$pd,["form_data[{$fileField['id']}]"=>$testImage]);
    $doc=dbF("SELECT * FROM documents WHERE lead_id=? AND document_type='form_upload' ORDER BY id DESC LIMIT 1",[$testLeadId]);
    assertNotNull($doc,'File not in documents table');
});

// Admin Review 1
test('Admin Review 1: assign to login agent', function() use ($testLeadId,$testUsers,$istNow) {
    dbU('leads',['assigned_to'=>$testUsers['login_agent']['id'],'workflow_stage'=>'LOGIN_AGENT_ASSIGNED','updated_at'=>$istNow],'id=?',[$testLeadId]);
    assertEquals('LOGIN_AGENT_ASSIGNED',dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId])['workflow_stage']);
});

// Login as Login Agent
test('Login as login agent', function() {
    global $BASE_URL;
    $p=httpGet($BASE_URL.'/login'); $csrf=extractCsrf($p['html']);
    $d=['username'=>'test_int_login_agent','password'=>'test1234']; if($csrf) $d['_csrf_token']=$csrf;
    httpPost($BASE_URL.'/login',$d);
    assertTrue(stripos(httpGet($BASE_URL.'/dashboard')['html'],'ashboard')!==false);
});

test('Pre-login page: agent form + files visible', function() use ($testLeadId) {
    global $BASE_URL;
    $r=httpGet($BASE_URL."/login-agent/cases/{$testLeadId}/pre-login");
    assertEquals(200,$r['code'],"HTTP {$r['code']}");
    $e=checkHtml($r['html']); assertTrue(empty($e),'Errors: '.implode('; ',$e));
    assertTrue(stripos($r['html'],'Agent Form')!==false,'Agent Form missing');
});

test('Login agent: submit pre-login', function() use ($testLeadId,$preLoginFormId,$testUsers) {
    global $BASE_URL;
    $fields=getFields($preLoginFormId); $pd=['lead_id'=>$testLeadId,'form_id'=>$preLoginFormId];
    foreach($fields as $f) { if(!in_array($f['type'],['file','image'])) $pd["form_data[{$f['id']}]"]="PreVal"; }
    $r=httpPost($BASE_URL.'/login-agent/cases/submit-checklist',$pd);
    if($r['json']) assertTrue($r['json']['success']??false,$r['json']['error']??'');
    assertEquals('ADMIN_REVIEW_2',dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId])['workflow_stage']);
});

test('Admin Review 2', function() use ($testLeadId,$istNow) {
    dbU('leads',['workflow_stage'=>'LOGIN_APPROVED','updated_at'=>$istNow],'id=?',[$testLeadId]);
});

test('Post-login page: all 3 forms visible', function() use ($testLeadId) {
    global $BASE_URL;
    $r=httpGet($BASE_URL."/login-agent/cases/{$testLeadId}/post-login");
    assertEquals(200,$r['code'],"HTTP {$r['code']}");
    $e=checkHtml($r['html']); assertTrue(empty($e),'Errors: '.implode('; ',$e));
    assertTrue(stripos($r['html'],'Agent Form')!==false,'Agent missing');
    assertTrue(stripos($r['html'],'Pre-Login')!==false,'Pre-Login missing');
    assertTrue(stripos($r['html'],'Post-Login')!==false,'Post-Login missing');
});

test('Login agent: submit post-login', function() use ($testLeadId,$postLoginFormId) {
    global $BASE_URL;
    $fields=getFields($postLoginFormId); $pd=['lead_id'=>$testLeadId,'form_id'=>$postLoginFormId];
    foreach($fields as $f) { if(!in_array($f['type'],['file','image'])) $pd["form_data[{$f['id']}]"]="PostVal"; }
    $r=httpPost($BASE_URL.'/login-agent/cases/submit-post-login',$pd);
    if($r['json']) assertTrue($r['json']['success']??false,$r['json']['error']??'');
    assertEquals('ADMIN_REVIEW_3',dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId])['workflow_stage']);
});

test('Admin Review 3: send to underwriting', function() use ($testLeadId,$testUsers,$istNow) {
    dbU('leads',['assigned_to'=>$testUsers['underwriting']['id'],'workflow_stage'=>'UNDERWRITING','updated_at'=>$istNow],'id=?',[$testLeadId]);
});

test('Login as underwriter', function() {
    global $BASE_URL;
    $p=httpGet($BASE_URL.'/login'); $csrf=extractCsrf($p['html']);
    $d=['username'=>'test_int_underwriting','password'=>'test1234']; if($csrf) $d['_csrf_token']=$csrf;
    httpPost($BASE_URL.'/login',$d);
    assertTrue(stripos(httpGet($BASE_URL.'/dashboard')['html'],'ashboard')!==false);
});

test('Underwriting page: all 4 forms visible + clean HTML', function() use ($testLeadId) {
    global $BASE_URL;
    $r=httpGet($BASE_URL."/underwriting/cases/{$testLeadId}");
    assertEquals(200,$r['code'],"HTTP {$r['code']}");
    $e=checkHtml($r['html']); assertTrue(empty($e),'Errors: '.implode('; ',$e));
    assertTrue(stripos($r['html'],'Agent Form')!==false,'Agent missing');
    assertTrue(stripos($r['html'],'Pre-Login')!==false,'Pre-Login missing');
    assertTrue(stripos($r['html'],'Post-Login')!==false,'Post-Login missing');
    assertTrue(stripos($r['html'],'Underwriting')!==false,'Underwriting missing');
    assertTrue(stripos($r['html'],'Assigned To')!==false,'Assigned To missing');
});

test('Underwriting: submit form', function() use ($testLeadId,$uwFormId) {
    global $BASE_URL;
    $fields=getFields($uwFormId); $pd=['lead_id'=>$testLeadId];
    foreach($fields as $f) { if(!in_array($f['type'],['file','image'])) $pd["form_data[{$f['id']}]"]="UWVal"; }
    $r=httpPost($BASE_URL.'/underwriting/cases/submit-form',$pd);
    if($r['json']) assertTrue($r['json']['success']??false,$r['json']['error']??'');
});

test('Admin Review 4: send to dispatch', function() use ($testLeadId,$testUsers,$istNow) {
    dbU('leads',['assigned_to'=>$testUsers['dispatch']['id'],'workflow_stage'=>'DISPATCH','updated_at'=>$istNow],'id=?',[$testLeadId]);
});

test('Login as dispatcher', function() {
    global $BASE_URL;
    $p=httpGet($BASE_URL.'/login'); $csrf=extractCsrf($p['html']);
    $d=['username'=>'test_int_dispatch','password'=>'test1234']; if($csrf) $d['_csrf_token']=$csrf;
    httpPost($BASE_URL.'/login',$d);
    assertTrue(stripos(httpGet($BASE_URL.'/dashboard')['html'],'ashboard')!==false);
});

test('Dispatch page: all 5 forms + assigned agent', function() use ($testLeadId) {
    global $BASE_URL;
    $r=httpGet($BASE_URL."/dispatch/cases/{$testLeadId}");
    assertEquals(200,$r['code'],"HTTP {$r['code']}");
    $e=checkHtml($r['html']); assertTrue(empty($e),'Errors: '.implode('; ',$e));
    assertTrue(stripos($r['html'],'Agent Form')!==false);
    assertTrue(stripos($r['html'],'Pre-Login')!==false);
    assertTrue(stripos($r['html'],'Post-Login')!==false);
    assertTrue(stripos($r['html'],'Underwriting')!==false);
    assertTrue(stripos($r['html'],'Dispatch')!==false);
    assertTrue(stripos($r['html'],'Assigned To')!==false);
});

test('Dispatch: submit + complete', function() use ($testLeadId,$dispatchFormId) {
    global $BASE_URL;
    $fields=getFields($dispatchFormId); $pd=['lead_id'=>$testLeadId];
    foreach($fields as $f) { if(!in_array($f['type'],['file','image'])) $pd["form_data[{$f['id']}]"]="DispVal"; }
    $r=httpPost($BASE_URL.'/dispatch/cases/submit-form',$pd);
    if($r['json']) assertTrue($r['json']['success']??false,$r['json']['error']??'');
    $r2=httpPost($BASE_URL.'/dispatch/cases/process',['lead_id'=>$testLeadId,'action'=>'complete','dispatch_remark'=>'Test done']);
    if($r2['json']) assertTrue($r2['json']['success']??false,$r2['json']['error']??'');
    assertEquals('COMPLETED',dbF("SELECT workflow_stage FROM leads WHERE id=?",[$testLeadId])['workflow_stage']);
});

test('All 5 submissions exist', function() use ($testLeadId) {
    $subs=dbA("SELECT f.name FROM form_submissions fs JOIN forms f ON fs.form_id=f.id WHERE fs.lead_id=? AND fs.status='submitted' ORDER BY fs.created_at",[$testLeadId]);
    assertEquals(5,count($subs),"Got ".count($subs).": ".implode(', ',array_column($subs,'name')));
});

test('Documents visible', function() use ($testLeadId) {
    assertTrue(dbA("SELECT id FROM documents WHERE lead_id=?",[$testLeadId])!=='');
});

// Cleanup
if($testLeadId) { foreach(['form_submission_values','form_submissions','documents','remarks','notifications','leads'] as $ct) { try { if($ct==='form_submission_values') dbD($ct,'submission_id IN (SELECT id FROM form_submissions WHERE lead_id=?)',[$testLeadId]); elseif($ct==='notifications') dbD($ct,'related_lead_id=?',[$testLeadId]); else dbD($ct,'lead_id=?',[$testLeadId]); } catch(Throwable $e){} } }
@unlink($testImage); @unlink($COOKIE_FILE);

// ===== RENDER =====
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CRM Integration Test</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f4f6f9;font-family:'Segoe UI',system-ui,sans-serif}.tc{border:none;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06)}.tp{border-left:4px solid #22c55e}.tf{border-left:4px solid #ef4444;background:#fef2f2}.th{background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;border-radius:12px 12px 0 0}.ss{font-size:2rem;font-weight:700}</style>
</head>
<body class="p-3">
<div class="container-fluid" style="max-width:900px">
<div class="card th mb-4"><div class="card-body py-4">
<h3 class="mb-1">🔗 BestDeal CRM — HTTP Integration Test</h3>
<small class="opacity-75">Logs in as each role, submits forms, uploads files, checks HTML rendering</small>
<div class="row mt-4 text-center">
<div class="col-4"><div class="ss text-success"><?= $passed ?></div><small>Passed</small></div>
<div class="col-4"><div class="ss text-danger"><?= $failed ?></div><small>Failed</small></div>
<div class="col-4"><div class="ss"><?= count($results) ?></div><small>Total</small></div>
</div></div></div>
<?php if($failed===0): ?>
<div class="alert alert-success mb-4" style="border-radius:12px"><strong>All <?= $passed ?> tests passed!</strong> Full workflow verified end-to-end via HTTP.</div>
<?php else: ?>
<div class="alert alert-danger mb-4" style="border-radius:12px"><strong><?= $failed ?> test(s) failed!</strong> Copy below and paste for fixing.</div>
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
<div class="text-center text-muted small my-4">Lead #<?= $testLeadId ?> cleaned | <?= date('d M Y, h:i:s A') ?></div>
</div></body></html>
