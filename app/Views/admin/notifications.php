<div class="page-header">
    <h4><i class="bi bi-bell me-2"></i>Notifications</h4>
</div>

<div class="table-container">
    <?php if (empty($notifications)): ?>
        <p class="text-center text-muted py-4">No notifications.</p>
    <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
        <div class="d-flex gap-3 p-3 border-bottom <?= !$notif['is_read'] ? 'bg-light' : '' ?>" id="notif-<?= $notif['id'] ?>">
            <div class="bg-<?= $notif['type'] === 'info' ? 'info' : ($notif['type'] === 'warning' ? 'warning' : ($notif['type'] === 'error' ? 'danger' : 'success') ) ?> bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                <i class="bi bi-<?= $notif['type'] === 'info' ? 'info-circle' : ($notif['type'] === 'warning' ? 'exclamation-triangle' : ($notif['type'] === 'error' ? 'x-circle' : 'check-circle') ) ?> text-<?= $notif['type'] === 'info' ? 'info' : ($notif['type'] === 'warning' ? 'warning' : ($notif['type'] === 'error' ? 'danger' : 'success') ) ?>"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold small"><?= htmlspecialchars($notif['title']) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($notif['message']) ?></div>
                <div class="text-muted mt-1" style="font-size:0.75rem">
                    <?= formatDate($notif['created_at'], 'd M Y, h:i A') ?>
                    <?php if (!$notif['is_read']): ?>
                        <button class="btn btn-sm btn-link p-0 ms-2" onclick="markRead(<?= $notif['id'] ?>)">Mark as read</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
async function markRead(id) {
    const result = await ajaxPost('/bestdealcrm/admin/notifications/read', { notification_id: id });
    if (result.success) {
        const el = document.getElementById('notif-' + id);
        if (el) el.classList.remove('bg-light');
    }
}
</script>
