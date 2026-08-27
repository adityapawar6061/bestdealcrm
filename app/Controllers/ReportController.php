<?php
namespace Controllers;

class ReportController extends BaseController
{
    // Static lead table columns
    private array $leadColumns = [
        'id'                => ['label' => 'Lead ID', 'table' => 'leads'],
        'customer_name'     => ['label' => 'Customer Name', 'table' => 'leads'],
        'mobile_number'     => ['label' => 'Mobile Number', 'table' => 'leads'],
        'location'          => ['label' => 'Location', 'table' => 'leads'],
        'state'             => ['label' => 'State', 'table' => 'leads'],
        'existing_la'       => ['label' => 'Existing LA', 'table' => 'leads'],
        'salary'            => ['label' => 'Salary', 'table' => 'leads'],
        'actual_salary'     => ['label' => 'Actual Salary', 'table' => 'leads'],
        'dtmf_input'        => ['label' => 'DTMF Input', 'table' => 'leads'],
        'response_date'     => ['label' => 'Response Date', 'table' => 'leads'],
        'data_type'         => ['label' => 'Data Type', 'table' => 'leads'],
        'bank_name'         => ['label' => 'Bank Name', 'table' => 'leads'],
        'current_status'    => ['label' => 'Current Status', 'table' => 'leads'],
        'update_status'     => ['label' => 'Update Status', 'table' => 'leads'],
        'remark'            => ['label' => 'Remark', 'table' => 'leads'],
        'workflow_stage'    => ['label' => 'Workflow Stage', 'table' => 'leads'],
        'assigned_to_name'  => ['label' => 'Assigned Agent', 'table' => 'users'],
        'pan_number'        => ['label' => 'PAN Number', 'table' => 'leads'],
        'created_at'        => ['label' => 'Created Date', 'table' => 'leads'],
        'updated_at'        => ['label' => 'Updated Date', 'table' => 'leads'],
    ];

