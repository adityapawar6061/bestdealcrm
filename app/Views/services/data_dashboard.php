<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="bi bi-speedometer2 me-2"></i>RM Admin Dashboard</h4>
    <div>
        <a href="<?= BASE_URL ?>/admin/data-view" class="btn btn-sm btn-primary"><i class="bi bi-list-ul me-1"></i>View Data</a>
        <a href="<?= BASE_URL ?>/admin/data-add" class="btn btn-sm btn-success"><i class="bi bi-plus-circle me-1"></i>Add Data</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number text-primary"><?= number_format($totalRecords) ?></div>
                    <div class="stat-label">Total Records</div>
                </div>
                <i class="bi bi-database text-primary" style="font-size:2rem;opacity:0.3"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number text-success"><?= number_format($todayRecords) ?></div>
                    <div class="stat-label">Today's Records</div>
                </div>
                <i class="bi bi-calendar-check text-success" style="font-size:2rem;opacity:0.3"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number text-info"><?= count($userPerformance) ?></div>
                    <div class="stat-label">Active Agents</div>
                </div>
                <i class="bi bi-people text-info" style="font-size:2rem;opacity:0.3"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-number text-warning">
                        <?php
                        $totalFollowUp = 0;
                        foreach ($userPerformance as $up) $totalFollowUp += $up['follow_up'];
                        echo number_format($totalFollowUp);
                        ?>
                    </div>
                    <div class="stat-label">Follow Ups Today</div>
                </div>
                <i class="bi bi-arrow-repeat text-warning" style="font-size:2rem;opacity:0.3"></i>
            </div>
        </div>
    </div>
</div>

<!-- User Performance Table -->
<div class="table-container">
    <h5 class="mb-3 fw-bold"><i class="bi bi-graph-up me-2"></i>User Performance — Overall Today</h5>
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>User</th>
                    <th class="text-center">Total Records</th>
                    <th class="text-center">RNR</th>
                    <th class="text-center">Disconnected</th>
                    <th class="text-center">Not Interested</th>
                    <th class="text-center">Call Back</th>
                    <th class="text-center">Follow Up</th>
                    <th class="text-center">Not Eligible</th>
                    <th class="text-center">Self Employed</th>
                    <th class="text-center">Lead</th>
                    <th class="text-center">DNC</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($userPerformance)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No entries today.</td></tr>
                <?php else: ?>
                    <?php foreach ($userPerformance as $up): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($up['user_name'] ?? 'Unknown') ?></strong></td>
                            <td class="text-center"><span class="badge bg-primary fs-6"><?= $up['total_records'] ?></span></td>
                            <td class="text-center"><?= $up['rnr'] ?></td>
                            <td class="text-center"><?= $up['disconnected'] ?></td>
                            <td class="text-center"><?= $up['not_interested'] ?></td>
                            <td class="text-center"><?= $up['call_back'] ?></td>
                            <td class="text-center"><strong class="text-primary"><?= $up['follow_up'] ?></strong></td>
                            <td class="text-center"><?= $up['not_eligible'] ?></td>
                            <td class="text-center"><?= $up['self_employed'] ?></td>
                            <td class="text-center"><strong class="text-success"><?= $up['lead'] ?></strong></td>
                            <td class="text-center"><?= $up['dnc'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <!-- Totals Row -->
                    <tr class="table-warning fw-bold">
                        <td>TOTAL</td>
                        <td class="text-center">
                            <?= array_sum(array_column($userPerformance, 'total_records')) ?>
                        </td>
                        <td class="text-center"><?= array_sum(array_column($userPerformance, 'rnr')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($userPerformance, 'disconnected')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($userPerformance, 'not_interested')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($userPerformance, 'call_back')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($userPerformance, 'follow_up')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($userPerformance, 'not_eligible')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($userPerformance, 'self_employed')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($userPerformance, 'lead')) ?></td>
                        <td class="text-center"><?= array_sum(array_column($userPerformance, 'dnc')) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
