<?php
/**
 * Shared partial: renders stacked forms for admin review pages
 * Expects: $lead, $agentForm, $agentFormValues, $agentName,
 *          and optionally $preLoginForm, $preLoginFormValues, $preLoginName,
 *          $postLoginForm, $postLoginFormValues, $postLoginName,
 *          $uwForm, $uwFormValues, $uwName, $documents, $allSubmissions
 */
require __DIR__ . '/../partials/form_renderer.php';

$forms = [];
if (!empty($agentForm)) $forms[] = ['form'=>$agentForm, 'values'=>$agentFormValues, 'name'=>$agentName??'', 'label'=>'Agent Form', 'color'=>'#6c757d', 'badge'=>'bg-secondary', 'readonly'=>true];
if (!empty($preLoginForm)) $forms[] = ['form'=>$preLoginForm, 'values'=>$preLoginFormValues, 'name'=>$preLoginName??'', 'label'=>'Pre-Login Checklist', 'color'=>'#0dcaf0', 'badge'=>'bg-info', 'readonly'=>true];
if (!empty($postLoginForm)) $forms[] = ['form'=>$postLoginForm, 'values'=>$postLoginFormValues, 'name'=>$postLoginName??'', 'label'=>'Post-Login Form', 'color'=>'#198754', 'badge'=>'bg-success', 'readonly'=>true];
if (!empty($uwForm)) $forms[] = ['form'=>$uwForm, 'values'=>$uwFormValues, 'name'=>$uwName??'', 'label'=>'Underwriting Form', 'color'=>'#ffc107', 'badge'=>'bg-warning text-dark', 'readonly'=>true];
?>

<!-- Forms -->
<?php foreach ($forms as $i => $f): ?>
<div class="mb-4" style="border-left:4px solid <?= $f['color'] ?>;padding-left:0">
    <div class="table-container" style="border-radius:0 12px 12px 0">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color:<?= $f['color'] ?>">
                <i class="bi bi-<?= $i===0?'person-check':($i===1?'clipboard-check':($i===2?'clipboard2-check':'file-earmark-check')) ?> me-2"></i>
                <?= $i+1 ?>. <?= $f['label'] ?>
                <?php if (!empty($f['name'])): ?>
                    <small class="fw-normal text-muted ms-2">by <?= htmlspecialchars($f['name']) ?></small>
                <?php endif; ?>
            </h5>
            <span class="badge <?= $f['badge'] ?>">Read Only</span>
        </div>
        <?php foreach ($f['form']['sections'] as $section): ?>
            <?php renderFormSectionReadonly($section, $f['values'], $lead['id'], $f['name'], $i===0?'agent':''); ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($forms)): ?>
<div class="table-container mb-4">
    <div class="alert alert-light mb-0">No form submissions found.</div>
</div>
<?php endif; ?>

<!-- Documents -->
<?php if (!empty($documents)): ?>
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-paperclip me-1"></i> Uploaded Documents</h6>
    <div class="row g-2">
        <?php foreach ($documents as $doc): ?>
        <div class="col-md-4 col-sm-6">
            <div class="d-flex align-items-center gap-2 p-2 bg-light rounded small">
                <?php if (str_starts_with($doc['mime_type'] ?? '', 'image/')): ?>
                    <i class="bi bi-image text-primary"></i>
                <?php elseif (($doc['mime_type'] ?? '') === 'application/pdf'): ?>
                    <i class="bi bi-file-pdf text-danger"></i>
                <?php else: ?>
                    <i class="bi bi-file-earmark text-secondary"></i>
                <?php endif; ?>
                <div class="flex-grow-1 text-truncate">
                    <a href="<?= BASE_URL ?>/public/uploads/documents/<?= $lead['id'] ?>/<?= htmlspecialchars($doc['filename']) ?>" target="_blank" class="text-decoration-none">
                        <?= htmlspecialchars($doc['original_name']) ?>
                    </a>
                    <div class="text-muted" style="font-size:0.65rem">Uploaded by <?= htmlspecialchars($doc['uploaded_by_name'] ?? '') ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Submissions History -->
<?php if (!empty($allSubmissions)): ?>
<div class="table-container mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Form Submissions History</h6>
    <table class="table table-sm small mb-0">
        <thead><tr><th>Form</th><th>Submitted By</th><th>Role</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($allSubmissions as $sub): ?>
        <tr>
            <td><strong><?= htmlspecialchars($sub['form_name'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($sub['submitted_by_name'] ?? '') ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$sub['role_name'] ?? ''))) ?></span></td>
            <td><span class="badge bg-<?= $sub['status']==='submitted'?'success':'warning' ?>"><?= ucfirst($sub['status']) ?></span></td>
            <td><?= formatDate($sub['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
