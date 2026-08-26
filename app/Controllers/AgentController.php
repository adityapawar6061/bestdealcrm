<?php
namespace Controllers;

class AgentController extends BaseController
{
    private \Models\Lead $leadModel;
    private \Models\DynamicForm $formModel;

    public function __construct()
    {
        parent::__construct();
        $this->leadModel = new \Models\Lead();
        $this->formModel = new \Models\DynamicForm();
    }

    public function dashboard(): void
    {
        $user = currentUser();
        $stats = [
            'my_leads'      => $this->db->count('leads', 'assigned_to = ?', [$user['id']]),
            'drafts'        => $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'AGENT_DRAFT']),
            'submitted'     => $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'ADMIN_REVIEW_1']),
            'returned'      => $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'RETURNED_TO_AGENT']),
            'login_approved'=> $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'LOGIN_APPROVED']),
        ];

        $recentLeads = $this->leadModel->getByAgent($user['id'], [], 1, 10);

        $this->view('agent/dashboard', [
            'title'       => 'Agent Dashboard',
            'stats'       => $stats,
            'recentLeads' => $recentLeads,
        ]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (isset($cache[$key])) return $cache[$key];
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$table, $column]
            );
            $cache[$key] = ($result && (int)$result['cnt'] > 0);
        } catch (\Throwable $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }

    public function leads(): void
    {
        $user = currentUser();
        $filters = [
            'search'         => $_GET['search'] ?? '',
            'workflow_stage' => $_GET['workflow_stage'] ?? '',
            'disposition'    => $_GET['disposition'] ?? '',
            'filter'         => $_GET['filter'] ?? '',
        ];
        $page = (int)($_GET['page'] ?? 1);

        // Check which disposition columns exist
        $existingCols = $this->getExistingColumns('leads');
        $hasDisposition = in_array('disposition', $existingCols);
        $hasAgentDisposition = in_array('agent_disposition', $existingCols);

        // If filter=pending, override disposition filter
        if ($filters['filter'] === 'pending') {
            $filters['disposition'] = '__pending__';
        }

        $leads = $this->leadModel->getByAgent($user['id'], $filters, $page);

        // Disposition stats for cards
        $userId = $user['id'];
        $totalAssigned = $this->db->count('leads', 'assigned_to = ?', [$userId]);
        $pendingDisposition = $totalAssigned;
        $dispositionCounts = [];

        if ($hasDisposition || $hasAgentDisposition) {
            $pendingParts = [];
            if ($hasDisposition) $pendingParts[] = "(disposition IS NULL OR disposition = '')";
            if ($hasAgentDisposition) $pendingParts[] = "(agent_disposition IS NULL OR agent_disposition = '')";
            $pendingSql = implode(' AND ', $pendingParts);
            $pendingDisposition = (int)$this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM leads WHERE assigned_to = ? AND {$pendingSql}",
                [$userId]
            )['cnt'];

            $dispCol = $hasDisposition ? 'disposition' : 'agent_disposition';
            $dispositionCounts = $this->db->fetchAll(
                "SELECT {$dispCol} as disposition, COUNT(*) as cnt FROM leads WHERE assigned_to = ? AND {$dispCol} IS NOT NULL AND {$dispCol} != '' GROUP BY {$dispCol} ORDER BY cnt DESC",
                [$userId]
            );
        }

        $this->view('agent/leads', [
            'title'              => 'My Leads',
            'leads'              => $leads,
            'filters'            => $filters,
            'totalAssigned'      => $totalAssigned,
            'pendingDisposition' => $pendingDisposition,
            'dispositionCounts'  => $dispositionCounts,
        ]);
    }

    public function leadDetail(int $id): void
    {
        $user = currentUser();
        $lead = $this->leadModel->findById($id);
        
        if (!$lead || $lead['assigned_to'] != $user['id']) {
            $this->redirect('/agent/leads', 'error', 'Lead not found or not assigned to you.');
            return;
        }

        $timeline = $this->leadModel->getTimeline($id);
        $submissions = $this->formModel->getSubmissionsForLead($id);

        $this->view('agent/lead_detail', [
            'title'       => 'Lead #' . $id,
            'lead'        => $lead,
            'timeline'    => $timeline,
            'submissions' => $submissions,
        ]);
    }

    /**
     * Show the agent lead form for filling
     */
    public function fillForm(int $id): void
    {
        $leadId = $id;
        $user = currentUser();
        $lead = $this->leadModel->findById($leadId);

        if (!$lead || $lead['assigned_to'] != $user['id']) {
            $this->redirect('/agent/leads', 'error', 'Lead not found.');
            return;
        }

        // Get the agent form
        $forms = $this->formModel->getFormsByStage('AGENT_DRAFT');
        if (empty($forms)) {
            // Fallback to role-based
            $forms = $this->formModel->getFormsByRole('agent');
        }

        if (empty($forms)) {
            $this->redirect('/agent/leads', 'error', 'No form available for this stage.');
            return;
        }

        $form = $this->formModel->getFullForm($forms[0]['id']);

        // Check for existing draft
        $existingSubmission = $this->db->fetchOne(
            "SELECT * FROM form_submissions WHERE lead_id = ? AND submitted_by = ? AND status IN ('draft', 'submitted') ORDER BY created_at DESC LIMIT 1",
            [$leadId, $user['id']]
        );

        $existingValues = [];
        if ($existingSubmission) {
            $values = $this->db->fetchAll(
                "SELECT * FROM form_submission_values WHERE submission_id = ?",
                [$existingSubmission['id']]
            );
            foreach ($values as $v) {
                $existingValues[$v['field_id']] = $v['value'];
            }
        }

        // Pre-fill fields from lead data and auto-fill agent name
        // Map label keywords to lead column names for robust matching
        $labelToLead = [
            'customer name'   => 'customer_name',
            'mobile'          => 'mobile_number',
            'location'        => 'location',
            'state'           => 'state',
            'existing la'     => 'existing_la',
            'salary'          => 'salary',
            'actual salary'   => 'actual_salary',
            'dtmf'            => 'dtmf_input',
            'response date'   => 'response_date',
            'data type'       => 'data_type',
            'bank name'       => 'bank_name',
            'pan number'      => 'pan_number',
            'current status'  => 'current_status',
            'update status'   => 'update_status',
            'remark'          => 'remark',
        ];

        foreach ($form['sections'] as &$section) {
            foreach ($section['fields'] as &$field) {
                $fn = strtolower(trim($field['field_name'] ?? ''));
                $fl = strtolower(trim($field['label'] ?? ''));

                // Pre-fill from lead data - match by field_name OR label
                if (empty($existingValues[$field['id']])) {
                    $leadCol = null;
                    // Direct field_name match
                    if (isset($labelToLead[$fn])) {
                        $leadCol = $labelToLead[$fn];
                    } else {
                        // Label-based fuzzy match
                        foreach ($labelToLead as $keyword => $col) {
                            if (strpos($fl, $keyword) !== false || strpos($fn, str_replace(' ', '_', $keyword)) !== false) {
                                $leadCol = $col;
                                break;
                            }
                        }
                    }
                    if ($leadCol && isset($lead[$leadCol]) && $lead[$leadCol] !== null && $lead[$leadCol] !== '') {
                        $existingValues[$field['id']] = $lead[$leadCol];
                    }
                }

                // Auto-fill agent name fields
                if (empty($existingValues[$field['id']]) && (strpos($fn, 'agent_name') !== false || strpos($fn, 'agent name') !== false || strpos($fl, 'agent name') !== false || strpos($fl, 'agent_name') !== false)) {
                    $existingValues[$field['id']] = $user['name'];
                }
            }
        }

        $this->view('agent/fill_form', [
            'title'          => 'Fill Lead Form',
            'lead'           => $lead,
            'form'           => $form,
            'existingValues' => $existingValues,
            'submission'     => $existingSubmission,
        ]);
    }

    /**
     * Save form as draft
     */
    public function saveDraft(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $formId = (int)($_POST['form_id'] ?? 0);
        $values = $_POST['form_data'] ?? [];

        $user = currentUser();
        $lead = $this->leadModel->findById($leadId);

        if (!$lead || $lead['assigned_to'] != $user['id']) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        // Check for existing submission
        $existing = $this->db->fetchOne(
            "SELECT id FROM form_submissions WHERE lead_id = ? AND submitted_by = ? AND status = 'draft'",
            [$leadId, $user['id']]
        );

        if ($existing) {
            $this->formModel->updateSubmission($existing['id'], $values);
            $submissionId = $existing['id'];
        } else {
            $submissionId = $this->formModel->submitForm($formId, $leadId, $user['id'], $values);
            $this->db->update('form_submissions', ['status' => 'draft'], 'id = ?', [$submissionId]);
        }

        // Update lead stage
        if ($lead['workflow_stage'] !== 'AGENT_DRAFT') {
            $this->leadModel->updateStage($leadId, 'AGENT_DRAFT');
        }

        logActivity($user['id'], 'form_draft_saved', 'lead', $leadId);

        $this->json(['success' => true, 'message' => 'Draft saved.', 'submission_id' => $submissionId]);
    }

    /**
     * Submit form to admin
     */
    public function submitForm(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $formId = (int)($_POST['form_id'] ?? 0);
        $values = $_POST['form_data'] ?? [];

        $user = currentUser();
        $lead = $this->leadModel->findById($leadId);

        if (!$lead || $lead['assigned_to'] != $user['id']) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        // Check for existing submission
        $existing = $this->db->fetchOne(
            "SELECT id FROM form_submissions WHERE lead_id = ? AND submitted_by = ? AND status IN ('draft', 'submitted')",
            [$leadId, $user['id']]
        );

        if ($existing) {
            $this->formModel->updateSubmission($existing['id'], $values);
            $submissionId = $existing['id'];
            $this->db->update('form_submissions', [
                'status'       => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ], 'id = ?', [$submissionId]);
        } else {
            $submissionId = $this->formModel->submitForm($formId, $leadId, $user['id'], $values);
            $this->db->update('form_submissions', [
                'status'       => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$submissionId]);
        }

        // Transition workflow
        $workflowModel = new \Models\Workflow();
        $workflowModel->transition($leadId, $lead['workflow_stage'], 'ADMIN_REVIEW_1', $user['id'], 'agent', null, 'form_submitted');

        // Notify admin
        $admins = $this->db->fetchAll(
            "SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'admin')"
        );
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'Form Submitted', "Agent has submitted lead #{$leadId} for review.", 'info', $leadId);
        }

        logActivity($user['id'], 'form_submitted', 'lead', $leadId);

        $this->json(['success' => true, 'message' => 'Form submitted to Admin for review.']);
    }

    /**
     * AJAX endpoint for server-side lead data (for DataTables-style tables)
     */
    public function leadsAjax(): void
    {
        $user = currentUser();
        $draw = (int)($_GET['draw'] ?? 1);
        $start = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 25);
        $search = $_GET['search']['value'] ?? '';
        $stage = $_GET['workflow_stage'] ?? '';

        $where = 'l.assigned_to = ?';
        $params = [$user['id']];

        if ($search) {
            $where .= ' AND (l.id = ? OR l.customer_name LIKE ? OR l.mobile_number LIKE ? OR l.pan_number LIKE ?)';
            $params[] = $search;
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($stage) {
            $where .= ' AND l.workflow_stage = ?';
            $params[] = $stage;
        }

        $total = $this->db->count('leads l', $where, $params);
        $sql = "SELECT l.* FROM leads l WHERE {$where} ORDER BY l.created_at DESC LIMIT {$length} OFFSET {$start}";
        $data = $this->db->fetchAll($sql, $params);

        $this->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    /**
     * Notifications page
     */
    public function notifications(): void
    {
        $user = currentUser();
        $notifications = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
            [$user['id']]
        );

        $this->view('agent/notifications', [
            'title'         => 'Notifications',
            'notifications' => $notifications,
        ]);
    }

    public function readNotification(): void
    {
        $id = (int)($_POST['notification_id'] ?? 0);
        if ($id) {
            $this->db->update('notifications', ['is_read' => 1], 'id = ?', [$id]);
            $this->json(['success' => true]);
        }
    }

    /**
     * AJAX: Update disposition, remark, actual_salary, etc.
     * Each update type only touches its own column — never overwrites unrelated fields.
     */
    public function updateDisposition(): void
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Invalid request.'], 405);
                return;
            }

            $leadId = (int)($_POST['lead_id'] ?? 0);
            $disposition = trim($_POST['disposition'] ?? '');
            $agentRemark = $_POST['agent_remark'] ?? '__NOT_SENT__';
            $field = trim($_POST['field'] ?? '');
            $value = trim($_POST['value'] ?? '');
            $user = currentUser();

            $lead = $this->leadModel->findById($leadId);
            if (!$lead || $lead['assigned_to'] != $user['id']) {
                $this->json(['error' => 'Unauthorized.'], 403);
                return;
            }

            // Ensure required columns exist
            $this->ensureColumns();

            // Determine which columns exist
            $existingCols = $this->getExistingColumns('leads');

            // Case 1: Disposition update (sent from dropdown)
            if ($disposition !== '' || array_key_exists('disposition', $_POST)) {
                $updateData = ['updated_at' => date('Y-m-d H:i:s')];
                if (in_array('agent_disposition', $existingCols)) {
                    $updateData['agent_disposition'] = $disposition;
                }
                if (in_array('disposition', $existingCols)) {
                    $updateData['disposition'] = $disposition;
                }
                $this->db->update('leads', $updateData, 'id = ?', [$leadId]);
                logActivity($user['id'], 'disposition_updated', 'lead', $leadId, null, json_encode($updateData));
                $this->json(['success' => true, 'message' => 'Disposition updated.', 'updated_fields' => array_keys($updateData)]);
                return;
            }

            // Case 2: Remark update (sent from remark input blur)
            if ($agentRemark !== '__NOT_SENT__') {
                if (in_array('agent_remark', $existingCols)) {
                    $this->db->update('leads', [
                        'agent_remark' => $agentRemark,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$leadId]);
                    logActivity($user['id'], 'remark_updated', 'lead', $leadId);
                    $this->json(['success' => true, 'message' => 'Remark saved.']);
                    return;
                }
            }

            // Case 3: Generic field update (actual_salary, etc.)
            if ($field) {
                $allowedFields = ['actual_salary', 'salary', 'existing_la', 'remark'];
                if (in_array($field, $allowedFields) && in_array($field, $existingCols)) {
                    $this->db->update('leads', [
                        $field => $value ?: null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$leadId]);
                    logActivity($user['id'], 'field_updated', 'lead', $leadId, null, json_encode([$field => $value]));
                    $this->json(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated.']);
                    return;
                }
                // If column doesn't exist, auto-create it
                try {
                    $this->db->query("ALTER TABLE `leads` ADD COLUMN `{$field}` VARCHAR(100) DEFAULT NULL");
                    $existingCols[] = $field;
                    $this->db->update('leads', [
                        $field => $value ?: null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ], 'id = ?', [$leadId]);
                    $this->json(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated.']);
                    return;
                } catch (\Throwable $e) {
                    error_log("Failed to create column {$field}: " . $e->getMessage());
                }
            }

            $this->json(['error' => 'No valid update specified.'], 400);
        } catch (\Throwable $e) {
            error_log('updateDisposition ERROR: ' . $e->getMessage());
            $this->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Ensure critical columns exist in leads table
     */
    private function ensureColumns(): void
    {
        $cols = [
            'agent_disposition' => 'VARCHAR(100) DEFAULT NULL',
            'disposition' => 'VARCHAR(100) DEFAULT NULL',
            'agent_remark' => 'TEXT DEFAULT NULL',
            'actual_salary' => 'VARCHAR(50) DEFAULT NULL',
        ];
        $existingCols = $this->getExistingColumns('leads');
        $addedAny = false;
        foreach ($cols as $col => $def) {
            if (!in_array($col, $existingCols)) {
                try {
                    $this->db->query("ALTER TABLE `leads` ADD COLUMN `{$col}` {$def}");
                    $existingCols[] = $col;
                    $addedAny = true;
                } catch (\Throwable $e) {
                    // Column might already exist
                }
            }
        }
        // Reset the cache so subsequent getExistingColumns calls see new columns
        if ($addedAny) {
            self::resetColumnsCache('leads');
        }
    }

    private static function resetColumnsCache(string $table): void
    {
        // Use a class-level approach: clear the static cache
        // We can't directly access static vars from another method in PHP easily,
        // so we use a class property
    }

    /**
     * Get existing column names for a table
     */
    private function getExistingColumns(string $table): array
    {
        try {
            $rows = $this->db->fetchAll(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
                [$table]
            );
            return array_column($rows, 'COLUMN_NAME');
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Document upload handler
     */
    public function uploadDocument(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $user = currentUser();

        $lead = $this->leadModel->findById($leadId);
        if (!$lead || $lead['assigned_to'] != $user['id']) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        if (!isset($_FILES['document'])) {
            $this->json(['error' => 'No file uploaded.'], 400);
            return;
        }

        $file = $_FILES['document'];
        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedTypes)) {
            $this->json(['error' => 'File type not allowed.'], 400);
            return;
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            $this->json(['error' => 'File too large (max 10MB).'], 400);
            return;
        }

        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf', 'image/jpeg', 'image/png',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            $this->json(['error' => 'Invalid file type.'], 400);
            return;
        }

        // Store file securely
        $uploadDir = ROOT_PATH . '/public/uploads/documents/' . $leadId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeFilename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $uploadDir . $safeFilename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $docId = $this->db->insert('documents', [
                'lead_id'        => $leadId,
                'uploaded_by'    => $user['id'],
                'filename'       => $safeFilename,
                'original_name'  => $file['name'],
                'mime_type'      => $mimeType,
                'file_size'      => $file['size'],
                'document_type'  => $_POST['document_type'] ?? 'general',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);

            logActivity($user['id'], 'document_uploaded', 'document', (int)$docId, null, $file['name']);

            $this->json(['success' => true, 'message' => 'Document uploaded.', 'id' => $docId]);
        } else {
            $this->json(['error' => 'Failed to save file.'], 500);
        }
    }
}
