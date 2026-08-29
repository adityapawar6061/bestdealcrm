<?php
/**
 * Shared form renderer helpers.
 * Include this file to get access to renderReadOnlyField(), getFieldColClass(), renderFormSection().
 * 
 * Expects:
 *   - BASE_URL constant
 * 
 * Available after include:
 *   - renderReadOnlyField($field, $value): string — renders a single field in read-only mode
 *   - getFieldColClass($field, $sectionLayout): string — returns Bootstrap column class
 *   - renderFormSection($section, $values, $readOnly, $formPrefix): string — renders a full section
 *   - renderUploadedDocuments($documents): string — renders document thumbnails
 */

if (!function_exists('getFieldColClass')) {
    function getFieldColClass($field, $sectionLayout = 2) {
        $type = $field['type'] ?? 'text';
        $fieldType = $field['field_type'] ?? 'field';
        if ($fieldType === 'heading' || $fieldType === 'subheading') return 'col-12';
        if ($type === 'textarea') return 'col-12';
        switch ((int)$sectionLayout) {
            case 1:  return 'col-md-12';
            case 3:  return 'col-md-4';
            default: return 'col-md-6';
        }
    }
}

if (!function_exists('renderReadOnlyField')) {
    function renderReadOnlyField($field, $value = '') {
        $type = $field['type'] ?? 'text';
        $label = htmlspecialchars($field['label'] ?? '');
        $fieldType = $field['field_type'] ?? 'field';
        $val = htmlspecialchars($value ?? '');
        $isEmpty = empty($val) || $val === '0000-00-00 00:00:00' || $val === '0000-00-00';

        $html = '<label class="form-label small fw-semibold">' . $label;
        if (!empty($field['required'])) $html .= ' <span class="text-danger">*</span>';
        $html .= '</label>';

        // Headings and subheadings
        if ($fieldType === 'heading') {
            return '<h5 class="text-primary fw-bold border-bottom pb-1 mt-3 mb-2">' . $label . '</h5>';
        }
        if ($fieldType === 'subheading') {
            return '<h6 class="text-dark fw-semibold mt-2 mb-1" style="font-size:0.95rem">' . $label . '</h6>';
        }

        // Helper: dash display for empty values (never put raw HTML in value attributes)
        $dashHtml = '<span class="text-muted">—</span>';

        switch ($type) {
            case 'textarea':
                $html .= '<div class="form-control form-control-sm bg-light" style="min-height:60px;white-space:pre-wrap">' . ($isEmpty ? $dashHtml : $val) . '</div>';
                return $html;

            case 'dropdown':
            case 'multi-select':
                // Convert value to label if options available
                $displayVal = $val;
                if (!empty($field['options']) && $val) {
                    foreach ($field['options'] as $opt) {
                        if (($opt['value'] ?? '') === $value) {
                            $displayVal = htmlspecialchars($opt['label'] ?? $val);
                            break;
                        }
                    }
                }
                if ($isEmpty || !$displayVal) {
                    $html .= '<div class="form-control form-control-sm bg-light">' . $dashHtml . '</div>';
                } else {
                    $html .= '<input type="text" class="form-control form-control-sm bg-light" value="' . $displayVal . '" readonly>';
                }
                return $html;

            case 'radio':
                $options = $field['options'] ?? [];
                $html .= '<div class="d-flex gap-3">';
                foreach ($options as $opt) {
                    $checked = ($value === $opt['value']);
                    $html .= '<div class="form-check"><input class="form-check-input" type="radio" disabled ' . ($checked ? 'checked' : '') . '><label class="form-check-label small">' . htmlspecialchars($opt['label']) . '</label></div>';
                }
                $html .= '</div>';
                return $html;

            case 'checkbox':
                $checked = !empty($value);
                $html = '<div class="form-check mt-1"><input class="form-check-input" type="checkbox" disabled ' . ($checked ? 'checked' : '') . '><label class="form-check-label small fw-semibold">' . $label . '</label></div>';
                return $html;

            case 'file':
            case 'image':
                if ($isEmpty) {
                    $html .= '<div class="form-control form-control-sm bg-light text-muted">—</div>';
                } else {
                    $uploadDir = BASE_URL . '/public/uploads/documents/' . ($field['_lead_id'] ?? '') . '/';
                    $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                    $html .= '<div class="form-control form-control-sm bg-light">';
                    $html .= '<a href="' . $uploadDir . $val . '" target="_blank" class="text-decoration-none">';
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $html .= '<img src="' . $uploadDir . $val . '" style="max-height:60px;border-radius:4px" class="me-1">';
                    } elseif ($ext === 'pdf') {
                        $html .= '<i class="bi bi-file-pdf text-danger me-1"></i>';
                    } else {
                        $html .= '<i class="bi bi-file-earmark me-1"></i>';
                    }
                    $html .= htmlspecialchars($value) . '</a></div>';
                }
                return $html;

            case 'date':
                if ($isEmpty) {
                    $html .= '<div class="form-control form-control-sm bg-light">' . $dashHtml . '</div>';
                } else {
                    $html .= '<input type="text" class="form-control form-control-sm bg-light" value="' . $val . '" readonly>';
                }
                return $html;

            case 'number':
            case 'decimal':
                if ($isEmpty) {
                    $html .= '<div class="form-control form-control-sm bg-light">' . $dashHtml . '</div>';
                } else {
                    $html .= '<input type="text" class="form-control form-control-sm bg-light" value="' . $val . '" readonly>';
                }
                return $html;

            default:
                if ($isEmpty) {
                    $html .= '<div class="form-control form-control-sm bg-light">' . $dashHtml . '</div>';
                } else {
                    $html .= '<input type="text" class="form-control form-control-sm bg-light" value="' . $val . '" readonly>';
                }
                return $html;
        }
    }
}

