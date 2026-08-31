<?php
$workflowSteps = [
    'LEAD_UPLOADED'=>['label'=>'Lead Uploaded','icon'=>'bi-cloud-upload'],'LEAD_ASSIGNED'=>['label'=>'Assigned','icon'=>'bi-person-check'],
    'AGENT_DRAFT'=>['label'=>'Agent Draft','icon'=>'bi-pencil-square'],'AGENT_SUBMITTED'=>['label'=>'Submitted','icon'=>'bi-send'],
    'ADMIN_REVIEW_1'=>['label'=>'Admin Review','icon'=>'bi-clipboard-check'],'LOGIN_AGENT_ASSIGNED'=>['label'=>'Login Agent','icon'=>'bi-person-badge'],
    'ADMIN_REVIEW_2'=>['label'=>'Review 2','icon'=>'bi-clipboard2-check'],'LOGIN_APPROVED'=>['label'=>'Approved','icon'=>'bi-check-circle'],
    'UNDERWRITING'=>['label'=>'Underwriting','icon'=>'bi-file-earmark-check'],'DISPATCH'=>['label'=>'Dispatch','icon'=>'bi-truck'],
    'COMPLETED'=>['label'=>'Completed','icon'=>'bi-trophy'],
];
?>
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clipboard2-check me-2"></i>Review Lead #<?= $lead['id'] ?> (Stage 4)</h4>
        <small class="text-muted"><?= htmlspecialchars($lead['customer_name'] ?? '') ?> | <?= htmlspecialchars($lead['mobile_number'] ?? '') ?> | <?= statusBadge($lead['workflow_stage']) ?></small>
    </div>
    <a href="<?= BASE_URL ?>/admin/review4" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>
<div class="table-container mb-4 p-3">
    <div class="d-flex align-items-center flex-wrap gap-1" style="overflow-x:auto">
        <?php $stepKeys=array_keys($workflowSteps); $currentIdx=array_search($lead['workflow_stage'],$stepKeys); if($currentIdx===false)$currentIdx=0;
        foreach($workflowSteps as $stage=>$info): $sIdx=array_search($stage,$stepKeys); $isActive=($stage===$lead['workflow_stage']); $isCompleted=($sIdx<$currentIdx); ?>
            <div class="text-center flex-shrink-0" style="min-width:70px">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1 <?= $isActive?'bg-primary text-white':($isCompleted?'bg-success text-white':'bg-light text-muted') ?>" style="width:32px;height:32px;font-size:0.75rem"><i class="bi <?= $info['icon'] ?>"></i></div>
                <div class="small <?= $isActive?'fw-bold text-primary':($isCompleted?'text-success':'text-muted') ?>" style="font-size:0.6rem;line-height:1.1"><?= $info['label'] ?></div>
            </div>
            <?php if($sIdx<count($stepKeys)-1): ?><div class="flex-shrink-0" style="width:16px;height:2px;background:<?= $isCompleted?'#22c55e':'#e2e8f0' ?>;margin-bottom:14px"></div><?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<div class="table-container mb-4 p-3">
    <div class="row g-3">
        <div class="col-md-3"><small class="text-muted d-block">Customer</small><strong><?= htmlspecialchars($lead['customer_name'] ?? '-') ?></strong></div>
        <div class="col-md-3"><small class="text-muted d-block">Mobile</small><strong><?= htmlspecialchars($lead['mobile_number'] ?? '-') ?></strong></div>
        <div class="col-md-3"><small class="text-muted d-block">Bank</small><strong><?= htmlspecialchars($lead['bank_name'] ?? '-') ?></strong></div>
        <div class="col-md-3"><small class="text-muted d-block">Stage</small><?= statusBadge($lead['workflow_stage']) ?></div>
    </div>
</div>
<div class="row g-4">
    <div class="col-lg-8"><?php include __DIR__ . '/_review_forms.php'; ?></div>
    <div class="col-lg-4">
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3">Timeline</h6>
            <?php foreach($timeline as $event): ?>
            <div class="d-flex gap-2 mb-2 pb-2 border-bottom small"><div><strong><?= htmlspecialchars($event['performed_by_name']??'System') ?></strong><span class="text-muted">— <?= htmlspecialchars(str_replace('_',' ',$event['action']??$event['new_stage'])) ?></span><div class="text-muted" style="font-size:0.75rem"><?= formatDate($event['created_at'],'d M, h:i A') ?></div></div></div>
            <?php endforeach; ?>
        </div>
        <?php if(!empty($remarks)): ?>
        <div class="table-container mb-4">
            <h6 class="fw-bold mb-3">Previous Remarks</h6>
            <?php foreach($remarks as $r): ?>
            <div class="d-flex gap-3 mb-2 pb-2 border-bottom small"><div><strong class="text-primary"><?= htmlspecialchars($r['user_name']??'') ?></strong><small class="text-muted ms-1"><?= formatDate($r['created_at'],'d M, h:i A') ?></small><div class="text-muted" style="font-size:0.65rem"><?= htmlspecialchars($r['stage']??'') ?></div><div><?= nl2br(htmlspecialchars($r['remark'])) ?></div></div></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="table-container">
            <h6 class="fw-bold mb-3">Your Review</h6>
            <form id="reviewForm"><?= csrfField() ?>
                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                <div class="mb-3"><label class="form-label small fw-semibold">Admin Approval 4 Remark</label><textarea name="admin_approval4_remark" class="form-control form-control-sm" rows="3" placeholder="Enter remarks..."></textarea></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Assign to Dispatch Agent</label>
                    <select name="assigned_to" class="form-select form-select-sm"><option value="">Select Dispatch Agent</option><?php foreach($dispatchAgents as $a): ?><option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option><?php endforeach; ?></select>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="processReview('approve_to_dispatch')"><i class="bi bi-check-lg me-1"></i> Approve & Send to Dispatch</button>
                    <button type="button" class="btn btn-danger" onclick="processReview('reject')"><i class="bi bi-x-lg me-1"></i> Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
async function processReview(action) {
    if(action==='reject'&&!confirm('Reject?'))return;
    const fd=new FormData(document.getElementById('reviewForm')); fd.append('action',action);
    try{const r=await fetch('<?= BASE_URL ?>/admin/review4/process',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});const res=await r.json();if(res.success){alert(res.message);window.location.href='<?= BASE_URL ?>/admin/review4';}else{alert(res.error||'Error');}}catch(e){alert('Network error');}
}
</script>
