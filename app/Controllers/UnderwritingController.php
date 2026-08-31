<?php
namespace Controllers;

class UnderwritingController extends BaseController
{
    private \Models\Lead $leadModel;
    private \Models\Workflow $workflowModel;

    public function __construct()
    {
        parent::__construct();
        $this->leadModel = new \Models\Lead();
        $this->workflowModel = new \Models\Workflow();
    }

    public function dashboard(): void
    {
        $user = currentUser();
        $stats = [
            'assigned'    => $this->db->count('leads', "assigned_to = ? AND workflow_stage = 'UNDERWRITING'", [$user['id']]),
            'approved'    => $this->db->count('leads', "assigned_to = ? AND workflow_stage = 'UNDERWRITING_APPROVED'", [$user['id']]),
            'rejected'    => $this->db->count('leads', "assigned_to = ? AND workflow_stage = 'UNDERWRITING_REJECTED'", [$user['id']]),
            'dispatch'    => $this->db->count('leads', "assigned_to = ? AND workflow_stage = 'DISPATCH'", [$user['id']]),
        ];

        $recentLeads = $this->leadModel->getByAgent($user['id'], [], 1, 10);

        $this->view('underwriting/dashboard', [
            'title'       => 'Underwriting Dashboard',
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

        $this->view('underwriting/cases', [
            'title'  => 'Underwriting Cases',
            'leads'  => $leads,
            'filters'=> $filters,
        ]);
    }

    public function caseDetail(int $id): void
    {
        $user = currentUser();
        $lead = $this->leadModel->findById($id);

        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->redirect('/underwriting/cases', 'error', 'Case not found.');
            return;
        }

        $timeline = $this->leadModel->getTimeline($id);
        $formModel = new \Models\DynamicForm();

        // Helper: get form submission with full structure
        $getFormWithValues = function($leadId, $roleName = null, $workflowStage = null) use ($formModel) {
            // Find form_id first by workflow_stage (avoids needing f.code column)
            $formId = null;
            if ($workflowStage) {
                $forms = $formModel->getFormsByStage($workflowStage);
                if (!empty($forms)) $formId = $forms[0]['id'];
            }

            $where = 'fs.lead_id = ? AND fs.status = \'submitted\'';
            $params = [$leadId];
            if ($roleName) {
                $where .= ' AND r.name = ?';
                $params[] = $roleName;
            }
            if ($formId) {
                $where .= ' AND fs.form_id = ?';
                $params[] = $formId;
            }
            $submission = $this->db->fetchOne(
                "SELECT fs.*, u.name as submitted_by_name, r.name as role_name
                 FROM form_submissions fs
                 JOIN users u ON fs.submitted_by = u.id
                 JOIN roles r ON u.role_id = r.id
                 WHERE {$where}
                 ORDER BY fs.created_at DESC LIMIT 1",
                $params
            );
            if (!$submission) return [null, [], null, null];
            $form = $formModel->getFullForm($submission['form_id']);
            $vals = $this->db->fetchAll(
                "SELECT * FROM form_submission_values WHERE submission_id = ?",
                [$submission['id']]
            );
            $values = [];
            foreach ($vals as $v) $values[$v['field_id']] = $v['value'];
            return [$form, $values, $submission['submitted_by_name'], $submission['role_name']];
        };

        // 1. Agent form (full structure, read-only)
        [$agentForm, $agentFormValues, $agentName, $agentRole] = $getFormWithValues($id, 'agent');

        // 2. Pre-login checklist (full structure, read-only)
        [$preLoginForm, $preLoginFormValues, $preLoginName, $preLoginRole] = $getFormWithValues($id, null, 'LOGIN_AGENT_DRAFT');

        // 3. Post-login form (full structure, read-only)
        [$postLoginForm, $postLoginFormValues, $postLoginName, $postLoginRole] = $getFormWithValues($id, null, 'POST_LOGIN');

        // 4. Underwriting form (editable)
        $uwForms = $formModel->getFormsByStage('UNDERWRITING');
        $uwForm = !empty($uwForms) ? $formModel->getFullForm($uwForms[0]['id']) : null;
        $uwFormValues = [];
        if ($uwForm) {
            $uwSubmission = $this->db->fetchOne(
                "SELECT * FROM form_submissions WHERE lead_id = ? AND form_id = ? AND submitted_by = ? ORDER BY created_at DESC LIMIT 1",
                [$id, $uwForm['id'], $user['id']]
            );
            if ($uwSubmission) {
                $vals = $this->db->fetchAll(
                    "SELECT * FROM form_submission_values WHERE submission_id = ?",
                    [$uwSubmission['id']]
                );
                foreach ($vals as $v) $uwFormValues[$v['field_id']] = $v['value'];
            }
        }

        // 5. Documents
        $documents = $this->db->fetchAll(
            "SELECT d.*, u.name as uploaded_by_name FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.lead_id = ? ORDER BY d.created_at DESC",
            [$id]
        );

        // 6. All submissions history
        $allSubmissions = $formModel->getSubmissionsForLead($id);

        // 7. Remarks
        $remarks = $this->db->fetchAll(
            "SELECT r.*, u.name as user_name FROM remarks r
             LEFT JOIN users u ON r.user_id = u.id
             WHERE r.lead_id = ? ORDER BY r.created_at DESC",
            [$id]
        );

        // 8. Assigned agent name
        $assignedAgentName = '';
        if (!empty($lead['assigned_to'])) {
            $assignedAgent = $this->db->fetchOne("SELECT name FROM users WHERE id = ?", [$lead['assigned_to']]);
            $assignedAgentName = $assignedAgent ? $assignedAgent['name'] : '';
        }

        $this->view('underwriting/case_detail', [
            'title'                => 'Underwriting: Lead #' . $id,
            'lead'                 => $lead,
            'timeline'             => $timeline,
            'agentForm'            => $agentForm,
            'agentFormValues'      => $agentFormValues,
            'agentName'            => $agentName ?? '',
            'preLoginForm'         => $preLoginForm,
            'preLoginFormValues'   => $preLoginFormValues,
            'preLoginName'         => $preLoginName ?? '',
            'postLoginForm'        => $postLoginForm,
            'postLoginFormValues'  => $postLoginFormValues,
            'postLoginName'        => $postLoginName ?? '',
            'uwForm'               => $uwForm,
            'uwFormValues'         => $uwFormValues,
            'documents'            => $documents,
            'allSubmissions'       => $allSubmissions,
            'remarks'              => $remarks,
            'assignedAgentName'    => $assignedAgentName,
        ]);
    }

    public function processCase(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $remark = $_POST['underwriting_remark'] ?? '';
        $user = currentUser();

        $lead = $this->leadModel->findById($leadId);
        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        $newStage = null;
        switch ($action) {
            case 'approve': $newStage = 'UNDERWRITING_APPROVED'; break;
            case 'reject': $newStage = 'UNDERWRITING_REJECTED'; break;
        };

        if (!$newStage) {
            $this->json(['error' => 'Invalid action.'], 400);
            return;
        }

        // Store remark
        $this->db->insert('remarks', [
            'lead_id'    => $leadId,
            'user_id'    => $user['id'],
            'stage'      => 'UNDERWRITING',
            'remark'     => $remark,
            'created_at' => nowIST(),
        ]);

        // Transition workflow
        $this->workflowModel->transition(
            $leadId, 'UNDERWRITING', $newStage,
            $user['id'], 'underwriting', $remark, $action
        );

        // If approved, assign to a dispatch agent
        if ($action === 'approve') {
            // Find an available dispatch agent
            $dispatchAgent = $this->db->fetchOne(
                "SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id
                 WHERE r.name = 'dispatch' AND u.status = 'active'
                 ORDER BY (SELECT COUNT(*) FROM leads l WHERE l.assigned_to = u.id AND l.workflow_stage = 'DISPATCH') ASC
                 LIMIT 1"
            );
            $assignedTo = $dispatchAgent ? $dispatchAgent['id'] : null;

            $updateData = [
                'workflow_stage' => 'DISPATCH',
                'updated_at'     => nowIST(),
            ];
            if ($assignedTo) {
                $updateData['assigned_to'] = $assignedTo;
            }
            $this->db->update('leads', $updateData, 'id = ?', [$leadId]);

            $this->workflowModel->transition(
                $leadId, $newStage, 'DISPATCH',
                $user['id'], 'underwriting', null, 'auto_assign_dispatch'
            );

            // Notify the assigned dispatch agent
            if ($assignedTo) {
                $agentName = $dispatchAgent ? $this->db->fetchOne("SELECT name FROM users WHERE id = ?", [$assignedTo]) : null;
                createNotification($assignedTo, 'New Case Assigned',
                    "Lead #{$leadId} has been approved by underwriting and assigned to you for dispatch.",
                    'info', $leadId);
            }
        }

        // Notify admins
        $admins = $this->db->fetchAll("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'admin')");
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'Underwriting ' . ucfirst($action), 
                "Lead #{$leadId} has been " . ($action === 'approve' ? 'approved' : 'rejected') . " by underwriting.", 
                $action === 'approve' ? 'success' : 'warning', $leadId);
        }

