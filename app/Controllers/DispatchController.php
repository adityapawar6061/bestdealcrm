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

        if (!$lead || $lead['assigned_to'] != $user['id']) {
            $this->redirect('/dispatch/cases', 'error', 'Case not found.');
            return;
        }

        $timeline = $this->leadModel->getTimeline($id);

        // 1. Agent form values (read-only)
        $agentSubmission = $this->db->fetchOne(
            "SELECT fs.* FROM form_submissions fs
             JOIN users u ON fs.submitted_by = u.id
             JOIN roles r ON u.role_id = r.id
             WHERE fs.lead_id = ? AND r.name = 'agent'
             ORDER BY fs.created_at DESC LIMIT 1",
            [$id]
        );
        $agentValues = [];
        if ($agentSubmission) {
            $agentValues = $this->db->fetchAll(
                "SELECT fsv.*, ff.label, ff.field_name, ff.type, ff.field_type
                 FROM form_submission_values fsv
                 JOIN form_fields ff ON fsv.field_id = ff.id
                 WHERE fsv.submission_id = ? ORDER BY ff.display_order",
                [$agentSubmission['id']]
            );
        }

        // 2. Pre-login checklist values (read-only)
        $preLoginSubmission = $this->db->fetchOne(
            "SELECT fs.* FROM form_submissions fs
             JOIN forms f ON fs.form_id = f.id
             WHERE fs.lead_id = ? AND f.code = 'PRE_LOGIN_CHECKLIST'
             ORDER BY fs.created_at DESC LIMIT 1",
            [$id]
        );
        $preLoginValues = [];
        if ($preLoginSubmission) {
            $preLoginValues = $this->db->fetchAll(
                "SELECT fsv.*, ff.label, ff.field_name, ff.type, ff.field_type
                 FROM form_submission_values fsv
                 JOIN form_fields ff ON fsv.field_id = ff.id
                 WHERE fsv.submission_id = ? ORDER BY ff.display_order",
                [$preLoginSubmission['id']]
            );
        }

        // 3. Post-login form values (read-only)
        $postLoginSubmission = $this->db->fetchOne(
            "SELECT fs.* FROM form_submissions fs
             JOIN forms f ON fs.form_id = f.id
             WHERE fs.lead_id = ? AND f.code = 'POST_LOGIN_FORM'
             ORDER BY fs.created_at DESC LIMIT 1",
            [$id]
        );
        $postLoginValues = [];
        if ($postLoginSubmission) {
            $postLoginValues = $this->db->fetchAll(
                "SELECT fsv.*, ff.label, ff.field_name, ff.type, ff.field_type
                 FROM form_submission_values fsv
                 JOIN form_fields ff ON fsv.field_id = ff.id
                 WHERE fsv.submission_id = ? ORDER BY ff.display_order",
                [$postLoginSubmission['id']]
            );
        }

        // 4. Documents
        $documents = $this->db->fetchAll(
            "SELECT * FROM documents WHERE lead_id = ? ORDER BY created_at DESC",
            [$id]
        );

        // 5. Remarks
        $remarks = $this->db->fetchAll(
            "SELECT r.*, u.name as user_name FROM remarks r
             LEFT JOIN users u ON r.user_id = u.id
             WHERE r.lead_id = ? ORDER BY r.created_at DESC",
            [$id]
        );

        $this->view('dispatch/case_detail', [
            'title'              => 'Dispatch: Lead #' . $id,
            'lead'               => $lead,
            'timeline'           => $timeline,
            'agentValues'        => $agentValues,
            'preLoginValues'     => $preLoginValues,
            'postLoginValues'    => $postLoginValues,
            'documents'          => $documents,
            'remarks'            => $remarks,
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
        if (!$lead || $lead['assigned_to'] != $user['id']) {
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
            'created_at' => date('Y-m-d H:i:s'),
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
