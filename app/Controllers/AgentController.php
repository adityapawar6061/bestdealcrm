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
        $uid = (int)$user['id'];
        $agentWhere = "(assigned_to = {$uid} OR created_by = {$uid})";
        $stats = [
            'my_leads'      => $this->db->count('leads', $agentWhere),
            'drafts'        => $this->db->count('leads', "({$agentWhere}) AND workflow_stage = 'AGENT_DRAFT'"),
            'submitted'     => $this->db->count('leads', "({$agentWhere}) AND workflow_stage = 'ADMIN_REVIEW_1'"),
            'returned'      => $this->db->count('leads', "({$agentWhere}) AND workflow_stage = 'RETURNED_TO_AGENT'"),
            'login_approved'=> $this->db->count('leads', "({$agentWhere}) AND workflow_stage = 'LOGIN_APPROVED'"),
        ];

        $recentLeads = $this->leadModel->getByAgent($user['id'], [], 1, 10);

        $this->view('agent/dashboard', [
            'title'       => 'Agent Dashboard',
            'stats'       => $stats,
            'recentLeads' => $recentLeads,
        ]);
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

        $existingCols = $this->getExistingColumns('leads');
        $hasDisposition = in_array('disposition', $existingCols);
        $hasAgentDisposition = in_array('agent_disposition', $existingCols);

        if ($filters['filter'] === 'pending') {
            $filters['disposition'] = '__pending__';
        }

        $leads = $this->leadModel->getByAgent($user['id'], $filters, $page);

        $userId = (int)$user['id'];
        $agentWhere = "(assigned_to = {$userId} OR created_by = {$userId})";
        $totalAssigned = $this->db->count('leads', $agentWhere);
        $pendingDisposition = $totalAssigned;
        $dispositionCounts = [];

        if ($hasDisposition || $hasAgentDisposition) {
            $pendingParts = [];
            if ($hasDisposition) $pendingParts[] = "(disposition IS NULL OR disposition = '')";
            if ($hasAgentDisposition) $pendingParts[] = "(agent_disposition IS NULL OR agent_disposition = '')";
            $pendingSql = implode(' AND ', $pendingParts);
            $pendingDisposition = (int)$this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM leads WHERE {$agentWhere} AND {$pendingSql}"
            )['cnt'];

            $dispCol = $hasDisposition ? 'disposition' : 'agent_disposition';
            $dispositionCounts = $this->db->fetchAll(
                "SELECT {$dispCol} as disposition, COUNT(*) as cnt FROM leads WHERE {$agentWhere} AND {$dispCol} IS NOT NULL AND {$dispCol} != '' GROUP BY {$dispCol} ORDER BY cnt DESC"
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

    /**
     * Create a new lead (agent fills basic admin data, then goes to form)
     */
    public function createLead(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $user = currentUser();
        $data = [
            'customer_name'   => trim($_POST['customer_name'] ?? ''),
            'mobile_number'   => trim($_POST['mobile_number'] ?? ''),
            'location'        => trim($_POST['location'] ?? ''),
            'state'           => trim($_POST['state'] ?? ''),
            'salary'          => trim($_POST['salary'] ?? ''),
            'actual_salary'   => trim($_POST['actual_salary'] ?? ''),
            'existing_la'     => trim($_POST['existing_la'] ?? ''),
            'bank_name'       => trim($_POST['bank_name'] ?? ''),
            'data_type'       => trim($_POST['data_type'] ?? ''),
            'response_date'   => trim($_POST['response_date'] ?? ''),
            'remark'          => trim($_POST['remark'] ?? ''),
        ];

        // Validate required fields
        if (empty($data['customer_name']) || empty($data['mobile_number'])) {
            $this->json(['error' => 'Customer Name and Mobile Number are required.'], 422);
            return;
        }

        // Check duplicate mobile
        $existing = $this->leadModel->checkDuplicateMobile($data['mobile_number']);
        if ($existing) {
            $this->json(['error' => 'A lead with this mobile number already exists.'], 422);
            return;
        }

        // Auto-create required columns
        $this->ensureColumns();

        // Set lead data
        $data['workflow_stage'] = 'LEAD_ASSIGNED';
        $data['assigned_to'] = $user['id'];
        $data['created_by'] = $user['id'];
        $data['created_at'] = nowIST();

        $leadId = $this->leadModel->create($data);

        // Record assignment
        $this->db->insert('lead_assignments', [
            'lead_id'     => $leadId,
            'assigned_to' => $user['id'],
            'assigned_by' => $user['id'],
            'assigned_at' => nowIST(),
            'status'      => 'active',
        ]);

        // Workflow history
        $workflowModel = new \Models\Workflow();
        $workflowModel->transition($leadId, 'LEAD_UPLOADED', 'LEAD_ASSIGNED', $user['id'], 'agent', null, 'agent_created_lead');

        logActivity($user['id'], 'lead_created', 'lead', $leadId);

        $this->json(['success' => true, 'message' => 'Lead created. Redirecting to form...', 'lead_id' => $leadId]);
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

    public function fillForm(int $id): void
    {
        $leadId = $id;
        $user = currentUser();
        $lead = $this->leadModel->findById($leadId);

        if (!$lead || $lead['assigned_to'] != $user['id']) {
            $this->redirect('/agent/leads', 'error', 'Lead not found.');
            return;
        }

        $forms = $this->formModel->getFormsByStage('AGENT_DRAFT');
        if (empty($forms)) {
            $forms = $this->formModel->getFormsByRole('agent');
        }

        if (empty($forms)) {
            $this->redirect('/agent/leads', 'error', 'No form available for this stage.');
            return;
        }

        $form = $this->formModel->getFullForm($forms[0]['id']);

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

                if (empty($existingValues[$field['id']])) {
                    $leadCol = null;
                    if (isset($labelToLead[$fn])) {
                        $leadCol = $labelToLead[$fn];
                    } else {
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

        if ($lead['workflow_stage'] !== 'AGENT_DRAFT') {
            $this->leadModel->updateStage($leadId, 'AGENT_DRAFT');
        }

        logActivity($user['id'], 'form_draft_saved', 'lead', $leadId);

        $this->json(['success' => true, 'message' => 'Draft saved.', 'submission_id' => $submissionId]);
    }

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

        $existing = $this->db->fetchOne(
            "SELECT id FROM form_submissions WHERE lead_id = ? AND submitted_by = ? AND status IN ('draft', 'submitted')",
            [$leadId, $user['id']]
        );

        if ($existing) {
            $this->formModel->updateSubmission($existing['id'], $values);
            $submissionId = $existing['id'];
            $this->db->update('form_submissions', [
                'status'       => 'submitted',
                'submitted_at' => nowIST(),
                'updated_at'   => nowIST(),
            ], 'id = ?', [$submissionId]);
        } else {
            $submissionId = $this->formModel->submitForm($formId, $leadId, $user['id'], $values);
            $this->db->update('form_submissions', [
                'status'       => 'submitted',
                'submitted_at' => nowIST(),
            ], 'id = ?', [$submissionId]);
        }

        $workflowModel = new \Models\Workflow();
        $workflowModel->transition($leadId, $lead['workflow_stage'], 'ADMIN_REVIEW_1', $user['id'], 'agent', null, 'form_submitted');

        $admins = $this->db->fetchAll(
            "SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'admin')"
        );
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'Form Submitted', "Agent has submitted lead #{$leadId} for review.", 'info', $leadId);
        }

        logActivity($user['id'], 'form_submitted', 'lead', $leadId);

        $this->json(['success' => true, 'message' => 'Form submitted to Admin for review.']);
    }

    public function leadsAjax(): void
    {
        $user = currentUser();
        $draw = (int)($_GET['draw'] ?? 1);
        $start = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 25);
        $search = $_GET['search']['value'] ?? '';
        $stage = $_GET['workflow_stage'] ?? '';

        $uid = (int)$user['id'];
        $where = "(l.assigned_to = {$uid} OR l.created_by = {$uid})";
        $params = [];

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

            $this->ensureColumns();
            $existingCols = $this->getExistingColumns('leads');

            // Case 1: Disposition update
            if ($disposition !== '' || array_key_exists('disposition', $_POST)) {
                $updateData = ['updated_at' => nowIST()];
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

            // Case 2: Remark update
            if ($agentRemark !== '__NOT_SENT__') {
                if (in_array('agent_remark', $existingCols)) {
                    $this->db->update('leads', [
                        'agent_remark' => $agentRemark,
                        'updated_at' => nowIST(),
                    ], 'id = ?', [$leadId]);
                    logActivity($user['id'], 'remark_updated', 'lead', $leadId);
                    $this->json(['success' => true, 'message' => 'Remark saved.']);
                    return;
                }
            }

            // Case 3: Generic field update (actual_salary, etc.)
            if ($field) {
                $allowedFields = ['actual_salary', 'salary', 'existing_la', 'remark', 'customer_name', 'mobile_number', 'location', 'state', 'bank_name', 'data_type', 'response_date'];
                if (in_array($field, $allowedFields)) {
                    // Ensure column exists first
                    $this->ensureColumns();
                    $allCols = $this->getExistingColumns('leads');
                    if (!in_array($field, $allCols)) {
                        try {
                            $this->db->query("ALTER TABLE `leads` ADD COLUMN `{$field}` VARCHAR(255) DEFAULT NULL");
                        } catch (\Throwable $e) {
                            error_log("Failed to create column {$field}: " . $e->getMessage());
                            $this->json(['error' => 'Could not create column: ' . $field], 500);
                            return;
                        }
                    }
                    try {
                        $this->db->update('leads', [
                            $field => $value ?: null,
                            'updated_at' => nowIST(),
                        ], 'id = ?', [$leadId]);
                        logActivity($user['id'], 'field_updated', 'lead', $leadId, null, json_encode([$field => $value]));
                        $this->json(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' updated.']);
                        return;
                    } catch (\Throwable $e) {
                        error_log("Update field {$field} failed: " . $e->getMessage());
                        $this->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
                        return;
                    }
                }
            }

            $this->json(['error' => 'No valid update specified.'], 400);
        } catch (\Throwable $e) {
            error_log('updateDisposition ERROR: ' . $e->getMessage());
            $this->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    private function ensureColumns(): void
    {
        $cols = [
            'agent_disposition' => 'VARCHAR(100) DEFAULT NULL',
            'disposition' => 'VARCHAR(100) DEFAULT NULL',
            'agent_remark' => 'TEXT DEFAULT NULL',
            'actual_salary' => 'VARCHAR(50) DEFAULT NULL',
            'created_by' => 'INT UNSIGNED DEFAULT NULL',
        ];
        $existingCols = $this->getExistingColumns('leads');
        foreach ($cols as $col => $def) {
            if (!in_array($col, $existingCols)) {
                try {
                    $this->db->query("ALTER TABLE `leads` ADD COLUMN `{$col}` {$def}");
                    $existingCols[] = $col;
                } catch (\Throwable $e) {
                    // Column might already exist
                }
            }
        }
    }

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
                'created_at'     => nowIST(),
            ]);

            logActivity($user['id'], 'document_uploaded', 'document', (int)$docId, null, $file['name']);

            $this->json(['success' => true, 'message' => 'Document uploaded.', 'id' => $docId]);
        } else {
            $this->json(['error' => 'Failed to save file.'], 500);
        }
    }
}
