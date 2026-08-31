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

        // Count hidden fields AND fetch their data directly (no AJAX needed)
        $hiddenCount = 0;
        $hiddenFieldsData = [];
        foreach ($form['sections'] as $section) {
            $allFields = $this->db->fetchAll(
                "SELECT is_hidden FROM form_fields WHERE section_id = ?",
                [$section['id']]
            );
            foreach ($allFields as $f) {
                if (!empty($f['is_hidden'])) $hiddenCount++;
            }
        }

        // Fetch hidden field details directly (same query that works in debug page)
        if ($hiddenCount > 0) {
            try {
                $hiddenRows = $this->db->fetchAll(
                    "SELECT f.id, f.field_name, f.label, f.type, s.name as section_name
                     FROM form_fields f
                     JOIN form_sections s ON f.section_id = s.id
                     WHERE s.form_id = ? AND f.is_hidden = 1
                     ORDER BY s.display_order, f.display_order",
                    [$id]
                );
                foreach ($hiddenRows as $row) {
                    $hiddenFieldsData[] = [
                        'id' => (int)$row['id'],
                        'section' => $row['section_name'],
                        'field_name' => $row['field_name'],
                        'label' => $row['label'],
                        'type' => $row['type'],
                    ];
                }
            } catch (\Throwable $e) {
                error_log('edit hiddenFields error: ' . $e->getMessage());
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
            'hiddenFieldsData' => $hiddenFieldsData,
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

        $fieldType = $_POST['field_type'] ?? 'field';
        if (in_array($fieldData['type'], ['heading', 'subheading'])) {
            $fieldType = $fieldData['type'];
            if (empty($fieldData['field_name'])) {
                $fieldData['field_name'] = 'heading_' . substr(bin2hex(random_bytes(4)), 0, 8);
            }
        }
        $fieldData['field_type'] = $fieldType;

        $id = $this->formModel->createField($fieldData);

        if (in_array($fieldData['type'], ['dropdown', 'multi-select', 'radio']) && !empty($_POST['options'])) {
            $this->formModel->saveFieldOptions($id, $_POST['options']);
        }

        $this->json(['success' => true, 'message' => 'Field added.', 'id' => $id]);
    }

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

    public function deleteField(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $this->ensureFieldColumns();
            $this->db->update('form_fields', ['is_hidden' => 1], 'id = ?', [$id]);
            $this->json(['success' => true, 'message' => 'Field hidden (soft deleted).']);
        }
    }

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

    public function restoreField(int $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->ensureFieldColumns();
            $this->db->update('form_fields', ['is_hidden' => 0], 'id = ?', [$id]);
            $this->json(['success' => true, 'message' => 'Field restored.']);
        }
    }

    public function hiddenFields(int $formId): void
    {
        $this->ensureFieldColumns();

        $hiddenFields = [];
        try {
            $rows = $this->db->fetchAll(
                "SELECT f.id, f.field_name, f.label, f.type, s.name as section_name
                 FROM form_fields f
                 JOIN form_sections s ON f.section_id = s.id
                 WHERE s.form_id = ? AND f.is_hidden = 1
                 ORDER BY s.display_order, f.display_order",
                [$formId]
            );
            foreach ($rows as $row) {
                $hiddenFields[] = [
                    'id' => (int)$row['id'],
                    'section' => $row['section_name'],
                    'field_name' => $row['field_name'],
                    'label' => $row['label'],
                    'type' => $row['type'],
                ];
            }
        } catch (\Throwable $e) {
            error_log('hiddenFields error: ' . $e->getMessage());
        }
        $this->json(['success' => true, 'fields' => $hiddenFields]);
    }

    private function ensureFieldColumns(): void
    {
        try {
            // form_fields columns
            $cols = $this->db->fetchAll(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_fields'"
            );
            $colNames = array_column($cols, 'COLUMN_NAME');
            if (!in_array('is_hidden', $colNames)) {
                $this->db->query("ALTER TABLE `form_fields` ADD COLUMN `is_hidden` TINYINT(1) DEFAULT 0");
            }
            if (!in_array('field_type', $colNames)) {
                $this->db->query("ALTER TABLE `form_fields` ADD COLUMN `field_type` VARCHAR(50) DEFAULT 'field'");
            }
            // form_sections columns
            $secCols = $this->db->fetchAll(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_sections'"
            );
            $secColNames = array_column($secCols, 'COLUMN_NAME');
            if (!in_array('column_layout', $secColNames)) {
                $this->db->query("ALTER TABLE `form_sections` ADD COLUMN `column_layout` INT DEFAULT 1");
            }
        } catch (\Throwable $e) {
            // Ignore
        }
    }

    /**
     * Save section order (drag-drop)
     */
    public function saveSectionOrder(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $sectionIds = $_POST['section_ids'] ?? [];
        if (empty($sectionIds)) {
            $this->json(['error' => 'No sections provided.'], 400);
            return;
        }

        try {
            foreach ($sectionIds as $index => $sectionId) {
                $this->db->update('form_sections', [
                    'display_order' => $index + 1,
                ], 'id = ?', [(int)$sectionId]);
            }
            $this->json(['success' => true, 'message' => 'Section order saved.']);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Save section column layout (1, 2, or 3 columns)
     */
    public function saveSectionLayout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $sectionId = (int)($_POST['section_id'] ?? 0);
        $layout = (int)($_POST['column_layout'] ?? 1);

        if (!$sectionId || $layout < 1 || $layout > 3) {
            $this->json(['error' => 'Invalid data.'], 400);
            return;
        }

        $this->db->update('form_sections', ['column_layout' => $layout], 'id = ?', [$sectionId]);
        $this->json(['success' => true, 'message' => 'Layout saved.']);
    }

    /**
     * Save field order within a section (drag-drop)
     */
    public function saveFieldOrder(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $fieldIds = $_POST['field_ids'] ?? [];
        if (empty($fieldIds)) {
            $this->json(['error' => 'No fields provided.'], 400);
            return;
        }

        try {
            foreach ($fieldIds as $index => $fieldId) {
                $this->db->update('form_fields', [
                    'display_order' => $index + 1,
                ], 'id = ?', [(int)$fieldId]);
            }
            $this->json(['success' => true, 'message' => 'Field order saved.']);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function deleteSection(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid request.'], 405);
            return;
        }

        $password = $_POST['password'] ?? '';
        if ($password !== '12345678') {
            $this->json(['error' => 'Incorrect password.'], 403);
            return;
        }

        $sectionId = (int)($_POST['section_id'] ?? 0);
        if (!$sectionId) {
            $this->json(['error' => 'Invalid section ID.'], 400);
            return;
        }

        try {
            $fields = $this->db->fetchAll(
                "SELECT id FROM form_fields WHERE section_id = ?",
                [$sectionId]
            );
            $fieldIds = array_column($fields, 'id');

            if (!empty($fieldIds)) {
                $placeholders = implode(',', array_fill(0, count($fieldIds), '?'));
                $this->db->delete('form_field_options', "field_id IN ({$placeholders})", $fieldIds);
                $this->db->query("DELETE FROM form_submission_values WHERE field_id IN ({$placeholders})", $fieldIds);
            }
            $this->db->delete('form_fields', 'section_id = ?', [$sectionId]);
            $this->db->delete('form_sections', 'id = ?', [$sectionId]);

            logActivity(currentUser()['id'], 'section_deleted', 'form_section', $sectionId);
            $this->json(['success' => true, 'message' => 'Section and all its fields deleted permanently.']);
        } catch (\Throwable $e) {
            error_log('deleteSection error: ' . $e->getMessage());
            $this->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

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

        try {
            $this->formModel->deleteForm($formId);
            logActivity(currentUser()['id'], 'form_deleted', 'form', $formId);
            $this->json(['success' => true, 'message' => 'Form deleted permanently.']);
        } catch (\Throwable $e) {
            error_log('deleteWithPassword error: ' . $e->getMessage());
            $this->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    public function getFieldOptions(int $id): void
    {
        $options = $this->db->fetchAll(
            "SELECT * FROM form_field_options WHERE field_id = ? ORDER BY display_order",
            [$id]
        );
        $this->json(['success' => true, 'options' => $options]);
    }

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
