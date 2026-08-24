<?php
namespace Controllers;

class TableBuilderController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(): void
    {
        $tables = $this->db->fetchAll("SELECT * FROM dynamic_tables ORDER BY created_at DESC");

        $this->view('admin/table_builder/index', [
            'title'  => 'Table Builder',
            'tables' => $tables,
        ]);
    }

    public function create(): void
    {
        $this->view('admin/table_builder/create', [
            'title' => 'Create Dynamic Table',
        ]);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $data = $this->sanitize($_POST);
        $errors = $this->validate($data, ['name' => 'required', 'display_name' => 'required']);

        if (!empty($errors)) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        // Validate table name (only alphanumeric and underscores)
        $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $data['name']);
        if (empty($tableName)) {
            $this->json(['error' => 'Invalid table name.'], 422);
            return;
        }

        $tableId = $this->db->insert('dynamic_tables', [
            'name'           => $tableName,
            'display_name'   => $data['display_name'],
            'description'    => $data['description'] ?? '',
            'created_by'     => currentUser()['id'],
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->json(['success' => true, 'message' => 'Table created. Add columns next.', 'id' => $tableId]);
    }

    public function edit(int $id): void
    {
        $table = $this->db->fetchOne("SELECT * FROM dynamic_tables WHERE id = ?", [$id]);
        if (!$table) {
            $this->redirect('/admin/table-builder', 'error', 'Table not found.');
            return;
        }

        $columns = $this->db->fetchAll(
            "SELECT * FROM dynamic_table_columns WHERE table_id = ? ORDER BY display_order",
            [$id]
        );

        $this->view('admin/table_builder/edit', [
            'title'   => 'Edit Table: ' . $table['display_name'],
            'table'   => $table,
            'columns' => $columns,
        ]);
    }

    public function addColumn(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $tableId = (int)($_POST['table_id'] ?? 0);
        $fieldName = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['field_name'] ?? '');

        if (!$tableId || empty($fieldName)) {
            $this->json(['error' => 'Table ID and valid field name required.'], 422);
            return;
        }

        $maxOrder = $this->db->fetchOne(
            "SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM dynamic_table_columns WHERE table_id = ?",
            [$tableId]
        );

        $id = $this->db->insert('dynamic_table_columns', [
            'table_id'       => $tableId,
            'field_name'     => $fieldName,
            'label'          => $_POST['label'] ?? '',
            'data_type'      => $_POST['data_type'] ?? 'text',
            'required'       => isset($_POST['required']) ? 1 : 0,
            'unique'         => isset($_POST['unique']) ? 1 : 0,
            'default_value'  => $_POST['default_value'] ?? '',
            'display_order'  => $maxOrder['next_order'],
            'visible_roles'  => $_POST['visible_roles'] ?? '',
            'editable_roles' => $_POST['editable_roles'] ?? '',
        ]);

        $this->json(['success' => true, 'message' => 'Column added.', 'id' => $id]);
    }

    public function deleteColumn(int $id): void
    {
        $this->db->delete('dynamic_table_columns', 'id = ?', [$id]);
        $this->json(['success' => true, 'message' => 'Column deleted.']);
    }
}
