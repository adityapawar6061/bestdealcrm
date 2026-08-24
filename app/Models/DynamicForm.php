<?php
namespace Models;

class DynamicForm
{
    private Database $db;

    public function __construct()
    {
        $this->db = \Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM forms WHERE id = ?", [$id]);
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM forms ORDER BY name");
    }

    public function create(array $data): string
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? 'active';
        return $this->db->insert('forms', $data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('forms', $data, 'id = ?', [$id]);
    }

    public function delete(int $id): int
    {
        return $this->db->delete('forms', 'id = ?', [$id]);
    }

    /**
     * Get form with sections and fields
     */
    public function getFullForm(int $formId): ?array
    {
        $form = $this->findById($formId);
        if (!$form) return null;

        $form['sections'] = $this->db->fetchAll(
            "SELECT * FROM form_sections WHERE form_id = ? ORDER BY display_order",
            [$formId]
        );

        foreach ($form['sections'] as &$section) {
            $section['fields'] = $this->db->fetchAll(
                "SELECT * FROM form_fields WHERE section_id = ? ORDER BY display_order",
                [$section['id']]
            );

            foreach ($section['fields'] as &$field) {
                if (in_array($field['type'], ['dropdown', 'multi-select', 'radio'])) {
                    $field['options'] = $this->db->fetchAll(
                        "SELECT * FROM form_field_options WHERE field_id = ? ORDER BY display_order",
                        [$field['id']]
                    );
                } else {
                    $field['options'] = [];
                }
            }
        }

        return $form;
    }

    /**
     * Create a form section
     */
    public function createSection(array $data): string
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('form_sections', $data);
    }

    /**
     * Create a form field
     */
    public function createField(array $data): string
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('form_fields', $data);
    }

    /**
     * Update a form field
     */
    public function updateField(int $fieldId, array $data): int
    {
        return $this->db->update('form_fields', $data, 'id = ?', [$fieldId]);
    }

    /**
     * Delete a form field
     */
    public function deleteField(int $fieldId): int
    {
        $this->db->delete('form_field_options', 'field_id = ?', [$fieldId]);
        return $this->db->delete('form_fields', 'id = ?', [$fieldId]);
    }

    /**
     * Save field options
     */
    public function saveFieldOptions(int $fieldId, array $options): void
    {
        $this->db->delete('form_field_options', 'field_id = ?', [$fieldId]);
        foreach ($options as $i => $option) {
            $this->db->insert('form_field_options', [
                'field_id'      => $fieldId,
                'label'         => $option['label'] ?? $option,
                'value'         => $option['value'] ?? $option,
                'display_order' => $i + 1,
            ]);
        }
    }

    /**
     * Submit form data
     */
    public function submitForm(int $formId, int $leadId, int $submittedBy, array $values): string
    {
        $submissionId = $this->db->insert('form_submissions', [
            'form_id'      => $formId,
            'lead_id'      => $leadId,
            'submitted_by' => $submittedBy,
            'status'       => 'submitted',
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        foreach ($values as $fieldId => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $this->db->insert('form_submission_values', [
                'submission_id' => $submissionId,
                'field_id'      => $fieldId,
                'value'         => $value,
            ]);
        }

        return $submissionId;
    }

    /**
     * Get form submission with values
     */
    public function getSubmission(int $submissionId): ?array
    {
        $submission = $this->db->fetchOne(
            "SELECT fs.*, f.name as form_name 
             FROM form_submissions fs 
             JOIN forms f ON fs.form_id = f.id 
             WHERE fs.id = ?",
            [$submissionId]
        );

        if (!$submission) return null;

        $submission['values'] = $this->db->fetchAll(
            "SELECT fsv.*, ff.field_name, ff.label, ff.type 
             FROM form_submission_values fsv 
             JOIN form_fields ff ON fsv.field_id = ff.id 
             WHERE fsv.submission_id = ?",
            [$submissionId]
        );

        return $submission;
    }

    /**
     * Get submissions for a lead
     */
    public function getSubmissionsForLead(int $leadId): array
    {
        return $this->db->fetchAll(
            "SELECT fs.*, f.name as form_name, u.name as submitted_by_name 
             FROM form_submissions fs 
             JOIN forms f ON fs.form_id = f.id 
             LEFT JOIN users u ON fs.submitted_by = u.id 
             WHERE fs.lead_id = ? 
             ORDER BY fs.created_at DESC",
            [$leadId]
        );
    }

    /**
     * Update a submission
     */
    public function updateSubmission(int $submissionId, array $values): void
    {
        $this->db->update('form_submissions', [
            'status'     => 'updated',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$submissionId]);

        foreach ($values as $fieldId => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            
            $existing = $this->db->fetchOne(
                "SELECT id FROM form_submission_values WHERE submission_id = ? AND field_id = ?",
                [$submissionId, $fieldId]
            );

            if ($existing) {
                $this->db->update('form_submission_values', [
                    'value' => $value,
                ], 'id = ?', [$existing['id']]);
            } else {
                $this->db->insert('form_submission_values', [
                    'submission_id' => $submissionId,
                    'field_id'      => $fieldId,
                    'value'         => $value,
                ]);
            }
        }
    }

    /**
     * Get forms by role
     */
    public function getFormsByRole(string $roleName): array
    {
        return $this->db->fetchAll(
            "SELECT f.* FROM forms f 
             JOIN form_role_access fra ON f.id = fra.form_id 
             JOIN roles r ON fra.role_id = r.id 
             WHERE r.name = ? AND f.status = 'active' 
             ORDER BY f.name",
            [$roleName]
        );
    }

    /**
     * Get forms by workflow stage
     */
    public function getFormsByStage(string $stage): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM forms WHERE workflow_stage = ? AND status = 'active' ORDER BY name",
            [$stage]
        );
    }
}
