<?php
/**
 * Admin panel: periods, criteria, oversight.
 *
 * Expected variables: $user, $activePeriod, $periods, $criteria, $stats,
 *                     $allEvaluations, $colleagues, $facultyCount
 */
$statusBadge = [
    "draft" => "badge-warning",
    "submitted" => "badge-info",
    "reviewed" => "badge-primary",
    "approved" => "badge-success",
];
$statusOptions = ["submitted", "reviewed", "approved"];
?>
<div class="container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">Admin Panel</h1>
        <p class="dashboard-subtitle">Manage evaluation periods, criteria, and oversee submissions.</p>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Periods -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Evaluation Periods</h2>
                <button type="button" class="btn btn-sm btn-primary" data-modal-open="periodModal">Add Period</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>AY / Semester</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($periods)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No periods yet. Create one to open evaluations.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($periods as $period): ?>
                                <tr>
                                    <td><?= e($period->name) ?></td>
                                    <td><?= e($period->academicYear) ?> &middot; <?= e($period->semester) ?></td>
                                    <td><?= e($period->getDateRange()) ?></td>
                                    <td>
                                        <span class="badge <?= $period->isActive ? "badge-success" : "badge-secondary" ?>">
                                            <?= $period->isActive ? "Active" : "Inactive" ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" action="/evaluations.php?action=activate_period" class="inline-form" data-confirm="<?= $period->isActive ? "Deactivate this period?" : "Activate this period? All other periods will be deactivated." ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="period_id" value="<?= (int)$period->id ?>">
                                            <button type="submit" class="btn btn-sm btn-outline"><?= $period->isActive ? "Deactivate" : "Activate" ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Criteria -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Evaluation Criteria</h2>
                <button type="button" class="btn btn-sm btn-primary" data-modal-open="criterionModal">Add Criterion</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Name</th>
                                <th>Weight</th>
                                <th>Range</th>
                                <th>Order</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($criteria)): ?>
                                <tr><td colspan="6" class="text-center text-muted">No criteria defined.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($criteria as $criterion): ?>
                                <tr>
                                    <td><?= e($criterion->category) ?></td>
                                    <td><?= e($criterion->name) ?></td>
                                    <td><?= e(number_format((float)$criterion->weight, 2)) ?></td>
                                    <td><?= (int)$criterion->minScore ?>–<?= (int)$criterion->maxScore ?></td>
                                    <td><?= (int)$criterion->displayOrder ?></td>
                                    <td>
                                        <span class="badge <?= $criterion->isActive ? "badge-success" : "badge-secondary" ?>">
                                            <?= $criterion->isActive ? "Active" : "Inactive" ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <?php if ($stats["period"]): ?>
    <div class="card mt-6">
        <div class="card-header">
            <h2 class="card-title">Period Statistics — <?= e($stats["period"]->name) ?></h2>
        </div>
        <div class="card-body">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div class="stat-value"><?= (int)$stats["total"] ?></div>
                    <div class="stat-label">Total Evaluations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-warning">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <div class="stat-value"><?= (int)$stats["draft"] ?></div>
                    <div class="stat-label">Draft</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-info">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 12h-4l-3 3L7 18"/></svg>
                    </div>
                    <div class="stat-value"><?= (int)$stats["submitted"] ?></div>
                    <div class="stat-label">Submitted</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="stat-value"><?= (int)$stats["approved"] ?></div>
                    <div class="stat-label">Approved</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon stat-icon-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                    </div>
                    <div class="stat-value"><?= (int)$facultyCount ?></div>
                    <div class="stat-label">Active Faculty</div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- All evaluations oversight -->
    <div class="card mt-6">
        <div class="card-header">
            <h2 class="card-title">All Evaluations in Active Period</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Evaluatee</th>
                            <th>Evaluator</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Updated</th>
                            <th class="text-right">Set status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allEvaluations)): ?>
                            <tr><td colspan="6" class="text-center text-muted">No evaluations in the active period yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($allEvaluations as $ev): ?>
                            <tr>
                                <td><?= e($ev->evaluateeName ?? "#" . $ev->evaluateeId) ?></td>
                                <td><?= e($ev->evaluatorName ?? "#" . $ev->evaluatorId) ?></td>
                                <td><span class="badge <?= $statusBadge[$ev->status->value] ?? "badge-secondary" ?>"><?= e($ev->status->label()) ?></span></td>
                                <td><?= $ev->totalScore !== null ? e(number_format($ev->totalScore, 2)) : "—" ?></td>
                                <td><?= e($ev->updatedAt->format("M j, Y g:ia")) ?></td>
                                <td class="text-right">
                                    <?php if (!$ev->isEditable()): ?>
                                        <form method="POST" action="/evaluations.php?action=change_status" class="inline-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="evaluation_id" value="<?= (int)$ev->id ?>">
                                            <select name="status" class="form-select form-select-sm" aria-label="Set status">
                                                <?php foreach ($statusOptions as $opt): ?>
                                                    <option value="<?= e($opt) ?>" <?= $ev->status->value === $opt ? "selected" : "" ?>><?= e(ucfirst($opt)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline">Apply</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted text-sm">draft</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Period modal -->
    <dialog class="modal" id="periodModal">
        <form method="POST" action="/evaluations.php?action=create_period" class="modal-content space-y-4">
            <?= csrf_field() ?>
            <h3 class="modal-title">Create Evaluation Period</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="period_name">Period Name</label>
                    <input type="text" id="period_name" name="name" class="form-input" placeholder="e.g., First Semester AY 2026-2027" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="academic_year">Academic Year</label>
                    <input type="text" id="academic_year" name="academic_year" class="form-input" placeholder="2026-2027" maxlength="20" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="semester">Semester</label>
                    <input type="text" id="semester" name="semester" class="form-input" placeholder="1st / 2nd / Summer" maxlength="20" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="is_active">Activate immediately</label>
                    <label class="checkbox-wrapper">
                        <input type="checkbox" id="is_active" name="is_active" class="checkbox-input">
                        <span class="checkbox-label">Set as the active period</span>
                    </label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-input" required>
                </div>
            </div>
            <div class="form-actions flex justify-end gap-2">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Create Period</button>
            </div>
        </form>
    </dialog>

    <!-- Criterion modal -->
    <dialog class="modal" id="criterionModal">
        <form method="POST" action="/evaluations.php?action=create_criterion" class="modal-content space-y-4">
            <?= csrf_field() ?>
            <h3 class="modal-title">Create Evaluation Criterion</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="c_category">Category</label>
                    <input type="text" id="c_category" name="category" class="form-input" placeholder="e.g., Teaching Effectiveness" maxlength="50" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="c_name">Criterion Name</label>
                    <input type="text" id="c_name" name="name" class="form-input" placeholder="e.g., Subject Mastery" maxlength="100" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="c_description">Description</label>
                <textarea id="c_description" name="description" class="form-input" rows="2" maxlength="1000" placeholder="Brief description"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="c_weight">Weight</label>
                    <input type="number" id="c_weight" name="weight" class="form-input" value="1.00" step="0.01" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="c_min">Min Score</label>
                    <input type="number" id="c_min" name="min_score" class="form-input" value="1" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="c_max">Max Score</label>
                    <input type="number" id="c_max" name="max_score" class="form-input" value="5" min="1" max="100" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="c_order">Display Order</label>
                    <input type="number" id="c_order" name="display_order" class="form-input" value="0" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="c_active">Active</label>
                    <label class="checkbox-wrapper">
                        <input type="checkbox" id="c_active" name="is_active" class="checkbox-input" checked>
                        <span class="checkbox-label">Usable in evaluations</span>
                    </label>
                </div>
            </div>
            <div class="form-actions flex justify-end gap-2">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Create Criterion</button>
            </div>
        </form>
    </dialog>
</div>
