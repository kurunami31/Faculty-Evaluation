<?php
/**
 * Evaluation dashboard.
 *
 * Expected variables:
 * - $user, $activePeriod, $stats, $myEvaluations, $receivedEvaluations, $colleagues
 */
$statusBadge = [
    "draft" => "badge-warning",
    "submitted" => "badge-info",
    "reviewed" => "badge-primary",
    "approved" => "badge-success",
];
?>
<div class="container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Evaluations</h1>
        <p class="dashboard-subtitle">
            <?= $activePeriod
                ? "Active period: <strong>" . e($activePeriod->name) . "</strong> (" . e($activePeriod->getDateRange()) . ")"
                : "No active evaluation period" ?>
        </p>
    </div>

    <?php if ($activePeriod === null): ?>
        <div class="alert alert-warning">
            No active evaluation period has been set. Please contact an administrator.
        </div>
    <?php else: ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="stat-value"><?= (int)count($myEvaluations) ?></div>
                <div class="stat-label">Evaluations I gave</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <div class="stat-value"><?= (int)count(array_filter($myEvaluations, fn ($e) => $e->status->value === "draft")) ?></div>
                <div class="stat-label">Drafts in progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="stat-value"><?= (int)count($receivedEvaluations) ?></div>
                <div class="stat-label">Evaluations I received</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-info">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="stat-value"><?= (int)$stats["total"] ?></div>
                <div class="stat-label">Total in period</div>
            </div>
        </div>

        <!-- Colleagues to evaluate -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Evaluate a colleague</h2>
                <p class="text-muted">One evaluation per colleague per period</p>
            </div>
            <div class="card-body">
                <?php if (empty($colleagues)): ?>
                    <p class="text-muted">No other faculty accounts are available.</p>
                <?php else: ?>
                    <div class="faculty-grid">
                        <?php foreach ($colleagues as $c): ?>
                            <?php
                                $logo = faculty_logo($c->department);
                                $existing = null;
                                foreach ($myEvaluations as $ev) {
                                    if ($ev->evaluateeId === $c->id) { $existing = $ev; break; }
                                }
                            ?>
                            <div class="faculty-card">
                                <div class="faculty-card-logo">
                                    <?php if ($logo): ?>
                                        <img src="<?= e($logo) ?>" alt="<?= e($c->department) ?> logo" width="56" height="56" loading="lazy">
                                    <?php else: ?>
                                        <span class="faculty-card-initial"><?= e(strtoupper($c->firstName[0] . $c->lastName[0])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="faculty-card-info">
                                    <strong><?= e($c->getFullName()) ?></strong>
                                    <span class="text-muted"><?= e($c->department) ?></span>
                                    <span class="text-muted"><?= e($c->employeeId) ?></span>
                                </div>
                                <?php if ($existing !== null): ?>
                                    <a class="btn btn-sm <?= $existing->isEditable() ? "btn-primary" : "btn-outline" ?>"
                                       href="/evaluations.php?action=fill&evaluatee_id=<?= (int)$c->id ?>">
                                        <?= $existing->isEditable() ? "Continue draft" : "View" ?>
                                    </a>
                                <?php else: ?>
                                    <a class="btn btn-sm btn-primary" href="/evaluations.php?action=fill&evaluatee_id=<?= (int)$c->id ?>">Evaluate</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- My evaluations (given) -->
        <div class="card mt-6">
            <div class="card-header">
                <h2 class="card-title">Evaluations I gave</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Evaluatee</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Updated</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($myEvaluations)): ?>
                                <tr><td colspan="5" class="text-center text-muted">You have not started any evaluations yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($myEvaluations as $ev): ?>
                                <tr>
                                    <td>
                                        <?= e($ev->evaluateeName ?? "Faculty #" . $ev->evaluateeId) ?>
                                        <div class="text-muted text-sm"><?= e($ev->evaluateeEmail ?? "") ?></div>
                                    </td>
                                    <td><span class="badge <?= $statusBadge[$ev->status->value] ?? "badge-secondary" ?>"><?= e($ev->status->label()) ?></span></td>
                                    <td><?= $ev->totalScore !== null ? e(number_format($ev->totalScore, 2)) : "—" ?></td>
                                    <td><?= e($ev->updatedAt->format("M j, Y g:ia")) ?></td>
                                    <td class="text-right">
                                        <?php if ($ev->isEditable()): ?>
                                            <a class="btn btn-sm btn-primary" href="/evaluations.php?action=fill&evaluatee_id=<?= (int)$ev->evaluateeId ?>">Edit</a>
                                        <?php else: ?>
                                            <a class="btn btn-sm btn-outline" href="/evaluations.php?action=fill&evaluatee_id=<?= (int)$ev->evaluateeId ?>">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Evaluations received -->
        <div class="card mt-6">
            <div class="card-header">
                <h2 class="card-title">Evaluations I received</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Evaluator</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($receivedEvaluations)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No evaluations received in this period.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($receivedEvaluations as $ev): ?>
                                <tr>
                                    <td><?= e($ev->evaluatorName ?? "Faculty #" . $ev->evaluatorId) ?></td>
                                    <td><span class="badge <?= $statusBadge[$ev->status->value] ?? "badge-secondary" ?>"><?= e($ev->status->label()) ?></span></td>
                                    <td><?= $ev->totalScore !== null ? e(number_format($ev->totalScore, 2)) : "—" ?></td>
                                    <td><?= $ev->submittedAt ? e($ev->submittedAt->format("M j, Y g:ia")) : "—" ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($user->role->canManageUsers()): ?>
        <div class="card mt-6">
            <div class="card-header">
                <h2 class="card-title">Administration</h2>
            </div>
            <div class="card-body">
                <div class="flex gap-4 flex-wrap">
                    <a href="/evaluations.php?action=admin" class="btn btn-primary">Admin Panel</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
