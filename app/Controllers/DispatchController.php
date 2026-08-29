<?php
namespace Controllers;

class DispatchController extends BaseController
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
            'assigned'  => $this->db->count('leads', "assigned_to = ? AND workflow_stage = 'DISPATCH'", [$user['id']]),
            'completed' => $this->db->count('leads', "assigned_to = ? AND workflow_stage = 'COMPLETED'", [$user['id']]),
        ];

        $recentLeads = $this->leadModel->getByAgent($user['id'], [], 1, 10);

        $this->view('dispatch/dashboard', [
            'title'       => 'Dispatch Dashboard',
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

        $this->view('dispatch/cases', [
            'title'  => 'Dispatch Cases',
            'leads'  => $leads,
            'filters'=> $filters,
        ]);
    }

    public function caseDetail(int $id): void
    {
        $user = currentUser();
        $lead = $this->leadModel->findById($id);

        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->redirect('/dispatch/cases', 'error', 'Case not found.');
            return;
        }

        $timeline = $this->leadModel->getTimeline($id);
        $formModel = new \Models\DynamicForm();

        // Helper: get form submission with full structure
        $getFormWithValues = function($leadId, $roleName = null, $formCode = null) use ($formModel) {
            $where = 'fs.lead_id = ? AND fs.status = \'submitted\'';
            $params = [$leadId];
            if ($roleName) {
                $where .= ' AND r.name = ?';
                $params[] = $roleName;
            }
            if ($formCode) {
                $where .= ' AND f.code = ?';
                $params[] = $formCode;
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
        [$preLoginForm, $preLoginFormValues, $preLoginName, $preLoginRole] = $getFormWithValues($id, null, 'PRE_LOGIN_CHECKLIST');

        // 3. Post-login form (full structure, read-only)
        [$postLoginForm, $postLoginFormValues, $postLoginName, $postLoginRole] = $getFormWithValues($id, null, 'POST_LOGIN_FORM');

        // 4. Underwriting form (full structure, read-only)
        [$uwForm, $uwFormValues, $uwName, $uwRole] = $getFormWithValues($id, null, 'underwriting_form');

        // 5. Dispatch form (editable)
        $dispatchForms = $formModel->getFormsByStage('DISPATCH');
        $dispatchForm = !empty($dispatchForms) ? $formModel->getFullForm($dispatchForms[0]['id']) : null;
        $dispatchFormValues = [];
        if ($dispatchForm) {
            $dispatchSubmission = $this->db->fetchOne(
                "SELECT * FROM form_submissions WHERE lead_id = ? AND form_id = ? AND submitted_by = ? ORDER BY created_at DESC LIMIT 1",
                [$id, $dispatchForm['id'], $user['id']]
            );
            if ($dispatchSubmission) {
                $vals = $this->db->fetchAll(
                    "SELECT * FROM form_submission_values WHERE submission_id = ?",
                    [$dispatchSubmission['id']]
                );
                foreach ($vals as $v) $dispatchFormValues[$v['field_id']] = $v['value'];
            }
        }

        // 6. Documents
        $documents = $this->db->fetchAll(
            "SELECT d.*, u.name as uploaded_by_name FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.lead_id = ? ORDER BY d.created_at DESC",
            [$id]
        );

        // 7. All submissions history
        $allSubmissions = $formModel->getSubmissionsForLead($id);

        // 8. Remarks
        $remarks = $this->db->fetchAll(
            "SELECT r.*, u.name as user_name FROM remarks r
             LEFT JOIN users u ON r.user_id = u.id
             WHERE r.lead_id = ? ORDER BY r.created_at DESC",
            [$id]
        );

        $this->view('dispatch/case_detail', [
            'title'                 => 'Dispatch: Lead #' . $id,
            'lead'                  => $lead,
            'timeline'              => $timeline,
            'agentForm'             => $agentForm,
            'agentFormValues'       => $agentFormValues,
            'agentName'             => $agentName ?? '',
            'preLoginForm'          => $preLoginForm,
            'preLoginFormValues'    => $preLoginFormValues,
            'preLoginName'          => $preLoginName ?? '',
            'postLoginForm'         => $postLoginForm,
            'postLoginFormValues'   => $postLoginFormValues,
            'postLoginName'         => $postLoginName ?? '',
            'uwForm'                => $uwForm,
            'uwFormValues'          => $uwFormValues,
            'uwName'                => $uwName ?? '',
            'dispatchForm'          => $dispatchForm,
            'dispatchFormValues'    => $dispatchFormValues,
            'documents'             => $documents,
            'allSubmissions'        => $allSubmissions,
            'remarks'               => $remarks,
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
        $remark = $_POST['dispatch_remark'] ?? '';
        $user = currentUser();

        $lead = $this->leadModel->findById($leadId);
        if (!$lead || ($lead['assigned_to'] != $user['id'] && ($lead['created_by'] ?? 0) != $user['id'])) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        $newStage = null;
        switch ($action) {
            case 'complete': $newStage = 'COMPLETED'; break;
        };

        if (!$newStage) {
            $this->json(['error' => 'Invalid action.'], 400);
            return;
        }

        // Store remark
        $this->db->insert('remarks', [
            'lead_id'    => $leadId,
            'user_id'    => $user['id'],
            'stage'      => 'DISPATCH',
            'remark'     => $remark,
            'created_at' => nowIST(),
        ]);

        // Transition workflow
        $this->workflowModel->transition(
            $leadId, 'DISPATCH', $newStage,
            $user['id'], 'dispatch', $remark, $action
        );

        // Notify admins
        $admins = $this->db->fetchAll("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE name = 'admin')");
        foreach ($admins as $admin) {
            createNotification($admin['id'], 'Dispatch Completed', 
                "Lead #{$leadId} has been marked as completed by dispatch.", 
                'success', $leadId);
        }

        logActivity($user['id'], 'dispatch_completed', 'lead', $leadId, null, $remark);

        $this->json(['success' => true, 'message' => 'Lead marked as completed.']);
    }

    /**
     * Save dispatch form as draft
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

        $dispatchForms = $formModel->getFormsByStage('DISPATCH');
        if (empty($dispatchForms)) {
            $this->json(['error' => 'No dispatch form configured.'], 400);
            return;
        }
        $formId = $dispatchForms[0]['id'];

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

        logActivity($user['id'], 'dispatch_draft_saved', 'lead', $leadId);
        $this->json(['success' => true, 'message' => 'Dispatch draft saved.']);
    }

    /**
     * Submit dispatch form
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

        $dispatchForms = $formModel->getFormsByStage('DISPATCH');
        if (empty($dispatchForms)) {
            $this->json(['error' => 'No dispatch form configured.'], 400);
            return;
        }
        $formId = $dispatchForms[0]['id'];

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

        logActivity($user['id'], 'dispatch_form_submitted', 'lead', $leadId);
        $this->json(['success' => true, 'message' => 'Dispatch form submitted.']);
    }

    public function notifications(): void
    {
        $user = currentUser();
        $notifications = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
            [$user['id']]
        );

        $this->view('dispatch/notifications', [
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