/**
 * Render a form section in read-only mode (like the agent form looks in fill form)
 * @param array $section — from getFullForm() 
 * @param array $values — keyed by field_id => value
 * @param int $leadId — for file upload paths
 * @param string $submittedByName — who submitted this form
 * @param string $submittedByRole — agent, login_agent, etc.
 */
if (!function_exists('renderFormSectionReadonly')) {
    function renderFormSectionReadonly($section, $values = [], $leadId = 0, $submittedByName = '', $submittedByRole = '') {
        $sectionLayout = $section['column_layout'] ?? 2;
        $sectionName = $section['name'] ?? '';
        $isAdminSection = stripos($sectionName, 'admin') !== false;
        
        echo '<div class="mb-4">';
        echo '<h6 class="small fw-bold mb-2 border-bottom pb-1">';
        echo '<i class="bi bi-card-list me-1"></i> ' . htmlspecialchars($sectionName);
        echo ' <small class="fw-normal text-muted">(' . (int)$sectionLayout . ' column' . ($sectionLayout > 1 ? 's' : '') . ')</small>';
        if ($isAdminSection) echo ' <span class="badge bg-secondary ms-1" style="font-size:0.6rem">READ ONLY</span>';
        echo '</h6>';
        echo '<div class="row g-3">';
        foreach ($section['fields'] as $field) {
            if (!empty($field['is_hidden'])) continue;
            $value = $values[$field['id']] ?? $field['default_value'] ?? '';
            $fieldWithLead = $field;
            $fieldWithLead['_lead_id'] = $leadId;
            echo '<div class="' . getFieldColClass($field, $sectionLayout) . '">';
            echo renderReadOnlyField($fieldWithLead, $value);
            echo '</div>';
        }
        echo '</div></div>';
    }
}

/**
 * Render uploaded documents as a grid with thumbnails
 */
if (!function_exists('renderUploadedDocuments')) {
    function renderUploadedDocuments($documents) {
        if (empty($documents)) return;
        echo '<h6 class="fw-bold mb-3"><i class="bi bi-paperclip me-1"></i> Uploaded Documents</h6>';
        echo '<div class="row g-2">';
        foreach ($documents as $doc) {
            $ext = strtolower(pathinfo($doc['original_name'] ?? '', PATHINFO_EXTENSION));
            $filePath = BASE_URL . '/public/uploads/documents/' . ($doc['lead_id'] ?? '') . '/' . htmlspecialchars($doc['filename']);
            echo '<div class="col-md-3 col-6">';
            echo '<div class="border rounded p-2 text-center">';
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                echo '<a href="' . $filePath . '" target="_blank"><img src="' . $filePath . '" alt="' . htmlspecialchars($doc['original_name'] ?? '') . '" class="img-fluid rounded" style="max-height:80px"></a>';
            } elseif ($ext === 'pdf') {
                echo '<a href="' . $filePath . '" target="_blank" class="text-decoration-none"><i class="bi bi-file-pdf text-danger" style="font-size:2rem"></i></a>';
            } else {
                echo '<a href="' . $filePath . '" target="_blank" class="text-decoration-none"><i class="bi bi-file-earmark text-primary" style="font-size:2rem"></i></a>';
            }
            echo '<div class="mt-1">';
            echo '<small class="text-muted d-block text-truncate" style="max-width:140px" title="' . htmlspecialchars($doc['original_name'] ?? '') . '">' . htmlspecialchars($doc['original_name'] ?? '') . '</small>';
            if (!empty($doc['uploaded_by_name'])) {
                echo '<small class="text-muted">by ' . htmlspecialchars($doc['uploaded_by_name']) . '</small>';
            }
            echo '</div></div></div>';
        }
        echo '</div>';
    }
}
?>
