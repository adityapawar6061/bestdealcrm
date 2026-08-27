<?php
namespace Controllers;

class FormBuilderController extends BaseController
{
    private \Models\DynamicForm $formModel;

    public function __construct()
    {
        parent::__construct();
        $this->formModel = new \Models\DynamicForm();
    }

    public function index(): void
    {
        $forms = $this->formModel->getAll();

        $this->view('admin/form_builder/index', [
            'title' => 'Form Builder',
            'forms' => $forms,
        ]);
    }

    public function create(): void
    {
        $this->view('admin/form_builder/create', [
            'title' => 'Create Form',
        ]);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $data = $this->sanitize($_POST);
        $errors = $this->validate($data, [
            'name' => 'required',
            'code' => 'required',
        ]);

        if (!empty($errors)) {
            $this->json(['errors' => $errors], 422);
            return;
        }

        $id = $this->formModel->create([
            'name'            => $data['name'],
            'code'            => $data['code'],
            'description'     => $data['description'] ?? '',
            'assigned_role'   => $data['assigned_role'] ?? '',
            'related_table'   => $data['related_table'] ?? '',
            'workflow_stage'  => $data['workflow_stage'] ?? '',
        ]);

        // Set role access
        if (!empty($_POST['allowed_roles'])) {
            foreach ($_POST['allowed_roles'] as $roleId) {
                $this->db->insert('form_role_access', [
                    'form_id' => $id,
                    'role_id' => $roleId,
                ]);
            }
        }

        logActivity(currentUser()['id'], 'form_created', 'form', $id);

        $this->json(['success' => true, 'message' => 'Form created.', 'id' => $id]);
    }

    public function edit(int $id): void
    {
        $this->ensureFieldColumns();
        $form = $this->formModel->getFullForm($id);
        if (!$form) {
            $this->redirect('/admin/form-builder', 'error', 'Form not found.');
            return;
        }

        // Count hidden fields
        $hiddenCount = 0;
        foreach ($form['sections'] as $section) {
            $allFields = $this->db->fetchAll(
                "SELECT is_hidden FROM form_fields WHERE section_id = ?",
                [$section['id']]
            );
            foreach ($allFields as $f) {
                if (!empty($f['is_hidden'])) $hiddenCount++;
            }
        }

        $allRoles = $this->db->fetchAll("SELECT * FROM roles ORDER BY name");
        $formRoles = $this->db->fetchAll(
            "SELECT role_id FROM form_role_access WHERE form_id = ?",
            [$id]
        );
        $assignedRoleIds = array_column($formRoles, 'role_id');

        $this->view('admin/form_builder/edit', [
            'title'           => 'Edit Form: ' . $form['name'],
            'form'            => $form,
            'allRoles'        => $allRoles,
            'assignedRoleIds' => $assignedRoleIds,
            'hiddenFieldCount' => $hiddenCount,
        ]);
    }

    public function update(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $data = $this->sanitize($_POST);

        $this->formModel->update($id, [
            'name'           => $data['name'] ?? '',
            'description'    => $data['description'] ?? '',
            'workflow_stage' => $data['workflow_stage'] ?? '',
            'status'         => $data['status'] ?? 'active',
        ]);

        // Update role access
        $this->db->delete('form_role_access', 'form_id = ?', [$id]);
        if (!empty($_POST['allowed_roles'])) {
            foreach ($_POST['allowed_roles'] as $roleId) {
                $this->db->insert('form_role_access', [
                    'form_id' => $id,
                    'role_id' => $roleId,
                ]);
            }
        }

        logActivity(currentUser()['id'], 'form_updated', 'form', $id);

        $this->json(['success' => true, 'message' => 'Form updated.']);
    }

    /**
     * Add a section to form via AJAX
     */
    public function addSection(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $formId = (int)($_POST['form_id'] ?? 0);
        $name = $_POST['name'] ?? '';

        if (!$formId || empty($name)) {
            $this->json(['error' => 'Form ID and section name required.'], 422);
            return;
        }

        $maxOrder = $this->db->fetchOne(
            "SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM form_sections WHERE form_id = ?",
            [$formId]
        );

        $id = $this->formModel->createSection([
            'form_id'       => $formId,
            'name'          => $name,
            'display_order' => $maxOrder['next_order'],
        ]);

        $this->json(['success' => true, 'message' => 'Section added.', 'id' => $id]);
    }

    /**
     * Add a field to section via AJAX
     */
    public function addField(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $sectionId = (int)($_POST['section_id'] ?? 0);
        $fieldData = [
            'section_id'    => $sectionId,
            'field_name'    => $_POST['field_name'] ?? '',
            'label'         => $_POST['label'] ?? '',
            'type'          => $_POST['type'] ?? 'text',
            'required'      => isset($_POST['required']) ? 1 : 0,
            'placeholder'   => $_POST['placeholder'] ?? '',
            'default_value' => $_POST['default_value'] ?? '',
            'visible_roles' => $_POST['visible_roles'] ?? '',
            'editable_roles'=> $_POST['editable_roles'] ?? '',
        ];

        $this->ensureFieldColumns();
        $maxOrder = $this->db->fetchOne(
            "SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM form_fields WHERE section_id = ?",
            [$sectionId]
        );
        $fieldData['display_order'] = $maxOrder['next_order'];

        // Handle heading/subheading types
        $fieldType = $_POST['field_type'] ?? 'field';
        if (in_array($fieldData['type'], ['heading', 'subheading'])) {
            $fieldType = $fieldData['type'];
            // Generate field_name for headings if empty
            if (empty($fieldData['field_name'])) {
                $fieldData['field_name'] = 'heading_' . substr(bin2hex(random_bytes(4)), 0, 8);
            }
        }
        $fieldData['field_type'] = $fieldType;

        $id = $this->formModel->createField($fieldData);

        // Save options for dropdown/multi-select/radio
        if (in_array($fieldData['type'], ['dropdown', 'multi-select', 'radio']) && !empty($_POST['options'])) {
            $this->formModel->saveFieldOptions($id, $_POST['options']);
        }

        $this->json(['success' => true, 'message' => 'Field added.', 'id' => $id]);
    }

