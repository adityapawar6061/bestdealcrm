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

        if (!$lead || $lead['assigned_to'] != $user['id']) {
            $this->redirect('/underwriting/cases', 'error', 'Case not found.');
            return;
        }

        $timeline = $this->leadModel->getTimeline($id);
        $submissions = (new \Models\DynamicForm())->getSubmissionsForLead($id);

        $this->view('underwriting/case_detail', [
            'title'       => 'Underwriting: Lead #' . $id,
            'lead'        => $lead,
            'timeline'    => $timeline,
            'submissions' => $submissions,
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
        if (!$lead || $lead['assigned_to'] != $user['id']) {
            $this->json(['error' => 'Unauthorized.'], 403);
            return;
        }

        $newStage = match($action) {
            'approve' => 'UNDERWRITING_APPROVED',
            'reject'  => 'UNDERWRITING_REJECTED',
            default   => null,
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
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Transition workflow
        $this->workflowModel->transition(
            $leadId, 'UNDERWRITING', $newStage,
            $user['id'], 'underwriting', $remark, $action
        );

        // If approved, assign to dispatch agent
        if ($action === 'approve') {
            $this->db->update('leads', [
                'workflow_stage' => 'DISPATCH',
                'updated_at'     => date('Y-m-d H:i:s'),
            ], 'id = ?', [$leadId]);

            $this->workflowModel->transition(
                $leadId, $newStage, 'DISPATCH',
                $user['id'], 'underwriting', null, 'auto_assign_dispatch'
            );
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