        logActivity($user['id'], 'underwriting_' . $action, 'lead', $leadId, null, $remark);

        $this->json(['success' => true, 'message' => "Underwriting {$action}d successfully."]);
    }

    /**
     * Save underwriting form as draft
     */
    public function saveFormDraft(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $values = $_POST['form_data'] ?? [];
        $user = currentUser();
        $formModel = new \Models\DynamicForm();

        $lead = $this->leadModel->findById($leadId);
        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        // Process file uploads
        $formModel->processFileUploads($leadId, $user['id'], $values);

        // Get or create submission
        $uwForms = $formModel->getFormsByStage('UNDERWRITING');
        if (empty($uwForms)) {
            $this->json(['error' => 'No underwriting form configured.'], 400);
            return;
        }
        $formId = $uwForms[0]['id'];

        $existing = $this->db->fetchOne(
            "SELECT id FROM form_submissions WHERE lead_id = ? AND form_id = ? AND submitted_by = ? ORDER BY created_at DESC LIMIT 1",
            [$leadId, $formId, $user['id']]
        );

        if ($existing) {
            $formModel->updateSubmission($existing['id'], $values);
            $this->db->update('form_submissions', ['status' => 'draft'], 'id = ?', [$existing['id']]);
        } else {
            $submissionId = $formModel->submitForm($formId, $leadId, $user['id'], $values);
            $this->db->update('form_submissions', ['status' => 'draft'], 'id = ?', [$submissionId]);
        }

        logActivity($user['id'], 'underwriting_draft_saved', 'lead', $leadId);
        $this->json(['success' => true, 'message' => 'Underwriting draft saved.']);
    }

    /**
     * Submit underwriting form
     */
    public function submitForm(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $values = $_POST['form_data'] ?? [];
        $user = currentUser();
        $formModel = new \Models\DynamicForm();

        $lead = $this->leadModel->findById($leadId);
        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        // Process file uploads
        $formModel->processFileUploads($leadId, $user['id'], $values);

        $uwForms = $formModel->getFormsByStage('UNDERWRITING');
        if (empty($uwForms)) {
            $this->json(['error' => 'No underwriting form configured.'], 400);
            return;
        }
        $formId = $uwForms[0]['id'];

        $existing = $this->db->fetchOne(
            "SELECT id FROM form_submissions WHERE lead_id = ? AND form_id = ? AND submitted_by = ? ORDER BY created_at DESC LIMIT 1",
            [$leadId, $formId, $user['id']]
        );

        if ($existing) {
            $formModel->updateSubmission($existing['id'], $values);
            $this->db->update('form_submissions', ['status' => 'submitted', 'submitted_at' => nowIST()], 'id = ?', [$existing['id']]);
        } else {
            $submissionId = $formModel->submitForm($formId, $leadId, $user['id'], $values);
        }

        logActivity($user['id'], 'underwriting_form_submitted', 'lead', $leadId);
        $this->json(['success' => true, 'message' => 'Underwriting form submitted.']);
    }

    public function notifications(): void
    {
        $user = currentUser();
        $notifications = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
            [$user['id']]
        );

        $this->view('underwriting/notifications', [
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
