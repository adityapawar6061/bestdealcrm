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
    public function fillForm(int $leadId): void
    {
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
}