    /**
     * Update a field via AJAX
     */
    public function updateField(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        try {
            $data = [];
            if (isset($_POST['label'])) $data['label'] = $_POST['label'];
            if (isset($_POST['type'])) $data['type'] = $_POST['type'];
            if (isset($_POST['required'])) $data['required'] = (int)$_POST['required'];
            if (isset($_POST['placeholder'])) $data['placeholder'] = $_POST['placeholder'];
            if (isset($_POST['default_value'])) $data['default_value'] = $_POST['default_value'];

            if (empty($data)) {
                $this->json(['error' => 'No data to update.'], 400);
                return;
            }

            $this->formModel->updateField($id, $data);
            logActivity(currentUser()['id'], 'field_updated', 'form_field', $id);
            $this->json(['success' => true, 'message' => 'Field updated.']);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Soft delete a field (hide it, keep data)
     */
    public function deleteField(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            // Ensure is_hidden column exists
            $this->ensureFieldColumns();
            $this->db->update('form_fields', ['is_hidden' => 1], 'id = ?', [$id]);
            $this->json(['success' => true, 'message' => 'Field hidden (soft deleted).']);
        }
    }

    /**
     * Hard delete - permanently remove a soft-deleted field
     */
    public function hardDeleteField(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            if ($password !== '12345678') {
                $this->json(['error' => 'Incorrect password.'], 403);
                return;
            }
            $this->formModel->deleteField($id);
            $this->json(['success' => true, 'message' => 'Field permanently deleted.']);
        }
    }

    /**
     * Restore a soft-deleted field
     */
    public function restoreField(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->ensureFieldColumns();
            $this->db->update('form_fields', ['is_hidden' => 0], 'id = ?', [$id]);
            $this->json(['success' => true, 'message' => 'Field restored.']);
        }
    }

    /**
     * Get hidden (soft-deleted) fields for hard delete
     */
    public function hiddenFields(int $formId): void
    {
        $this->ensureFieldColumns();
        $form = $this->formModel->getFullForm($formId);
        $hiddenFields = [];
        if ($form) {
            foreach ($form['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    if (!empty($field['is_hidden'])) {
                        $hiddenFields[] = [
                            'id' => $field['id'],
                            'section' => $section['name'],
                            'field_name' => $field['field_name'],
                            'label' => $field['label'],
                            'type' => $field['type'],
                        ];
                    }
                }
            }
        }
        $this->json(['success' => true, 'fields' => $hiddenFields]);
    }

    /**
     * Ensure is_hidden column exists in form_fields
     */
    private function ensureFieldColumns(): void
    {
        try {
            $cols = $this->db->fetchAll(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_fields'"
            );
            $colNames = array_column($cols, 'COLUMN_NAME');
            if (!in_array('is_hidden', $colNames)) {
                $this->db->query("ALTER TABLE `form_fields` ADD COLUMN `is_hidden` TINYINT(1) DEFAULT 0");
            }
            // Also ensure field_type column for heading/subheading
            if (!in_array('field_type', $colNames)) {
                $this->db->query("ALTER TABLE `form_fields` ADD COLUMN `field_type` VARCHAR(50) DEFAULT 'field'");
            }
        } catch (\Throwable $e) {
            // Ignore - columns may already exist
        }
    }

    /**
     * Delete form with password confirmation
     */
    public function deleteWithPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $formId = (int)($_POST['form_id'] ?? 0);
        $password = $_POST['confirm_password'] ?? '';

        if ($password !== '12345678') {
            $this->json(['error' => 'Incorrect password.'], 403);
            return;
        }

        if (!$formId) {
            $this->json(['error' => 'Invalid form ID.'], 400);
            return;
        }

        $this->formModel->deleteForm($formId);
        logActivity(currentUser()['id'], 'form_deleted', 'form', $formId);

        $this->json(['success' => true, 'message' => 'Form deleted permanently.']);
    }

    /**
     * Get field options via AJAX
     */
    public function getFieldOptions(int $id): void
    {
        $options = $this->db->fetchAll(
            "SELECT * FROM form_field_options WHERE field_id = ? ORDER BY display_order",
            [$id]
        );
        $this->json(['success' => true, 'options' => $options]);
    }

    /**
     * Save field options via AJAX
     */
    public function saveFieldOptions(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $options = $_POST['options'] ?? [];
        $this->formModel->saveFieldOptions($id, $options);
        $this->json(['success' => true, 'message' => 'Options saved.']);
    }
}
