<div class="page-header">
    <h4><i class="bi bi-bell me-2"></i>Notifications</h4>
</div>

<div class="table-container">
    <?php if (empty($notifications)): ?>
        <div class="text-center py-5">
            <i class="bi bi-bell-slash text-muted" style="font-size:3rem"></i>
            <p class="text-muted mt-2">No notifications.</p>
        </div>
    <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
        <div class="d-flex align-items-start gap-3 p-3 border-bottom <?= $notif['is_read'] ? '' : 'bg-light' ?>" id="notif-<?= $notif['id'] ?>">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px;background:<?= ($notif['type'] === 'success' ? '#dcfce7' : ($notif['type'] === 'warning' ? '#fef3c7' : '#dbeafe')) ?>">
                <i class="bi bi-<?= ($notif['type'] === 'success' ? 'check-circle text-success' : ($notif['type'] === 'warning' ? 'exclamation-triangle text-warning' : 'info-circle text-primary')) ?>"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold small"><?= htmlspecialchars($notif['title']) ?></div>
                <div class="text-muted" style="font-size:0.85rem"><?= htmlspecialchars($notif['message']) ?></div>
                <small class="text-muted"><?= formatDate($notif['created_at']) ?></small>
                <?php if ($notif['related_lead_id']): ?>
                    <a href="/bestdealcrm/agent/leads/<?= $notif['related_lead_id'] ?>" class="ms-2 small">View Lead</a>
                <?php endif; ?>
            </div>
            <?php if (!$notif['is_read']): ?>
            <button class="btn btn-sm btn-outline-primary" onclick="markRead(<?= $notif['id'] ?>)">Mark Read</button>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
async function markRead(id) {
    const fd = new FormData(); fd.append('notification_id', id);
    await ajaxPost('/bestdealcrm/agent/notifications/read', fd);
    const el = document.getElementById('notif-' + id); if (el) el.classList.remove('bg-light');
}
</script>
