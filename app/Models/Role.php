<?php
namespace Models;

class Role
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM roles ORDER BY name");
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM roles WHERE id = ?", [$id]);
    }

    public function getPermissions(int $roleId): array
    {
        return $this->db->fetchAll(
            "SELECT p.* FROM permissions p 
             JOIN role_permissions rp ON p.id = rp.permission_id 
             WHERE rp.role_id = ? 
             ORDER BY p.name",
            [$roleId]
        );
    }

    public function setPermissions(int $roleId, array $permissionIds): void
    {
        $this->db->delete('role_permissions', 'role_id = ?', [$roleId]);
        
        if (!empty($permissionIds)) {
            $this->db->beginTransaction();
            try {
                foreach ($permissionIds as $permId) {
                    $this->db->insert('role_permissions', [
                        'role_id'       => $roleId,
                        'permission_id' => $permId,
                    ]);
                }
                $this->db->commit();
            } catch (\Exception $e) {
                $this->db->rollback();
                throw $e;
            }
        }
    }

    public function getAllPermissions(): array
    {
        return $this->db->fetchAll("SELECT * FROM permissions ORDER BY name");
    }

    public function create(array $data): string
    {
        return $this->db->insert('roles', $data);
    }

    public function update(int $id, array $data): int
    {
        return $this->db->update('roles', $data, 'id = ?', [$id]);
    }

    public function delete(int $id): int
    {
        return $this->db->delete('roles', 'id = ?', [$id]);
    }
}
