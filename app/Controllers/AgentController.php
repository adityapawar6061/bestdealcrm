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

    public function leads(): void
    {
        $user = currentUser();
        $filters = [
            'search'         => $_GET['search'] ?? '',
            'workflow_stage' => $_GET['workflow_stage'] ?? '',
        ];
        $page = (int)($_GET['page'] ?? 1);

        $leads = $this->leadModel->getByAgent($user['id'], $filters, $page);

        $this->view('agent/leads', [
            'title'  => 'My Leads',
            'leads'  => $leads,
            'filters'=> $filters,
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
        $sql = "SELECT l.id, l.customer_name, l.mobile_number, l.location, l.state, 
                       l.existing_la, l.salary, l.actual_salary, l.bank_name, 
                       l.current_status, l.workflow_stage, l.created_at, l.updated_at,
                       l.response_date, l.data_type, l.agent_disposition, l.agent_remark
                FROM leads l WHERE {$where} ORDER BY l.created_at DESC LIMIT {$length} OFFSET {$start}";
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
