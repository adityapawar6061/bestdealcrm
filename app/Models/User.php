<?php
namespace Models;

class User
{
    private Database $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT u.*, r.name as role_name, tl.name as team_leader_name 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             LEFT JOIN users tl ON u.team_leader_id = tl.id 
             WHERE u.id = ?",
            [$id]
        );
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public function create(array $data): string
    {
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'active';
        
        return $this->db->insert('users', $data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('users', $data, 'id = ?', [$id]);
    }

    public function resetPassword(int $id, string $newPassword): int
    {
        return $this->db->update('users', [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    public function toggleStatus(int $id): int
    {
        $user = $this->findById($id);
        $newStatus = ($user['status'] === 'active') ? 'inactive' : 'active';
        return $this->db->update('users', [
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);
    }

    public function getAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = '1=1';
        $params = [];

        if (!empty($filters['role_id'])) {
            $where .= ' AND u.role_id = ?';
            $params[] = $filters['role_id'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND u.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        if (!empty($filters['team_leader_id'])) {
            $where .= ' AND u.team_leader_id = ?';
            $params[] = $filters['team_leader_id'];
        }

        $total = $this->db->count('users u', $where, $params);
        $totalPages = max(1, ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT u.id, u.name, u.email, u.username, u.mobile, u.status, 
                       u.role_id, u.team_leader_id, u.last_login_at, u.created_at,
                       r.name as role_name, tl.name as team_leader_name
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN users tl ON u.team_leader_id = tl.id 
                WHERE {$where} 
                ORDER BY u.created_at DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        $data = $this->db->fetchAll($sql, $params);

        return [
            'data'         => $data,
            'total'        => $total,
            'current_page' => $page,
            'total_pages'  => $totalPages,
            'per_page'     => $perPage,
        ];
    }

    public function getAgents(): array
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.name, u.username 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE r.name = 'agent' AND u.status = 'active' 
             ORDER BY u.name"
        );
    }

    public function getTeamLeaders(): array
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.name, u.username 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE r.name = 'team_leader' AND u.status = 'active' 
             ORDER BY u.name"
        );
    }

    public function getLoginAgents(): array
    {
        return $this->db->fetchAll(
            "SELECT u.id, u.name, u.username 
             FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE r.name = 'login_agent' AND u.status = 'active' 
             ORDER BY u.name"
        );
    }

    public function getLoginHistory(int $userId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM login_logs WHERE user_id = ? ORDER BY login_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }
}
