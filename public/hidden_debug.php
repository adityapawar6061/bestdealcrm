<?php
/**
 * DEBUG PAGE - Check hidden fields
 * DELETE THIS FILE AFTER TESTING
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('Asia/Kolkata');

$rootPath = dirname(__DIR__);
define('ROOT_PATH', $rootPath);

// Load autoloader (same as public/index.php)
spl_autoload_register(function ($class) {
    $map = array(
        'Database' => ROOT_PATH . '/config/database.php',
        'Router'   => ROOT_PATH . '/config/Router.php',
    );
    if (isset($map[$class])) {
        if (file_exists($map[$class])) {
            require_once $map[$class];
        }
        return;
    }
    $parts = explode(chr(92), $class);
    if (count($parts) < 2) return;
    $relativePath = implode(DIRECTORY_SEPARATOR, $parts);
    $filePath = ROOT_PATH . '/app/' . $relativePath . '.php';
    if (file_exists($filePath)) {
        require_once $filePath;
        return;
    }
});

if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
}
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) {
    require_once ROOT_PATH . '/app/Helpers/Session.php';
}
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) {
    require_once ROOT_PATH . '/app/Helpers/Helpers.php';
}

echo "<!DOCTYPE html><html><head><title>Hidden Fields Debug</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5}";
echo "table{border-collapse:collapse;margin:10px 0}td,th{border:1px solid #ccc;padding:6px 10px;text-align:left}";
echo "h2{color:#333}h3{color:#666;margin-top:20px}.ok{color:green}.err{color:red}";
echo "</style></head><body>";

echo "<h2>🔍 Hidden Fields Debug</h2>";

try {
    $db = Database::getInstance();

    // 1. Check if is_hidden column exists
    echo "<h3>1. Column Check</h3>";
    $cols = $db->fetchAll("SHOW COLUMNS FROM form_fields LIKE 'is_hidden'");
    if (!empty($cols)) {
        echo "<p class='ok'>✅ is_hidden column EXISTS</p>";
        echo "<pre>" . htmlspecialchars(print_r($cols[0], true)) . "</pre>";
    } else {
        echo "<p class='err'>❌ is_hidden column DOES NOT EXIST in form_fields</p>";
    }

    // 2. Count all fields per form
    echo "<h3>2. Fields per form</h3>";
    $forms = $db->fetchAll("SELECT id, name FROM forms");
    echo "<table><tr><th>Form ID</th><th>Form Name</th><th>Total Fields</th><th>Hidden Fields</th></tr>";
    foreach ($forms as $f) {
        $total = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM form_fields WHERE section_id IN (SELECT id FROM form_sections WHERE form_id = ?)",
            [$f['id']]
        );
        try {
            $hidden = $db->fetchOne(
                "SELECT COUNT(*) as cnt FROM form_fields WHERE section_id IN (SELECT id FROM form_sections WHERE form_id = ?) AND is_hidden = 1",
                [$f['id']]
            );
            $hiddenCnt = $hidden['cnt'] ?? 'N/A';
        } catch (\Throwable $e) {
            $hiddenCnt = 'ERROR: ' . $e->getMessage();
        }
        echo "<tr><td>{$f['id']}</td><td>" . htmlspecialchars($f['name']) . "</td><td>{$total['cnt']}</td><td>{$hiddenCnt}</td></tr>";
    }
    echo "</table>";

    // 3. ALL fields for form 1 with is_hidden raw values
    echo "<h3>3. ALL fields for Form 1 (raw is_hidden values)</h3>";
    try {
        $allFields = $db->fetchAll(
            "SELECT f.id, f.field_name, f.label, f.is_hidden, f.field_type, f.type, s.name as section_name
             FROM form_fields f
             JOIN form_sections s ON f.section_id = s.id
             WHERE s.form_id = 1
             ORDER BY s.display_order, f.display_order"
        );
        if (!empty($allFields)) {
            echo "<table><tr><th>ID</th><th>Section</th><th>Field Name</th><th>Label</th><th>is_hidden</th><th>field_type</th><th>type</th></tr>";
            foreach ($allFields as $row) {
                $bg = (!empty($row['is_hidden']) && $row['is_hidden'] != '0') ? "background:#fff3cd;" : "";
                $isH = htmlspecialchars(var_export($row['is_hidden'], true));
                $ft = htmlspecialchars($row['field_type'] ?? 'NULL');
                $tp = htmlspecialchars($row['type'] ?? '');
                $fn = htmlspecialchars($row['field_name'] ?? '');
                $lb = htmlspecialchars($row['label'] ?? '');
                $sn = htmlspecialchars($row['section_name'] ?? '');
                echo "<tr style='{$bg}'><td>{$row['id']}</td><td>{$sn}</td><td>{$fn}</td><td>{$lb}</td><td>{$isH}</td><td>{$ft}</td><td>{$tp}</td></tr>";
            }
            echo "</table>";
            echo "<p>Total: " . count($allFields) . " fields</p>";
        } else {
            echo "<p class='err'>No fields found for form 1.</p>";
        }
    } catch (\Throwable $e) {
        echo "<p class='err'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // 4. Distinct is_hidden values
    echo "<h3>4. Distinct is_hidden values for form 1</h3>";
    try {
        $distinctHidden = $db->fetchAll(
            "SELECT DISTINCT f.is_hidden, COUNT(*) as cnt
             FROM form_fields f
             JOIN form_sections s ON f.section_id = s.id
             WHERE s.form_id = 1
             GROUP BY f.is_hidden"
        );
        echo "<pre>" . htmlspecialchars(print_r($distinctHidden, true)) . "</pre>";
    } catch (\Throwable $e) {
        echo "<p class='err'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // 5. Test the exact controller query
    echo "<h3>5. Direct query test (same as hiddenFields controller for form 1)</h3>";
    try {
        $formId = 1;
        $testRows = $db->fetchAll(
            "SELECT f.id, f.field_name, f.label, f.type, s.name as section_name
             FROM form_fields f
             JOIN form_sections s ON f.section_id = s.id
             WHERE s.form_id = ? AND f.is_hidden = 1
             ORDER BY s.display_order, f.display_order",
            [$formId]
        );
        echo "<p>Query returned <strong>" . count($testRows) . "</strong> hidden fields</p>";
        if (!empty($testRows)) {
            echo "<pre>" . htmlspecialchars(print_r($testRows, true)) . "</pre>";
        }
    } catch (\Throwable $e) {
        echo "<p class='err'>QUERY ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }

    // 6. Also test: what if is_hidden is NULL?
    echo "<h3>6. Test: fields where is_hidden IS NULL or is_hidden = 0 for form 1</h3>";
    try {
        $visible = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM form_fields f JOIN form_sections s ON f.section_id = s.id WHERE s.form_id = 1 AND (f.is_hidden IS NULL OR f.is_hidden = 0)"
        );
        echo "<p>Visible fields: " . ($visible['cnt'] ?? 'N/A') . "</p>";
        $hidden2 = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM form_fields f JOIN form_sections s ON f.section_id = s.id WHERE s.form_id = 1 AND f.is_hidden = 1"
        );
        echo "<p>Hidden fields (is_hidden = 1): " . ($hidden2['cnt'] ?? 'N/A') . "</p>";
        $hidden3 = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM form_fields f JOIN form_sections s ON f.section_id = s.id WHERE s.form_id = 1 AND f.is_hidden IS NOT NULL AND f.is_hidden != 0 AND f.is_hidden != 1"
        );
        echo "<p>Fields with other is_hidden values: " . ($hidden3['cnt'] ?? 'N/A') . "</p>";
    } catch (\Throwable $e) {
        echo "<p class='err'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

} catch (\Throwable $e) {
    echo "<p class='err'>FATAL ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><p><a href='/bestdealcrm/admin/form-builder/1/edit'>← Back to Form Builder</a> | <strong>DELETE THIS FILE AFTER TESTING</strong></p>";
echo "</body></html>";
