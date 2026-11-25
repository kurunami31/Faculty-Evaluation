<?php
/**
 * Evaluation form.
 *
 * Expected variables: $user, $activePeriod, $evaluation, $criteria, $responses
 */
?>
<div class="container">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Evaluate Faculty</h2>
                <p class="text-muted">
                    Period: <?= e($evaluation->periodName ?? "") ?> &nbsp;|&nbsp;
                    Evaluatee: <?= e($evaluation->evaluateeName ?? "") ?>
                </p>
            </div>
            <a href="/evaluations.php?action=dashboard" class="btn btn-outline btn-sm">Back</a>
        </div>
        <div class="card-body">
            <?php if ($evaluation->isSubmitted()): ?>
                <div class="alert alert-success">
                    This evaluation has already been submitted and can no longer be edited.
                    <?php if ($evaluation->totalScore !== null): ?>
                        Final weighted score: <strong><?= e(number_format($evaluation->totalScore, 2)) ?></strong>
                    <?php endif; ?>
                </div>
                <?php foreach ($responses as $criterionId => $resp): ?>
                    <?php $c = null; foreach ($criteria as $cc) { if ($cc->id === $criterionId) { $c = $cc; break; } } ?>
                    <?php if ($c): ?>
                        <div class="criterion-card">
                            <div class="criterion-header">
                                <strong><?= e($c->category) ?></strong>
                                <span class="badge badge-primary"><?= e($c->name) ?></span>
                                <span class="badge badge-success">Score: <?= (int)$resp->score ?></span>
                            </div>
                            <?php if ($resp->comments): ?>
                                <p class="text-sm text-muted"><?= e($resp->comments) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <form id="evaluationForm"
                      class="space-y-4"
                      data-evaluation-id="<?= (int)$evaluation->id ?>"
                      data-evaluatee-id="<?= (int)$evaluation->evaluateeId ?>"
                      data-csrf="<?= e(csrf_token()) ?>"
                      data-save-url="/evaluations.php?action=save_response"
                      data-submit-url="/evaluations.php?action=submit">
                    <input type="hidden" name="evaluation_id" value="<?= (int)$evaluation->id ?>">

                    <div class="criteria-grid">
                        <?php foreach ($criteria as $criterion): ?>
                            <div class="criterion-card">
                                <div class="criterion-header">
                                    <strong><?= e($criterion->category) ?></strong>
                                    <span class="badge badge-primary"><?= e($criterion->name) ?></span>
                                    <span class="text-muted">Score: <?= (int)$criterion->minScore ?>–<?= (int)$criterion->maxScore ?></span>
                                </div>
                                <?php if (!empty($criterion->description)): ?>
                                    <p class="text-sm text-muted mb-3"><?= e($criterion->description) ?></p>
                                <?php endif; ?>
                                <div class="score-selector" role="radiogroup" aria-label="<?= e($criterion->name) ?> score">
                                    <?php for ($s = $criterion->minScore; $s <= $criterion->maxScore; $s++): ?>
                                        <label class="score-btn<?= (isset($responses[$criterion->id]) && (int)$responses[$criterion->id]->score === $s) ? " active" : "" ?>">
                                            <input type="radio" name="score_<?= (int)$criterion->id ?>" value="<?= (int)$s ?>" <?= (isset($responses[$criterion->id]) && (int)$responses[$criterion->id]->score === $s) ? "checked" : "" ?>>
                                            <span><?= (int)$s ?></span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <div class="comments-field mt-2">
                                    <textarea name="comments_<?= (int)$criterion->id ?>" rows="2" class="form-input" maxlength="2000" placeholder="Comments (optional)"><?= e(isset($responses[$criterion->id]) ? (string)$responses[$criterion->id]->comments : "") ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-actions mt-6 flex justify-between items-center">
                        <span id="saveStatus" class="text-muted text-sm" aria-live="polite"></span>
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-outline" data-action="save-draft">Save Draft</button>
                            <button type="button" class="btn btn-primary" data-action="submit-evaluation">Submit Evaluation</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