    private array $dynamicLeadColumns = [
        'disposition'       => ['label' => 'Disposition', 'table' => 'leads'],
        'agent_disposition' => ['label' => 'Agent Disposition', 'table' => 'leads'],
        'agent_remark'      => ['label' => 'Agent Remarks', 'table' => 'leads'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `report_templates` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `columns_config` TEXT NOT NULL,
            `created_by` INT UNSIGNED,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Get all form builder fields grouped by form → section
     */
    private function getFormBuilderColumns(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $forms = $this->db->fetchAll("SELECT id, name, code FROM forms ORDER BY id");
        $result = [];

        foreach ($forms as $form) {
            $sections = $this->db->fetchAll(
                "SELECT id, name FROM form_sections WHERE form_id = ? ORDER BY display_order",
                [$form['id']]
            );
            $formSections = [];
            foreach ($sections as $section) {
                $fields = $this->db->fetchAll(
                    "SELECT id, field_name, label, type FROM form_fields WHERE section_id = ? AND (is_hidden IS NULL OR is_hidden = 0) AND type NOT IN ('heading', 'subheading') ORDER BY display_order",
                    [$section['id']]
                );
                if (!empty($fields)) {
                    $formSections[] = [
                        'name'   => $section['name'],
                        'fields' => $fields,
                    ];
                }
            }
            if (!empty($formSections)) {
                $result[] = [
                    'id'       => $form['id'],
                    'name'     => $form['name'],
                    'code'     => $form['code'],
                    'sections' => $formSections,
                ];
            }
        }

        $cache = $result;
        return $result;
    }

    /**
     * List all report templates
     */
    public function index(): void
    {
        $templates = $this->db->fetchAll(
            "SELECT rt.*, u.name as created_by_name 
             FROM report_templates rt 
             LEFT JOIN users u ON rt.created_by = u.id 
             ORDER BY rt.created_at DESC"
        );

        $this->view('admin/reports/index', [
            'title'     => 'Reports & Export',
            'templates' => $templates,
        ]);
    }

    /**
     * Create new report template
     */
    public function create(): void
    {
        $existingDynamic = [];
        foreach ($this->dynamicLeadColumns as $key => $col) {
            $exists = $this->db->fetchOne(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = ?",
                [$key]
            );
            if ($exists) $existingDynamic[$key] = $col;
        }

        $this->view('admin/reports/create', [
            'title'            => 'Create Report Template',
            'leadColumns'      => $this->leadColumns,
            'dynamicColumns'   => $existingDynamic,
            'formColumns'      => $this->getFormBuilderColumns(),
        ]);
    }

    /**
     * Store new report template
     */
    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $name = trim($_POST['template_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $columns = $_POST['columns'] ?? [];

        if (empty($name)) {
            $this->json(['error' => 'Template name is required.'], 400);
            return;
        }
        if (empty($columns)) {
            $this->json(['error' => 'Select at least one column.'], 400);
            return;
        }

        $allLabels = $this->getAllColumnLabels();
        $columnsConfig = [];
        foreach ($columns as $colName) {
            $label = $allLabels[$colName] ?? $colName;
            $columnsConfig[] = ['field' => $colName, 'label' => $label];
        }

        $user = currentUser();
        $templateId = $this->db->insert('report_templates', [
            'name'           => $name,
            'description'    => $description,
            'columns_config' => json_encode($columnsConfig),
            'created_by'     => $user['id'],
            'created_at'     => nowIST(),
        ]);

        logActivity($user['id'], 'report_template_created', 'report_template', (int)$templateId);
        $this->json(['success' => true, 'message' => 'Template created.', 'id' => $templateId]);
    }

    /**
     * Edit report template
     */
    public function edit(int $id): void
    {
        $template = $this->db->fetchOne("SELECT * FROM report_templates WHERE id = ?", [$id]);
        if (!$template) {
            $this->redirect('/admin/reports', 'error', 'Template not found.');
            return;
        }
        $template['columns_config'] = json_decode($template['columns_config'], true) ?? [];

        $existingDynamic = [];
        foreach ($this->dynamicLeadColumns as $key => $col) {
            $exists = $this->db->fetchOne(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = ?",
                [$key]
            );
            if ($exists) $existingDynamic[$key] = $col;
        }

        $this->view('admin/reports/create', [
            'title'            => 'Edit Report Template',
            'leadColumns'      => $this->leadColumns,
            'dynamicColumns'   => $existingDynamic,
            'formColumns'      => $this->getFormBuilderColumns(),
            'template'         => $template,
            'editMode'         => true,
        ]);
    }

    /**
     * Update report template
     */
    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $id = (int)($_POST['template_id'] ?? 0);
        $name = trim($_POST['template_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $columns = $_POST['columns'] ?? [];

        if (!$id || empty($name) || empty($columns)) {
            $this->json(['error' => 'All fields required.'], 400);
            return;
        }

        $allLabels = $this->getAllColumnLabels();
        $columnsConfig = [];
        foreach ($columns as $colName) {
            $label = $allLabels[$colName] ?? $colName;
            $columnsConfig[] = ['field' => $colName, 'label' => $label];
        }

        $this->db->update('report_templates', [
            'name'           => $name,
            'description'    => $description,
            'columns_config' => json_encode($columnsConfig),
            'updated_at'     => nowIST(),
        ], 'id = ?', [$id]);

        $this->json(['success' => true, 'message' => 'Template updated.']);
    }

    /**
     * Delete report template
     */
    public function delete(): void
    {
        $id = (int)($_POST['template_id'] ?? 0);
        if ($id) {
            $this->db->delete('report_templates', 'id = ?', [$id]);
            $this->json(['success' => true, 'message' => 'Template deleted.']);
        }
    }

    /**
     * Generate report page — select date range + filters, preview data
     */
    public function generate(int $id): void
    {
        $template = $this->db->fetchOne("SELECT * FROM report_templates WHERE id = ?", [$id]);
        if (!$template) {
            $this->redirect('/admin/reports', 'error', 'Template not found.');
            return;
        }
        $template['columns_config'] = json_decode($template['columns_config'], true) ?? [];

        $agents = $this->db->fetchAll(
            "SELECT u.id, u.name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('agent', 'login_agent', 'team_leader', 'underwriting', 'dispatch') AND u.status = 'active' ORDER BY u.name"
        );
        $banks = $this->db->fetchAll("SELECT DISTINCT bank_name FROM leads WHERE bank_name IS NOT NULL AND bank_name != '' ORDER BY bank_name");
        $stages = $this->db->fetchAll("SELECT DISTINCT workflow_stage FROM leads WHERE workflow_stage IS NOT NULL ORDER BY workflow_stage");

        if (isset($_GET['preview'])) {
            $this->previewData($template);
            return;
        }

        $this->view('admin/reports/generate', [
            'title'     => 'Generate Report: ' . htmlspecialchars($template['name']),
            'template'  => $template,
            'agents'    => $agents,
            'banks'     => array_column($banks, 'bank_name'),
            'stages'    => array_column($stages, 'workflow_stage'),
        ]);
    }

    /**
     * Export data as CSV
     */
    public function export(int $id): void
    {
        $template = $this->db->fetchOne("SELECT * FROM report_templates WHERE id = ?", [$id]);
        if (!$template) { $this->json(['error' => 'Not found.'], 404); return; }

        $columnsConfig = json_decode($template['columns_config'], true) ?? [];
        if (empty($columnsConfig)) { $this->json(['error' => 'No columns.'], 400); return; }

        list($where, $params) = $this->buildFilterWhere();
        $data = $this->queryReportData($columnsConfig, $where, $params);

        logActivity(currentUser()['id'], 'report_exported', 'report_template', $id, null,
            json_encode(['rows' => count($data['rows']), 'columns' => count($columnsConfig)]));

        $filename = $template['name'] . '_' . date('Y-m-d_His') . '.csv';
        if (ob_get_level()) ob_end_clean();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $fp = fopen('php://output', 'w');
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

        // Header
        fputcsv($fp, array_column($columnsConfig, 'label'));

        // Data
        foreach ($data['rows'] as $row) {
            $dataRow = [];
            foreach ($columnsConfig as $col) {
                $val = $row[$col['field']] ?? '';
                if (in_array($col['field'], ['salary', 'actual_salary', 'existing_la']) && is_numeric($val) && $val > 0) {
                    $dataRow[] = '₹' . number_format($val);
                } else {
                    $dataRow[] = $val;
                }
            }
            fputcsv($fp, $dataRow);
        }

        fclose($fp);
        exit;
    }

    /**
     * Preview data via AJAX
     */
    private function previewData(array $template): void
    {
        $columnsConfig = $template['columns_config'] ?? [];
        list($where, $params) = $this->buildFilterWhere();

        $countData = $this->queryReportData($columnsConfig, $where, $params, true);
        $data = $this->queryReportData($columnsConfig, $where, $params, false, 100);

        $this->json([
            'success' => true,
            'columns' => $columnsConfig,
            'rows'    => $data['rows'],
            'total'   => $countData['total'],
            'showing' => count($data['rows']),
        ]);
    }

    /**
     * Build WHERE clause from filter params
     */
    private function buildFilterWhere(): array
    {
        $where = '1=1';
        $params = [];

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $dateField = $_GET['date_field'] ?? 'created_at';
        $agentFilter = $_GET['agent_id'] ?? '';
        $bankFilter = $_GET['bank_name'] ?? '';
        $stageFilter = $_GET['workflow_stage'] ?? '';
        $search = $_GET['search'] ?? '';

        $allowedDateFields = ['created_at', 'updated_at', 'response_date'];
        if (!in_array($dateField, $allowedDateFields)) $dateField = 'created_at';

        if ($dateFrom) { $where .= " AND l.{$dateField} >= ?"; $params[] = $dateFrom . ' 00:00:00'; }
        if ($dateTo) { $where .= " AND l.{$dateField} <= ?"; $params[] = $dateTo . ' 23:59:59'; }
        if ($agentFilter) { $where .= ' AND l.assigned_to = ?'; $params[] = (int)$agentFilter; }
        if ($bankFilter) { $where .= ' AND l.bank_name = ?'; $params[] = $bankFilter; }
        if ($stageFilter) { $where .= ' AND l.workflow_stage = ?'; $params[] = $stageFilter; }
        if ($search) { $s = '%' . $search . '%'; $where .= ' AND (l.customer_name LIKE ? OR l.mobile_number LIKE ? OR l.id = ?)'; $params[] = $s; $params[] = $s; $params[] = $search; }

        return [$where, $params];
    }

    /**
     * Query report data — static columns from leads + dynamic columns from form submissions
     */
    private function queryReportData(array $columnsConfig, string $where, array $params, bool $countOnly = false, int $limit = 100): array
    {
        // Separate static lead columns from form builder columns
        $staticFields = [];
        $formFields = []; // ['form_id' => [field_name => label]]

        $allKnown = array_merge(
            array_keys($this->leadColumns),
            array_keys($this->dynamicLeadColumns)
        );

        foreach ($columnsConfig as $col) {
            $field = $col['field'];
            if (in_array($field, $allKnown)) {
                $staticFields[] = $col;
            } else {
                // This is a form builder field — extract form_id and field_name
                // Format: form_{formId}_{fieldName}
                if (preg_match('/^form_(\d+)_(.+)$/', $field, $m)) {
                    $formId = (int)$m[1];
                    $fieldName = $m[2];
                    $formFields[$formId][$fieldName] = $col['label'];
                }
            }
        }

        // COUNT query
        if ($countOnly) {
            $total = $this->db->count('leads l', $where, $params);
            return ['total' => $total, 'rows' => []];
        }

        // Build SQL for static columns
        $sqlFields = [];
        foreach ($staticFields as $col) {
            $field = $col['field'];
            if ($field === 'assigned_to_name') {
                $sqlFields[] = 'u.name as assigned_to_name';
            } else {
                $sqlFields[] = "l.{$field}";
            }
        }
        $sqlFields[] = 'l.id as _lead_id'; // Always include lead_id for joining

        $sql = "SELECT " . implode(', ', $sqlFields) . "
                FROM leads l 
                LEFT JOIN users u ON l.assigned_to = u.id 
                WHERE {$where} 
                ORDER BY l.created_at DESC 
                LIMIT {$limit}";

        $rows = $this->db->fetchAll($sql, $params);

        // If no form fields selected, return early
        if (empty($formFields)) {
            return ['total' => count($rows), 'rows' => $rows];
        }

        // Get all lead IDs
        $leadIds = array_column($rows, '_lead_id');
        if (empty($leadIds)) return ['total' => 0, 'rows' => []];

        // Fetch ALL form submission values for these leads in ONE query
        $placeholders = implode(',', array_fill(0, count($leadIds), '?'));
        $allFieldIds = [];
        foreach ($formFields as $formId => $fields) {
            foreach (array_keys($fields) as $fn) {
                // We need to look up field IDs — do it in bulk
            }
        }

        // Get all field IDs for the selected form fields
        $allFieldNames = [];
        foreach ($formFields as $formId => $fields) {
            foreach (array_keys($fields) as $fn) {
                $allFieldNames[] = ['form_id' => $formId, 'field_name' => $fn];
            }
        }

        // Fetch field IDs in bulk
        $fieldIdMap = []; // [formId_fieldName => fieldId]
        foreach ($formFields as $formId => $fields) {
            $fieldNames = array_keys($fields);
            $fnPlaceholders = implode(',', array_fill(0, count($fieldNames), '?'));
            $fieldRows = $this->db->fetchAll(
                "SELECT id, field_name FROM form_fields ff 
                 JOIN form_sections fs ON ff.section_id = fs.id 
                 WHERE fs.form_id = ? AND ff.field_name IN ({$fnPlaceholders})",
                array_merge([$formId], $fieldNames)
            );
            foreach ($fieldRows as $fr) {
                $fieldIdMap["{$formId}_{$fr['field_name']}"] = $fr['id'];
            }
        }

        if (empty($fieldIdMap)) return ['total' => count($rows), 'rows' => $rows];

        // Fetch submission values in ONE query
        $fieldIds = array_values($fieldIdMap);
        $fiPlaceholders = implode(',', array_fill(0, count($fieldIds), '?'));
        $submissionValues = $this->db->fetchAll(
            "SELECT fsv.value, fsv.field_id, fs.lead_id
             FROM form_submission_values fsv
             JOIN form_submissions fs ON fsv.submission_id = fs.id
             WHERE fs.lead_id IN ({$placeholders}) AND fsv.field_id IN ({$fiPlaceholders})",
            array_merge($leadIds, $fieldIds)
        );

        // Build a lookup: leadId_fieldName => value
        $valueLookup = [];
        // Reverse map: fieldId => fieldKey (formId_fieldName)
        $reverseFieldMap = [];
        foreach ($fieldIdMap as $key => $fid) {
            $reverseFieldMap[$fid] = $key;
        }

        foreach ($submissionValues as $sv) {
            $fieldKey = $reverseFieldMap[$sv['field_id']] ?? null;
            if ($fieldKey) {
                // Extract field name from "formId_fieldName"
                $parts = explode('_', $fieldKey, 2);
                $fieldName = $parts[1] ?? '';
                $lookupKey = "{$sv['lead_id']}_{$fieldName}";
                // Keep latest value
                $valueLookup[$lookupKey] = $sv['value'];
            }
        }

        // Merge form field values into rows
        foreach ($rows as &$row) {
            $leadId = $row['_lead_id'];
            foreach ($formFields as $formId => $fields) {
                foreach (array_keys($fields) as $fn) {
                    $key = "{$leadId}_{$fn}";
                    $row["form_{$formId}_{$fn}"] = $valueLookup[$key] ?? '';
                }
            }
        }
        unset($row);

        return ['total' => count($rows), 'rows' => $rows];
    }

    /**
     * Get all column labels (static + dynamic + form builder)
     */
    private function getAllColumnLabels(): array
    {
        $labels = [];
        foreach ($this->leadColumns as $k => $v) $labels[$k] = $v['label'];
        foreach ($this->dynamicLeadColumns as $k => $v) $labels[$k] = $v['label'];

        $forms = $this->getFormBuilderColumns();
        foreach ($forms as $form) {
            foreach ($form['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    $labels["form_{$form['id']}_{$field['field_name']}"] = "{$field['label']} ({$form['name']})";
                }
            }
        }

        return $labels;
    }
}
