<?php
namespace Controllers;

class TeamLeaderController extends BaseController
{
    private \Models\Lead $leadModel;

    public function __construct()
    {
        parent::__construct();
        $this->leadModel = new \Models\Lead();
    }

    public function dashboard(): void
    {
        $user = currentUser();
        $teamLeaderId = $user['id'];

        // Get team member IDs
        $teamMembers = $this->db->fetchAll(
            "SELECT id, name FROM users WHERE team_leader_id = ? AND status = 'active'",
            [$teamLeaderId]
        );
        $teamIds = array_column($teamMembers, 'id');
        $teamIds[] = $teamLeaderId; // Include self

        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));

        $stats = [
            'team_size'       => count($teamMembers),
            'total_leads'     => $this->db->count('leads', "assigned_to IN ({$placeholders})", $teamIds),
            'agent_draft'     => $this->db->count('leads', "assigned_to IN ({$placeholders}) AND workflow_stage = 'AGENT_DRAFT'", $teamIds),
            'pending_review'  => $this->db->count('leads', "assigned_to IN ({$placeholders}) AND workflow_stage = 'ADMIN_REVIEW_1'", $teamIds),
            'submitted'       => $this->db->count('leads', "assigned_to IN ({$placeholders}) AND workflow_stage = 'AGENT_SUBMITTED'", $teamIds),
            'returned'        => $this->db->count('leads', "assigned_to IN ({$placeholders}) AND workflow_stage = 'RETURNED_TO_AGENT'", $teamIds),
            'approved'        => $this->db->count('leads', "assigned_to IN ({$placeholders}) AND workflow_stage = 'LOGIN_APPROVED'", $teamIds),
            'completed'       => $this->db->count('leads', "assigned_to IN ({$placeholders}) AND workflow_stage = 'COMPLETED'", $teamIds),
        ];

        $recentLeads = $this->db->fetchAll(
            "SELECT l.*, u.name as assigned_to_name 
             FROM leads l 
             LEFT JOIN users u ON l.assigned_to = u.id 
             WHERE l.assigned_to IN ({$placeholders})
             ORDER BY l.created_at DESC LIMIT 10",
            $teamIds
        );

        $this->view('team_leader/dashboard', [
            'title'       => 'Team Leader Dashboard',
            'stats'       => $stats,
            'teamMembers' => $teamMembers,
            'recentLeads' => $recentLeads,
        ]);
    }

    public function team(): void
    {
        $user = currentUser();
        $teamMembers = $this->db->fetchAll(
            "SELECT u.id, u.name, u.email, u.username, u.status, u.last_login_at,
                    COUNT(l.id) as lead_count
             FROM users u
             LEFT JOIN leads l ON l.assigned_to = u.id
             WHERE u.team_leader_id = ? AND u.status = 'active'
             GROUP BY u.id
             ORDER BY u.name",
            [$user['id']]
        );

        $this->view('team_leader/team', [
            'title'       => 'My Team',
            'teamMembers' => $teamMembers,
        ]);
    }

    public function teamLeads(): void
    {
        $user = currentUser();
        $filters = [
            'search'         => $_GET['search'] ?? '',
            'workflow_stage' => $_GET['workflow_stage'] ?? '',
        ];
        $page = (int)($_GET['page'] ?? 1);

        $leads = $this->leadModel->getByTeamLeader($user['id'], $filters, $page);

        $this->view('team_leader/team_leads', [
            'title'  => 'Team Leads',
            'leads'  => $leads,
            'filters'=> $filters,
        ]);
    }

    public function notifications(): void
    {
        $user = currentUser();
        $notifications = $this->db->fetchAll(
            "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
            [$user['id']]
        );

        $this->view('team_leader/notifications', [
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
