<?php
/**
 * DEBUG PAGE - Check hidden fields
 * DELETE THIS FILE AFTER TESTING
 */

// Load the same environment as the app
$rootPath = dirname(__DIR__);
define('ROOT_PATH', $rootPath);

date_default_timezone_set('Asia/Kolkata');

if (file_exists(ROOT_PATH . '/config/config.php')) {
    require_once ROOT_PATH . '/config/config.php';
}
if (file_exists(ROOT_PATH . '/app/Helpers/Session.php')) {
    require_once ROOT_PATH . '/app/Helpers/Session.php';
}
if (file_exists(ROOT_PATH . '/app/Helpers/Helpers.php')) {
    require_once ROOT_PATH . '/app/Helpers/Helpers.php';
}

echo "<h2>Hidden Fields Debug</h2>";

try {
    $db = Database::getInstance();

    // 1. Check if is_hidden column exists
    echo "<h3>1. Column Check</h3>";
    $cols = $db->fetchAll("SHOW COLUMNS FROM form_fields LIKE 'is_hidden'");
    if (!empty($cols)) {
        echo "<p style='color:green'>✅ is_hidden column EXISTS</p>";
        echo "<pre>" . print_r($cols[0], true) . "</pre>";
    } else {
        echo "<p style='color:red'>❌ is_hidden column DOES NOT EXIST</p>";
    }

    $cols2 = $db->fetchAll("SHOW COLUMNS FROM form_fields LIKE 'field_type'");
    if (!empty($cols2)) {
        echo "<p style='color:green'>✅ field_type column EXISTS</p>";
    } else {
        echo "<p style='color:red'>❌ field_type column DOES NOT EXIST</p>";
    }

    // 2. Count all fields per form
    echo "<h3>2. Fields per form</h3>";
    $forms = $db->fetchAll("SELECT id, name FROM forms");
    foreach ($forms as $f) {
        $total = $db->fetchOne("SELECT COUNT(*) as cnt FROM form_fields WHERE section_id IN (SELECT id FROM form_sections WHERE form_id = ?)", [$f['id']]);
        $hidden = $db->fetchOne("SELECT COUNT(*) as cnt FROM form_fields WHERE section_id IN (SELECT id FROM form_sections WHERE form_id = ?) AND is_hidden = 1", [$f['id']]);
        echo "<p>Form #{$f['id']} ({$f['name']}): Total={$total['cnt']}, Hidden={$hidden['cnt']}</p>";
    }

    // 3. Show all hidden fields with details
    echo "<h3>3. All hidden fields</h3>";
    $hiddenRows = $db->fetchAll(
        "SELECT f.id, f.field_name, f.label, f.type, f.is_hidden, f.section_id, s.name as section_name, s.form_id
         FROM form_fields f
         JOIN form_sections s ON f.section_id = s.id
         WHERE f.is_hidden = 1
         ORDER BY s.form_id, s.display_order"
    );
    if (!empty($hiddenRows)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field ID</th><th>Form ID</th><th>Section</th><th>Field Name</th><th>Label</th><th>Type</th><th>is_hidden</th></tr>";
        foreach ($hiddenRows as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['form_id']}</td>";
            echo "<td>{$row['section_name']}</td>";
            echo "<td>{$row['field_name']}</td>";
            echo "<td>{$row['label']}</td>";
            echo "<td>{$row['type']}</td>";
            echo "<td>{$row['is_hidden']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red'>No hidden fields found at all.</p>";
    }

    // 4. Show ALL fields for form 1 with their is_hidden values
    echo "<h3>4. ALL fields for Form 1 (raw is_hidden values)</h3>";
    $allFields = $db->fetchAll(
        "SELECT f.id, f.field_name, f.label, f.is_hidden, f.field_type, s.name as section_name
         FROM form_fields f
         JOIN form_sections s ON f.section_id = s.id
         WHERE s.form_id = 1
         ORDER BY s.display_order, f.display_order"
    );
    if (!empty($allFields)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field ID</th><th>Section</th><th>Field Name</th><th>Label</th><th>is_hidden</th><th>field_type</th></tr>";
        foreach ($allFields as $row) {
            $style = !empty($row['is_hidden']) ? "background-color:#fff3cd;" : "";
            echo "<tr style='{$style}'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['section_name']}</td>";
            echo "<td>{$row['field_name']}</td>";
            echo "<td>{$row['label']}</td>";
            echo "<td>" . var_export($row['is_hidden'], true) . "</td>";
            echo "<td>" . ($row['field_type'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No fields found for form 1.</p>";
    }

    // 5. Direct AJAX test - same query as the controller
    echo "<h3>5. Direct query test (same as hiddenFields controller)</h3>";
    $formId = 1;
    $testRows = $db->fetchAll(
        "SELECT f.id, f.field_name, f.label, f.type, s.name as section_name
         FROM form_fields f
         JOIN form_sections s ON f.section_id = s.id
         WHERE s.form_id = ? AND f.is_hidden = 1
         ORDER BY s.display_order, f.display_order",
        [$formId]
    );
    echo "<p>Query returned " . count($testRows) . " rows</p>";
    echo "<pre>" . print_r($testRows, true) . "</pre>";

    // 6. Test what is_hidden = 1 actually means (maybe it's NULL?)
    echo "<h3>6. Distinct is_hidden values for form 1</h3>";
    $distinctHidden = $db->fetchAll(
        "SELECT DISTINCT f.is_hidden, COUNT(*) as cnt
         FROM form_fields f
         JOIN form_sections s ON f.section_id = s.id
         WHERE s.form_id = 1
         GROUP BY f.is_hidden"
    );
    echo "<pre>" . print_r($distinctHidden, true) . "</pre>";

} catch (\Throwable $e) {
    echo "<p style='color:red'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><p><a href='/bestdealcrm/admin/form-builder/1/edit'>← Back to Form Builder</a> | <strong>DELETE THIS FILE AFTER TESTING</strong></p>";
