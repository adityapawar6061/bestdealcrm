<?php
namespace Controllers;

class ReportController extends BaseController
{
    // All available columns for lead reports
    private array $availableColumns = [
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

    // Additional dynamic columns that may exist
    private array $dynamicColumns = [
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
            `columns_config` TEXT NOT NULL COMMENT 'JSON array of column configs',
            `created_by` INT UNSIGNED,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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
     * Create new report template form
     */
    public function create(): void
    {
        // Check which dynamic columns actually exist
        $existingDynamic = [];
        foreach ($this->dynamicColumns as $key => $col) {
            $exists = $this->db->fetchOne(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = ?",
                [$key]
            );
            if ($exists) {
                $existingDynamic[$key] = $col;
            }
        }

        $this->view('admin/reports/create', [
            'title'            => 'Create Report Template',
            'availableColumns' => $this->availableColumns,
            'dynamicColumns'   => $existingDynamic,
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

        // Build columns config with labels
        $columnsConfig = [];
        $allCols = array_merge($this->availableColumns, $this->dynamicColumns);
        foreach ($columns as $colName) {
            $label = $allCols[$colName]['label'] ?? $colName;
            $columnsConfig[] = ['field' => $colName, 'label' => $label];
        }

        $user = currentUser();
        $templateId = $this->db->insert('report_templates', [
            'name'           => $name,
            'description'    => $description,
            'columns_config' => json_encode($columnsConfig),
            'created_by'     => $user['id'],
            'created_at'     => date('Y-m-d H:i:s'),
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

        // Check dynamic columns
        $existingDynamic = [];
        foreach ($this->dynamicColumns as $key => $col) {
            $exists = $this->db->fetchOne(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'leads' AND COLUMN_NAME = ?",
                [$key]
            );
            if ($exists) {
                $existingDynamic[$key] = $col;
            }
        }

        $this->view('admin/reports/create', [
            'title'            => 'Edit Report Template',
            'availableColumns' => $this->availableColumns,
            'dynamicColumns'   => $existingDynamic,
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

        $allCols = array_merge($this->availableColumns, $this->dynamicColumns);
        $columnsConfig = [];
        foreach ($columns as $colName) {
            $label = $allCols[$colName]['label'] ?? $colName;
            $columnsConfig[] = ['field' => $colName, 'label' => $label];
        }

        $this->db->update('report_templates', [
            'name'           => $name,
            'description'    => $description,
            'columns_config' => json_encode($columnsConfig),
            'updated_at'     => date('Y-m-d H:i:s'),
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

        // Get filter options
        $agents = $this->db->fetchAll(
            "SELECT u.id, u.name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('agent', 'login_agent', 'team_leader', 'underwriting', 'dispatch') AND u.status = 'active' ORDER BY u.name"
        );
        $banks = $this->db->fetchAll("SELECT DISTINCT bank_name FROM leads WHERE bank_name IS NOT NULL AND bank_name != '' ORDER BY bank_name");
        $stages = $this->db->fetchAll("SELECT DISTINCT workflow_stage FROM leads WHERE workflow_stage IS NOT NULL ORDER BY workflow_stage");

        // If AJAX preview, return data
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
     * Export data as CSV with BOM (opens in Excel)
     */
    public function export(int $id): void
    {
        $template = $this->db->fetchOne("SELECT * FROM report_templates WHERE id = ?", [$id]);
        if (!$template) {
            $this->json(['error' => 'Template not found.'], 404);
            return;
        }

        $columnsConfig = json_decode($template['columns_config'], true) ?? [];
        if (empty($columnsConfig)) {
            $this->json(['error' => 'No columns configured.'], 400);
            return;
        }

        // Build query with filters
        $where = '1=1';
        $params = [];

        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        $dateField = $_GET['date_field'] ?? 'created_at';
        $agentFilter = $_GET['agent_id'] ?? '';
        $bankFilter = $_GET['bank_name'] ?? '';
        $stageFilter = $_GET['workflow_stage'] ?? '';
        $search = $_GET['search'] ?? '';

        // Validate date field
        $allowedDateFields = ['created_at', 'updated_at', 'response_date'];
        if (!in_array($dateField, $allowedDateFields)) {
            $dateField = 'created_at';
        }

        if ($dateFrom) {
            $where .= " AND l.{$dateField} >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo) {
            $where .= " AND l.{$dateField} <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        if ($agentFilter) {
            $where .= ' AND l.assigned_to = ?';
            $params[] = (int)$agentFilter;
        }
        if ($bankFilter) {
            $where .= ' AND l.bank_name = ?';
            $params[] = $bankFilter;
        }
        if ($stageFilter) {
            $where .= ' AND l.workflow_stage = ?';
            $params[] = $stageFilter;
        }
        if ($search) {
            $s = '%' . $search . '%';
            $where .= ' AND (l.customer_name LIKE ? OR l.mobile_number LIKE ? OR l.id = ?)';
            $params[] = $s;
            $params[] = $s;
            $params[] = $search;
        }

        // Build column list for SQL
        $selectedFields = array_column($columnsConfig, 'field');
        $sqlFields = [];
        foreach ($selectedFields as $field) {
            if ($field === 'assigned_to_name') {
                $sqlFields[] = 'u.name as assigned_to_name';
            } else {
                $sqlFields[] = "l.{$field}";
            }
        }

        $sql = "SELECT " . implode(', ', $sqlFields) . "
                FROM leads l 
                LEFT JOIN users u ON l.assigned_to = u.id 
                WHERE {$where} 
                ORDER BY l.created_at DESC";

        $rows = $this->db->fetchAll($sql, $params);

        logActivity(currentUser()['id'], 'report_exported', 'report_template', $id, null,
            json_encode(['rows' => count($rows), 'columns' => count($selectedFields)]));

        // Generate CSV with UTF-8 BOM (opens correctly in Excel)
        $filename = $template['name'] . '_' . date('Y-m-d_His') . '.csv';

        // Clean output buffer
        if (ob_get_level()) ob_end_clean();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $fp = fopen('php://output', 'w');

        // UTF-8 BOM for Excel
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header row
        $headerRow = array_column($columnsConfig, 'label');
        fputcsv($fp, $headerRow);

        // Data rows
        foreach ($rows as $row) {
            $dataRow = [];
            foreach ($selectedFields as $field) {
                $val = $row[$field] ?? '';
                // Format salary with ₹
                if (in_array($field, ['salary', 'actual_salary', 'existing_la']) && is_numeric($val) && $val > 0) {
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
        $selectedFields = array_column($columnsConfig, 'field');

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

        $sqlFields = [];
        foreach ($selectedFields as $field) {
            if ($field === 'assigned_to_name') {
                $sqlFields[] = 'u.name as assigned_to_name';
            } else {
                $sqlFields[] = "l.{$field}";
            }
        }

        $total = $this->db->count('leads l', $where, $params);

        $sql = "SELECT " . implode(', ', $sqlFields) . "
                FROM leads l 
                LEFT JOIN users u ON l.assigned_to = u.id 
                WHERE {$where} 
                ORDER BY l.created_at DESC 
                LIMIT 100";

        $rows = $this->db->fetchAll($sql, $params);

        $this->json([
            'success'  => true,
            'columns'  => $columnsConfig,
            'rows'     => $rows,
            'total'    => $total,
            'showing'  => count($rows),
        ]);
    }
}
