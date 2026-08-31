<?php
namespace Models;

class DynamicForm
{
    private $db;

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
        $data['created_at'] = nowIST();
        $data['status'] = $data['status'] ?? 'active';
        return $this->db->insert('forms', $data);
    }

    public function update(int $id, array $data): int
    {
        $data['updated_at'] = nowIST();
        return $this->db->update('forms', $data, 'id = ?', [$id]);
    }

    public function delete(int $id): int
    {
        return $this->db->delete('forms', 'id = ?', [$id]);
    }

    /**
     * Delete form with all sections, fields, options, and role access
     */
    public function deleteForm(int $formId): void
    {
        // Get all sections
        $sections = $this->db->fetchAll(
            "SELECT id FROM form_sections WHERE form_id = ?",
            [$formId]
        );

        foreach ($sections as $section) {
            // Delete field options and submission values for fields in this section
            $fields = $this->db->fetchAll(
                "SELECT id FROM form_fields WHERE section_id = ?",
                [$section['id']]
            );
            $fieldIds = array_column($fields, 'id');
            if (!empty($fieldIds)) {
                $placeholders = implode(',', array_fill(0, count($fieldIds), '?'));
                $this->db->delete('form_field_options', "field_id IN ({$placeholders})", $fieldIds);
                $this->db->query("DELETE FROM form_submission_values WHERE field_id IN ({$placeholders})", $fieldIds);
            }
            $this->db->delete('form_fields', 'section_id = ?', [$section['id']]);
        }

        // Delete submissions and their values for this form
        $submissions = $this->db->fetchAll(
            "SELECT id FROM form_submissions WHERE form_id = ?",
            [$formId]
        );
        $subIds = array_column($submissions, 'id');
        if (!empty($subIds)) {
            $placeholders = implode(',', array_fill(0, count($subIds), '?'));
            $this->db->query("DELETE FROM form_submission_values WHERE submission_id IN ({$placeholders})", $subIds);
            $this->db->delete('form_submissions', 'form_id = ?', [$formId]);
        }

        $this->db->delete('form_sections', 'form_id = ?', [$formId]);
        $this->db->delete('form_role_access', 'form_id = ?', [$formId]);
        $this->db->delete('forms', 'id = ?', [$formId]);
    }

    /**
     * Get form with sections and fields
     */
    public function getFullForm(int $formId, bool $includeHidden = false): ?array
    {
        $form = $this->findById($formId);
        if (!$form) return null;

        // Ensure is_hidden column exists
        $this->ensureHiddenColumn();

        $form['sections'] =            $this->db->fetchAll(
            "SELECT * FROM form_sections WHERE form_id = ? ORDER BY display_order",
            [$formId]
        );
        // Ensure column_layout exists
        try {
            $colCheck = $this->db->fetchOne("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_sections' AND COLUMN_NAME = 'column_layout'");
            if (!$colCheck) {
                $this->db->query("ALTER TABLE `form_sections` ADD COLUMN `column_layout` INT DEFAULT 1");
            }
        } catch (\Throwable $e) {}

        foreach ($form['sections'] as &$section) {
            $hiddenFilter = $includeHidden ? '' : ' AND (f.is_hidden IS NULL OR f.is_hidden = 0)';
            $section['fields'] = $this->db->fetchAll(
                "SELECT f.* FROM form_fields f WHERE f.section_id = ? {$hiddenFilter} ORDER BY f.display_order",
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
        $data['created_at'] = nowIST();
        return $this->db->insert('form_sections', $data);
    }

    /**
     * Ensure is_hidden column exists in form_fields
     */
    private function ensureHiddenColumn(): void
    {
        static $done = false;
        if ($done) return;
        try {
            $colCheck = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_fields' AND COLUMN_NAME = 'is_hidden'"
            );
            if ($colCheck && (int)$colCheck['cnt'] === 0) {
                $this->db->query("ALTER TABLE `form_fields` ADD COLUMN `is_hidden` TINYINT(1) DEFAULT 0");
            }
            $colCheck2 = $this->db->fetchOne(
                "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_fields' AND COLUMN_NAME = 'field_type'"
            );
            if ($colCheck2 && (int)$colCheck2['cnt'] === 0) {
                $this->db->query("ALTER TABLE `form_fields` ADD COLUMN `field_type` VARCHAR(50) DEFAULT 'field'");
            }
        } catch (\Throwable $e) {
            // Column might already exist or permissions issue
        }
        $done = true;
    }

    /**
     * Create a form field
     */
    public function createField(array $data): string
    {
        $data['created_at'] = nowIST();
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
            'created_at'   => nowIST(),
            'updated_at'   => nowIST(),
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
            "SELECT fs.*, f.name as form_name, u.name as submitted_by_name, r.name as role_name 
             FROM form_submissions fs 
             JOIN forms f ON fs.form_id = f.id 
             LEFT JOIN users u ON fs.submitted_by = u.id 
             LEFT JOIN roles r ON u.role_id = r.id 
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
            'updated_at' => nowIST(),
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
     * Process file uploads from form submissions
     * Handles files uploaded via <input type="file" name="form_data[field_id]">
     */
    public function processFileUploads(int $leadId, int $uploadedBy, array &$values): void
    {
        if (empty($_FILES['form_data'])) {
            error_log("processFileUploads: no form_data in FILES");
            return;
        }

        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        $uploadDir = ROOT_PATH . '/public/uploads/documents/' . $leadId . '/';

        // Create directory if needed
        if (!is_dir($uploadDir)) {
            $created = @mkdir($uploadDir, 0755, true);
            if (!$created) {
                error_log("processFileUploads: failed to create dir {$uploadDir} — check permissions");
                return;
            }
        }

        $files = $_FILES['form_data'];
        $processed = 0;
        foreach ($files['error'] as $fieldId => $error) {
            if ($error !== UPLOAD_ERR_OK || empty($files['name'][$fieldId])) continue;

            $file = [
                'name'     => $files['name'][$fieldId],
                'type'     => $files['type'][$fieldId],
                'tmp_name' => $files['tmp_name'][$fieldId],
                'error'    => $files['error'][$fieldId],
                'size'     => $files['size'][$fieldId],
            ];

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) continue;

            $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $safeFilename = $safeFilename . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $safeFilename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Determine mime type
                $mimeMap = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';

                // Insert into documents table
                $docId = $this->db->insert('documents', [
                    'lead_id'        => $leadId,
                    'uploaded_by'    => $uploadedBy,
                    'filename'       => $safeFilename,
                    'original_name'  => $file['name'],
                    'mime_type'      => $mimeType,
                    'file_size'      => $file['size'],
                    'document_type'  => 'form_upload',
                    'created_at'     => nowIST(),
                ]);

                // Store reference in submission values
                $values[$fieldId] = json_encode([
                    'doc_id'       => (int)$docId,
                    'filename'     => $safeFilename,
                    'original'     => $file['name'],
                    'mime_type'    => $mimeType,
                    'file_size'    => $file['size'],
                ]);

                logActivity($uploadedBy, 'form_file_uploaded', 'document', (int)$docId, null, $file['name']);
                $processed++;
            } else {
                error_log("processFileUploads: move_uploaded_file failed for {$file['name']} -> {$destination}");
            }
        }
        if ($processed > 0) {
            error_log("processFileUploads: processed {$processed} files for lead #{$leadId}");
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
