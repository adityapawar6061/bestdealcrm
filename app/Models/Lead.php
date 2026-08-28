<?php
namespace Models;

class Lead
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->ensureColumns();
    }

    /**
     * Auto-create columns needed for lead tracking
     */
    private function ensureColumns(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;
        try {
            $colCheck = $this->db->fetchOne(
                "SHOW COLUMNS FROM leads LIKE 'created_by'"
            );
            if (!$colCheck) {
                $this->db->query("ALTER TABLE `leads` ADD COLUMN `created_by` INT UNSIGNED DEFAULT NULL AFTER `assigned_by`");
            }
        } catch (\Throwable $e) {
            // Column might already exist or permissions issue
        }
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT l.*, u.name as assigned_to_name, up.name as assigned_by_name 
             FROM leads l 
             LEFT JOIN users u ON l.assigned_to = u.id 
             LEFT JOIN users up ON l.assigned_by = up.id 
             WHERE l.id = ?",
            [$id]
        );
    }

    public function create(array $data): string
    {
        $data['created_at'] = nowIST();
        $data['updated_at'] = nowIST();
        $data['workflow_stage'] = $data['workflow_stage'] ?? 'LEAD_UPLOADED';
        return $this->db->insert('leads', $data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = nowIST();
        return $this->db->update('leads', $data, 'id = ?', [$id]);
    }

    public function updateStage(int $id, string $stage, ?string $remark = null): int
    {
        $result = $this->db->update('leads', [
            'workflow_stage' => $stage,
            'updated_at'     => nowIST(),
        ], 'id = ?', [$id]);

        if ($result > 0) {
            $lead = $this->findById($id);
            logActivity(
                currentUser()['id'] ?? 0,
                'stage_change',
                'lead',
                $id,
                null,
                json_encode(['new_stage' => $stage, 'remark' => $remark])
            );
        }

        return $result;
    }

    public function assign(int $leadId, int $assignedTo, ?int $assignedBy = null, ?string $remark = null, bool $preserveStage = false): string
    {
        $assignedBy = $assignedBy ?? (currentUser()['id'] ?? null);
        
        // Record assignment
        $assignmentId = $this->db->insert('lead_assignments', [
            'lead_id'     => $leadId,
            'assigned_to' => $assignedTo,
            'assigned_by' => $assignedBy,
            'assigned_at' => nowIST(),
            'status'      => 'active',
            'remark'      => $remark,
        ]);

        // Update lead
        $updateData = [
            'assigned_to' => $assignedTo,
            'assigned_by' => $assignedBy,
            'updated_at'  => nowIST(),
        ];
        if (!$preserveStage) {
            $updateData['workflow_stage'] = 'LEAD_ASSIGNED';
        }
        $this->db->update('leads', $updateData, 'id = ?', [$leadId]);

        // Log activity
        logActivity($assignedBy, 'lead_assigned', 'lead', $leadId, null, json_encode([
            'assigned_to' => $assignedTo,
        ]));

        // Create notification
        createNotification($assignedTo, 'New Lead Assigned', "You have been assigned a new lead (#{$leadId}).", 'info', $leadId);

        return $assignmentId;
    }

    public function getByAgent(int $agentId, array $filters = [], int $page = 1, int $perPage = 25): array
    {
        // Show leads the agent created OR is currently assigned to
        $where = '(l.assigned_to = ? OR (COALESCE(l.created_by, 0) = ? AND l.created_by IS NOT NULL))';
        $params = [$agentId, $agentId];

        return $this->searchLeads($where, $params, $filters, $page, $perPage);
    }

    public function getByTeamLeader(int $teamLeaderId, array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = 'l.assigned_to IN (SELECT id FROM users WHERE team_leader_id = ?)';
        $params = [$teamLeaderId];

        return $this->searchLeads($where, $params, $filters, $page, $perPage);
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        return $this->searchLeads('1=1', [], $filters, $page, $perPage);
    }

    private function searchLeads(string $baseWhere, array $baseParams, array $filters, int $page, int $perPage): array
    {
        $where = $baseWhere;
        $params = $baseParams;

        if (!empty($filters['search'])) {
            $where .= ' AND (l.id = ? OR l.customer_name LIKE ? OR l.mobile_number LIKE ? OR l.pan_number LIKE ?)';
            $search = $filters['search'];
            $params[] = $search;
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if (!empty($filters['workflow_stage'])) {
            $where .= ' AND l.workflow_stage = ?';
            $params[] = $filters['workflow_stage'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND l.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['bank_name'])) {
            $where .= ' AND l.bank_name = ?';
            $params[] = $filters['bank_name'];
        }
        if (!empty($filters['assigned_to'])) {
            $where .= ' AND l.assigned_to = ?';
            $params[] = $filters['assigned_to'];
        }
        if (!empty($filters['disposition'])) {
            if ($filters['disposition'] === '__pending__') {
                // Pending = no disposition set (try both columns)
                $pendingParts = [];
                if ($this->hasColumnStatic('leads', 'disposition')) {
                    $pendingParts[] = "(l.disposition IS NULL OR l.disposition = '')";
                }
                if ($this->hasColumnStatic('leads', 'agent_disposition')) {
                    $pendingParts[] = "(l.agent_disposition IS NULL OR l.agent_disposition = '')";
                }
                if (!empty($pendingParts)) {
                    $where .= ' AND (' . implode(' AND ', $pendingParts) . ')';
                }
            } else {
                // Filter by specific disposition value
                $dispParts = [];
                if ($this->hasColumnStatic('leads', 'disposition')) {
                    $dispParts[] = 'l.disposition = ?';
                }
                if ($this->hasColumnStatic('leads', 'agent_disposition')) {
                    $dispParts[] = 'l.agent_disposition = ?';
                }
                if (!empty($dispParts)) {
                    $where .= ' AND (' . implode(' OR ', $dispParts) . ')';
                    // Add param once for each part (same value)
                    foreach ($dispParts as $_) { $params[] = $filters['disposition']; }
                }
            }
        }
        if (!empty($filters['date_from'])) {
            $where .= ' AND l.created_at >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND l.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $total = $this->db->count('leads l', $where, $params);
        $totalPages = max(1, ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT l.*, u.name as assigned_to_name 
                FROM leads l 
                LEFT JOIN users u ON l.assigned_to = u.id 
                WHERE {$where} 
                ORDER BY l.created_at DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $data = $this->db->fetchAll($sql, $params);

        return [
            'data'         => $data,
            'total'        => $total,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'per_page'     => $perPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function getStats(string $where = '1=1', array $params = []): array
    {
        $sql = "SELECT workflow_stage, COUNT(*) as count 
                FROM leads 
                WHERE {$where} 
                GROUP BY workflow_stage";
        return $this->db->fetchAll($sql, $params);
    }

    public function getTimeline(int $leadId): array
    {
        return $this->db->fetchAll(
            "SELECT wh.*, u.name as performed_by_name 
             FROM workflow_history wh 
             LEFT JOIN users u ON wh.performed_by = u.id 
             WHERE wh.lead_id = ? 
             ORDER BY wh.created_at ASC",
            [$leadId]
        );
    }

    public function getAssignmentHistory(int $leadId): array
    {
        return $this->db->fetchAll(
            "SELECT la.*, u1.name as assigned_to_name, u2.name as assigned_by_name 
             FROM lead_assignments la 
             LEFT JOIN users u1 ON la.assigned_to = u1.id 
             LEFT JOIN users u2 ON la.assigned_by = u2.id 
             WHERE la.lead_id = ? 
             ORDER BY la.assigned_at DESC",
            [$leadId]
        );
    }

    public function checkDuplicateMobile(string $mobile): ?array
    {
        $result = $this->db->fetchOne("SELECT id, customer_name FROM leads WHERE mobile_number = ?", [$mobile]);
        return $result ?: null;
    }

    public function getUnassignedCount(): int
    {
        return $this->db->count('leads', 'assigned_to IS NULL');
    }

    /**
     * Check if a column exists in a table (cached per request)
     */
    private function hasColumnStatic(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (isset($cache[$key])) return $cache[$key];
        try {
            $result = $this->db->fetchOne(
                "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
                [$table, $column]
            );
            $cache[$key] = ($result !== null);
        } catch (\Throwable $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }
}
