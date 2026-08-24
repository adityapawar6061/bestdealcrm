<div class="page-header">
    <h4><i class="bi bi-diagram-3 me-2"></i>Workflow Stages</h4>
    <p class="text-muted small">The loan processing workflow stages</p>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order</th>
                    <th>Stage</th>
                    <th>Label</th>
                    <th>Description</th>
                    <th>Final</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stages as $stage): ?>
                <tr>
                    <td><?= $stage['display_order'] ?></td>
                    <td><code><?= htmlspecialchars($stage['name']) ?></code></td>
                    <td><strong><?= htmlspecialchars($stage['label']) ?></strong></td>
                    <td><small class="text-muted"><?= htmlspecialchars($stage['description'] ?? '-') ?></small></td>
                    <td><?= $stage['is_final'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Workflow Diagram -->
<div class="table-container mt-4">
    <h6 class="fw-bold mb-3">Workflow Flow</h6>
    <div class="p-3 bg-light rounded">
        <div class="d-flex flex-wrap align-items-center gap-2" style="font-size:0.85rem">
            <span class="badge bg-secondary">LEAD_UPLOADED</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-info">LEAD_ASSIGNED</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-warning text-dark">AGENT_DRAFT</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-primary">AGENT_SUBMITTED</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-warning text-dark">ADMIN_REVIEW_1</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-info">LOGIN_AGENT_ASSIGNED</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-warning text-dark">LOGIN_AGENT_DRAFT</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-warning text-dark">ADMIN_REVIEW_2</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-success">LOGIN_APPROVED</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-info">POST_LOGIN</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-warning text-dark">ADMIN_REVIEW_3</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-primary">UNDERWRITING</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-warning text-dark">ADMIN_REVIEW_4</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-info">DISPATCH</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-success">COMPLETED</span>
        </div>
        <div class="mt-2">
            <small class="text-muted">
                <i class="bi bi-arrow-return-left text-danger me-1"></i> Can return to: RETURNED_TO_AGENT, REJECTED
            </small>
        </div>
    </div>
</div>
