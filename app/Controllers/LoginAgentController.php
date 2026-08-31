<?php
namespace Controllers;

class LoginAgentController extends BaseController
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
            'assigned_cases'  => $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'LOGIN_AGENT_ASSIGNED']),
            'drafts'          => $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'LOGIN_AGENT_DRAFT']),
            'submitted'       => $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'ADMIN_REVIEW_2']),
            'approved'        => $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'LOGIN_APPROVED']),
            'post_login'      => $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'POST_LOGIN']),
            'returned'        => $this->db->count('leads', 'assigned_to = ? AND workflow_stage = ?', [$user['id'], 'RETURNED_TO_AGENT']),
        ];

        $recentLeads = $this->leadModel->getByAgent($user['id'], [], 1, 10);

        $this->view('login_agent/dashboard', [
            'title'       => 'Login Agent Dashboard',
            'stats'       => $stats,
            'recentLeads' => $recentLeads,
        ]);
    }

    public function cases(): void
    {
        $user = currentUser();
        $filters = [
            'search'         => $_GET['search'] ?? '',
            'workflow_stage' => $_GET['workflow_stage'] ?? '',
        ];
        $page = (int)($_GET['page'] ?? 1);

        $leads = $this->leadModel->getByAgent($user['id'], $filters, $page);

        // Enrich leads with agent submissions and documents
        foreach ($leads['data'] as &$lead) {
            // Get agent's submitted form data
            $agentSubmission = $this->db->fetchOne(
                "SELECT fs.* FROM form_submissions fs 
                 JOIN users u ON fs.submitted_by = u.id
                 JOIN roles r ON u.role_id = r.id
                 WHERE fs.lead_id = ? AND r.name = 'agent' 
                 ORDER BY fs.created_at DESC LIMIT 1",
                [$lead['id']]
            );
            $lead['agent_submission'] = $agentSubmission;
            
            if ($agentSubmission) {
                $values = $this->db->fetchAll(
                    "SELECT fsv.*, ff.label, ff.type, ff.field_name
                     FROM form_submission_values fsv
                     JOIN form_fields ff ON fsv.field_id = ff.id
                     WHERE fsv.submission_id = ?",
                    [$agentSubmission['id']]
                );
                $lead['agent_values'] = $values;
            } else {
                $lead['agent_values'] = [];
            }
            
            // Get documents
            $lead['documents'] = $this->db->fetchAll(
                "SELECT * FROM documents WHERE lead_id = ? ORDER BY created_at DESC",
                [$lead['id']]
            );
            
            // Get remarks from various stages
            $lead['remarks'] = $this->db->fetchAll(
                "SELECT r.*, u.name as user_name FROM remarks r 
                 LEFT JOIN users u ON r.user_id = u.id
                 WHERE r.lead_id = ? ORDER BY r.created_at DESC",
                [$lead['id']]
            );
        }

        $this->view('login_agent/cases', [
            'title'  => 'Assigned Cases',
            'leads'  => $leads,
            'filters'=> $filters,
        ]);
    }

    /**
     * Pre-Login Checklist form
     */
    public function preLoginChecklist(int $id): void
    {
        $leadId = $id;
        $user = currentUser();
        $lead = $this->leadModel->findById($leadId);

        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->redirect('/login-agent/cases', 'error', 'Lead not found.');
            return;
        }

        // Get the pre-login form
        $forms = $this->formModel->getFormsByStage('LOGIN_AGENT_DRAFT');
        if (empty($forms)) {
            $forms = $this->formModel->getFormsByRole('login_agent');
        }

        $form = !empty($forms) ? $this->formModel->getFullForm($forms[0]['id']) : null;

        // Check for existing submission
        $existing = $this->db->fetchOne(
            "SELECT * FROM form_submissions WHERE lead_id = ? AND submitted_by = ? AND status IN ('draft', 'submitted') ORDER BY created_at DESC LIMIT 1",
            [$leadId, $user['id']]
        );

        $existingValues = [];
        if ($existing) {
            $values = $this->db->fetchAll(
                "SELECT * FROM form_submission_values WHERE submission_id = ?",
                [$existing['id']]
            );
            foreach ($values as $v) {
                $existingValues[$v['field_id']] = $v['value'];
            }
        }

        // 1. Get agent form full structure (read-only)
        $agentForm = null;
        $agentFormValues = [];
        $agentSubmission = $this->db->fetchOne(
            "SELECT fs.* FROM form_submissions fs
             JOIN users u ON fs.submitted_by = u.id
             JOIN roles r ON u.role_id = r.id
             WHERE fs.lead_id = ? AND r.name = 'agent' AND fs.status = 'submitted'
             ORDER BY fs.created_at DESC LIMIT 1",
            [$leadId]
        );
        if ($agentSubmission) {
            // Get the form structure with sections
            $agentForm = $this->formModel->getFullForm($agentSubmission['form_id']);
            // Get submitted values keyed by field_id
            $vals = $this->db->fetchAll(
                "SELECT * FROM form_submission_values WHERE submission_id = ?",
                [$agentSubmission['id']]
            );
            foreach ($vals as $v) {
                $agentFormValues[$v['field_id']] = $v['value'];
            }
        }

        // 2. Get documents uploaded for this lead
        $documents = $this->db->fetchAll(
            "SELECT d.*, u.name as uploaded_by_name
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.lead_id = ? ORDER BY d.created_at DESC",
            [$leadId]
        );

        // 3. Get all submissions for this lead (for reference)
        $allSubmissions = $this->formModel->getSubmissionsForLead($leadId);

        $this->view('login_agent/pre_login', [
            'title'            => 'Pre-Login Checklist',
            'lead'             => $lead,
            'form'             => $form,
            'existingValues'   => $existingValues,
            'submission'       => $existing,
            'agentForm'        => $agentForm,
            'agentFormValues'  => $agentFormValues,
            'agentSubmission'  => $agentSubmission,
            'documents'        => $documents,
            'allSubmissions'   => $allSubmissions,
        ]);
    }

    /**
     * Save pre-login checklist as draft
     */
    public function saveChecklistDraft(): void
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
        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
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

        if ($lead['workflow_stage'] !== 'LOGIN_AGENT_DRAFT') {
            $this->leadModel->updateStage($leadId, 'LOGIN_AGENT_DRAFT');
        }

        logActivity($user['id'], 'checklist_draft_saved', 'lead', $leadId);

        $this->json(['success' => true, 'message' => 'Checklist draft saved.']);
    }

    /**
     * Submit checklist to admin
     */
    public function submitChecklist(): void
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
        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
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
            ], 'id = ?', [$submissionId]);
        } else {
            $submissionId = $this->formModel->submitForm($formId, $leadId, $user['id'], $values);
            $this->db->update('form_submissions', [
                'status'       => 'submitted',
                'submitted_at' => nowIST(),
            ], 'id = ?', [$submissionId]);
        }

        $workflowModel = new \Models\Workflow();
        $workflowModel->transition($leadId, $lead['workflow_stage'], 'ADMIN_REVIEW_2', $user['id'], 'login_agent', null, 'checklist_submitted');

        // Notify admin
        $admins = $this->db->fetchAll("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'admin')");
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'Checklist Submitted', "Pre-Login checklist submitted for lead #{$leadId}.", 'info', $leadId);
        }

        logActivity($user['id'], 'checklist_submitted', 'lead', $leadId);

        $this->json(['success' => true, 'message' => 'Checklist submitted to Admin.']);
    }

    /**
     * Send back to agent
     */
    public function sendBackToAgent(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $remark = $_POST['remark'] ?? '';
        $user = currentUser();

        if (empty($remark)) {
            $this->json(['error' => 'Remark is mandatory when sending back to agent.'], 422);
            return;
        }

        $lead = $this->leadModel->findById($leadId);
        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        $workflowModel = new \Models\Workflow();
        $workflowModel->transition($leadId, $lead['workflow_stage'], 'RETURNED_TO_AGENT', $user['id'], 'login_agent', $remark, 'send_back');

        // Store remark
        $this->db->insert('remarks', [
            'lead_id'    => $leadId,
            'user_id'    => $user['id'],
            'stage'      => 'RETURNED_TO_AGENT',
            'remark'     => $remark,
            'created_at' => nowIST(),
        ]);

        // Notify the original agent
        if ($lead['assigned_to']) {
            createNotification($lead['assigned_to'], 'Form Returned', "Your form for lead #{$leadId} has been returned with remarks.", 'warning', $leadId);
        }

        logActivity($user['id'], 'form_returned', 'lead', $leadId, null, $remark);

        $this->json(['success' => true, 'message' => 'Form returned to agent.']);
    }

    /**
     * Post-Login form - shows agent form (read-only), pre-login (read-only), post-login (editable)
     */
    public function postLogin(int $id): void
    {
        $leadId = $id;
        $user = currentUser();
        $lead = $this->leadModel->findById($leadId);

        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->redirect('/login-agent/cases', 'error', 'Lead not found.');
            return;
        }

        // Allow access if LOGIN_APPROVED or POST_LOGIN (draft save)
        $allowedStages = ['LOGIN_APPROVED', 'POST_LOGIN'];
        if (!in_array($lead['workflow_stage'], $allowedStages)) {
            $this->redirect('/login-agent/cases', 'error', 'Lead not available for post-login.');
            return;
        }

        // 1. Get agent form submission (read-only) — full structure like pre-login page
        $agentForm = null;
        $agentFormValues = [];
        $agentSubmission = $this->db->fetchOne(
            "SELECT fs.* FROM form_submissions fs
             JOIN users u ON fs.submitted_by = u.id
             JOIN roles r ON u.role_id = r.id
             WHERE fs.lead_id = ? AND r.name = 'agent' AND fs.status = 'submitted'
             ORDER BY fs.created_at DESC LIMIT 1",
            [$leadId]
        );
        if ($agentSubmission) {
            $agentForm = $this->formModel->getFullForm($agentSubmission['form_id']);
            $vals = $this->db->fetchAll(
                "SELECT * FROM form_submission_values WHERE submission_id = ?",
                [$agentSubmission['id']]
            );
            foreach ($vals as $v) {
                $agentFormValues[$v['field_id']] = $v['value'];
            }
            // Get agent name for display
            $agentSubmitter = $this->db->fetchOne(
                "SELECT u.name FROM users u WHERE u.id = ?",
                [$agentSubmission['submitted_by']]
            );
        }

        // 2. Get pre-login checklist submission (read-only) — full structure
        $preLoginForm = null;
        $preLoginFormValues = [];
        $preLoginSubmission = $this->db->fetchOne(
            "SELECT fs.* FROM form_submissions fs
             WHERE fs.lead_id = ? AND fs.status = 'submitted'
             ORDER BY fs.created_at DESC LIMIT 1",
            [$leadId]
        );
        if ($preLoginSubmission) {
            $preLoginForm = $this->formModel->getFullForm($preLoginSubmission['form_id']);
            $vals = $this->db->fetchAll(
                "SELECT * FROM form_submission_values WHERE submission_id = ?",
                [$preLoginSubmission['id']]
            );
            foreach ($vals as $v) {
                $preLoginFormValues[$v['field_id']] = $v['value'];
            }
            $preLoginSubmitter = $this->db->fetchOne(
                "SELECT u.name FROM users u WHERE u.id = ?",
                [$preLoginSubmission['submitted_by']]
            );
        }

        // 3. Get post-login form (editable)
        $forms = $this->formModel->getFormsByStage('POST_LOGIN');
        $postForm = !empty($forms) ? $this->formModel->getFullForm($forms[0]['id']) : null;

        // 4. Get existing post-login draft values
        $postLoginValues = [];
        $postLoginSubmission = null;
        if ($postForm) {
            $postLoginSubmission = $this->db->fetchOne(
                "SELECT * FROM form_submissions WHERE lead_id = ? AND form_id = ? AND submitted_by = ? ORDER BY created_at DESC LIMIT 1",
                [$leadId, $postForm['id'], $user['id']]
            );
            if ($postLoginSubmission) {
                $vals = $this->db->fetchAll(
                    "SELECT * FROM form_submission_values WHERE submission_id = ?",
                    [$postLoginSubmission['id']]
                );
                foreach ($vals as $v) {
                    $postLoginValues[$v['field_id']] = $v['value'];
                }
            }
        }

        // Get documents for this lead
        $documents = $this->db->fetchAll(
            "SELECT d.*, u.name as uploaded_by_name
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.lead_id = ? ORDER BY d.created_at DESC",
            [$leadId]
        );

        // Get all submissions for history
        $allSubmissions = $this->formModel->getSubmissionsForLead($leadId);

        $this->view('login_agent/post_login', [
            'title'              => 'Post-Login Form',
            'lead'               => $lead,
            // Agent form (full structure)
            'agentForm'          => $agentForm,
            'agentFormValues'    => $agentFormValues,
            // Pre-login (full structure)
            'preLoginForm'       => $preLoginForm,
            'preLoginFormValues' => $preLoginFormValues,
            // Post-login editable
            'postForm'           => $postForm,
            'postLoginValues'    => $postLoginValues,
            'postLoginSubmission'=> $postLoginSubmission,
            'documents'          => $documents,
            'allSubmissions'     => $allSubmissions,
        ]);
    }

    /**
     * Save post-login as draft
     */
    public function savePostLoginDraft(): void
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
        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM form_submissions WHERE lead_id = ? AND submitted_by = ? AND form_id = ? ORDER BY created_at DESC LIMIT 1",
            [$leadId, $user['id'], $formId]
        );

        if ($existing) {
            $this->formModel->updateSubmission($existing['id'], $values);
            $this->db->update('form_submissions', ['status' => 'draft'], 'id = ?', [$existing['id']]);
        } else {
            $submissionId = $this->formModel->submitForm($formId, $leadId, $user['id'], $values);
            $this->db->update('form_submissions', ['status' => 'draft'], 'id = ?', [$submissionId]);
        }

        logActivity($user['id'], 'post_login_draft_saved', 'lead', $leadId);

        $this->json(['success' => true, 'message' => 'Post-Login draft saved.']);
    }

    /**
     * Submit post-login form
     */
    public function submitPostLogin(): void
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
        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM form_submissions WHERE lead_id = ? AND submitted_by = ? AND form_id = ? ORDER BY created_at DESC LIMIT 1",
            [$leadId, $user['id'], $formId]
        );

        if ($existing) {
            $this->formModel->updateSubmission($existing['id'], $values);
            $this->db->update('form_submissions', [
                'status' => 'submitted',
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->formModel->submitForm($formId, $leadId, $user['id'], $values);
        }

        // Transition to POST_LOGIN
        if ($lead['workflow_stage'] !== 'POST_LOGIN') {
            $workflowModel = new \Models\Workflow();
            $workflowModel->transition($leadId, $lead['workflow_stage'], 'POST_LOGIN', $user['id'], 'login_agent', null, 'post_login_submitted');
        }

        $admins = $this->db->fetchAll("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'admin')");
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'Post-Login Submitted', "Post-login form submitted for lead #{$leadId}.", 'info', $leadId);
        }

        logActivity($user['id'], 'post_login_submitted', 'lead', $leadId);

        $this->json(['success' => true, 'message' => 'Post-Login form submitted to Admin for Review 3.']);
    }

    /**
     * AJAX endpoint for server-side cases data
     */
    public function casesAjax(): void
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
            $where .= ' AND (l.id = ? OR l.customer_name LIKE ? OR l.mobile_number LIKE ?)';
            $params[] = $search;
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($stage) {
            $where .= ' AND l.workflow_stage = ?';
            $params[] = $stage;
        }

        $total = $this->db->count('leads l', $where, $params);
        $sql = "SELECT l.id, l.customer_name, l.mobile_number, l.location, l.state,
                       l.bank_name, l.workflow_stage, l.created_at, l.updated_at
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

        $this->view('login_agent/notifications', [
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
}
