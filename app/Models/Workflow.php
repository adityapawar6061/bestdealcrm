<?php
namespace Models;

class Workflow
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function getAllStages(): array
    {
        return $this->db->fetchAll("SELECT * FROM workflow_stages ORDER BY display_order");
    }

    public function getStage(string $stageName): ?array
    {
        return $this->db->fetchOne("SELECT * FROM workflow_stages WHERE name = ?", [$stageName]);
    }

    public function getTransitions(string $fromStage): array
    {
        return $this->db->fetchAll(
            "SELECT wt.*, ws.name as to_stage_name, ws.label as to_stage_label 
             FROM workflow_transitions wt 
             JOIN workflow_stages ws ON wt.to_stage = ws.name 
             WHERE wt.from_stage = ? 
             ORDER BY ws.display_order",
            [$fromStage]
        );
    }

    public function transition(
        int $leadId, 
        string $fromStage, 
        string $toStage, 
        int $performedBy, 
        string $userRole,
        ?string $remark = null,
        ?string $action = null
    ): void {
        // Update lead stage
        \Database::getInstance()->update('leads', [
            'workflow_stage' => $toStage,
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [$leadId]);

        // Record in workflow history
        \Database::getInstance()->insert('workflow_history', [
            'lead_id'        => $leadId,
            'previous_stage' => $fromStage,
            'new_stage'      => $toStage,
            'action'         => $action ?? $toStage,
            'performed_by'   => $performedBy,
            'user_role'      => $userRole,
            'remark'         => $remark,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        // Log activity
        logActivity($performedBy, 'workflow_transition', 'lead', $leadId, $fromStage, $toStage);
    }

    public function getHistory(int $leadId): array
    {
        return \Database::getInstance()->fetchAll(
            "SELECT wh.*, u.name as performed_by_name 
             FROM workflow_history wh 
             LEFT JOIN users u ON wh.performed_by = u.id 
             WHERE wh.lead_id = ? 
             ORDER BY wh.created_at ASC",
            [$leadId]
        );
    }

    public function getPendingApprovals(string $stage, ?int $assignedTo = null): array
    {
        $where = 'l.workflow_stage = ?';
        $params = [$stage];

        if ($assignedTo !== null) {
            $where .= ' AND l.assigned_to = ?';
            $params[] = $assignedTo;
        }

        return \Database::getInstance()->fetchAll(
            "SELECT l.*, u.name as assigned_to_name 
             FROM leads l 
             LEFT JOIN users u ON l.assigned_to = u.id 
             WHERE {$where} 
             ORDER BY l.updated_at DESC",
            $params
        );
    }
}
