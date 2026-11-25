<?php

declare(strict_types=1);

namespace App\Evaluation\Service;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Auth\Service\RateLimiter as AuthRateLimiter;
use App\Evaluation\Entity\Evaluation;
use App\Evaluation\Entity\EvaluationCriterion;
use App\Evaluation\Entity\EvaluationPeriod;
use App\Evaluation\Entity\EvaluationStatus;
use App\Evaluation\Repository\EvaluationRepository;
use App\Shared\AuditLogger;
use DateTimeImmutable;
use Exception;

/**
 * Service layer for faculty evaluation business logic.
 *
 * Enforces:
 * - One active evaluation period at a time
 * - Role-based access (faculty evaluates others / chairs/admins oversee)
 * - Score range validation against criteria
 * - One draft per evaluator+evaluatee per period
 * - Audit logging for all state changes
 */
final class EvaluationService
{
    private EvaluationRepository $evaluationRepository;
    private UserRepository $userRepository;
    private AuthRateLimiter $rateLimiter;
    private AuditLogger $auditLogger;

    public function __construct(
        EvaluationRepository $evaluationRepository,
        UserRepository $userRepository,
        AuthRateLimiter $rateLimiter,
        AuditLogger $auditLogger,
    ) {
        $this->evaluationRepository = $evaluationRepository;
        $this->userRepository = $userRepository;
        $this->rateLimiter = $rateLimiter;
        $this->auditLogger = $auditLogger;
    }

    /* ---------- Periods ---------- */

    public function createPeriod(array $data, User $actor): EvaluationPeriod
    {
        // Only one active period allowed
        if (!empty($data["is_active"])) {
            $this->evaluationRepository->setActivePeriod(0); // deactivate all first
        }

        $period = $this->evaluationRepository->createPeriod($data);

        $this->auditLogger->log(
            $actor->id,
            "evaluation_period.create",
            "evaluation_period",
            $period->id,
            null,
            [
                "name" => $period->name,
                "academic_year" => $period->academicYear,
                "semester" => $period->semester,
                "start_date" => $period->startDate->format("Y-m-d"),
                "end_date" => $period->endDate->format("Y-m-d"),
                "is_active" => $period->isActive,
            ]
        );

        return $period;
    }

    public function activatePeriod(int $periodId, User $actor): void
    {
        $this->evaluationRepository->setActivePeriod($periodId);
        $period = $this->evaluationRepository->findPeriodById($periodId);
        if ($period === null) {
            throw new Exception("Evaluation period not found");
        }

        $this->auditLogger->log(
            $actor->id,
            "evaluation_period.activate",
            "evaluation_period",
            $periodId,
            null,
            ["name" => $period->name]
        );
    }

    public function getActivePeriod(): ?EvaluationPeriod
    {
        return $this->evaluationRepository->getActivePeriod();
    }

    public function listPeriods(): array
    {
        return $this->evaluationRepository->getAllPeriods();
    }

    /* ---------- Criteria ---------- */

    public function createCriterion(array $data, User $actor): EvaluationCriterion
    {
        $criterion = $this->evaluationRepository->createCriterion($data);

        $this->auditLogger->log(
            $actor->id,
            "evaluation_criterion.create",
            "evaluation_criterion",
            $criterion->id,
            null,
            [
                "category" => $criterion->category,
                "name" => $criterion->name,
                "weight" => $criterion->weight,
                "min_score" => $criterion->minScore,
                "max_score" => $criterion->maxScore,
            ]
        );

        return $criterion;
    }

    public function listCriteria(): array
    {
        return $this->evaluationRepository->getActiveCriteria();
    }

    /* ---------- Evaluations ---------- */

    /**
     * Create (or return existing) draft evaluation for an evaluatee in the active period.
     *
     * Any authenticated active user may evaluate a colleague; self-evaluation is blocked.
     * Period/criterion/status management remains restricted to admin/chair.
     */
    public function seedEvaluationForEvaluatee(int $evaluateeId, User $actor): Evaluation
    {
        if ($evaluateeId === $actor->id) {
            throw new Exception("You cannot evaluate yourself");
        }

        $period = $this->evaluationRepository->getActivePeriod();
        if ($period === null) {
            throw new Exception("No active evaluation period");
        }

        $evaluatee = $this->userRepository->findById($evaluateeId);
        if ($evaluatee === null || !$evaluatee->isActive()) {
            throw new Exception("Evaluatee not found");
        }

        // Prevent duplicate drafts (one per evaluator+evaluatee per period)
        $existing = $this->evaluationRepository->findByPeriodEvaluatorEvaluatee($period->id, $actor->id, $evaluateeId);
        if ($existing !== null) {
            return $existing;
        }

        // Evaluator defaults to the actor (admin/chair seeding on behalf)
        $evaluation = $this->evaluationRepository->createEvaluation(
            $period->id,
            $evaluateeId,
            $actor->id
        );

        $this->auditLogger->log(
            $actor->id,
            "evaluation.seed",
            "evaluation",
            $evaluation->id,
            null,
            ["period_id" => $period->id, "evaluatee_id" => $evaluateeId]
        );

        return $evaluation;
    }

    /**
     * Save a response for a criterion within a draft evaluation.
     *
     * Enforces:
     * - Evaluation must be in draft state
     * - Actor must be the evaluator
     * - Score must be within criterion bounds
     * - Rate limiting per evaluation
     */
    public function saveResponse(
        int $evaluationId,
        int $criterionId,
        int $score,
        ?string $comments,
        User $actor
    ): void {
        $evaluation = $this->evaluationRepository->findEvaluationById($evaluationId);
        if ($evaluation === null) {
            throw new Exception("Evaluation not found");
        }

        if (!$evaluation->isEditable()) {
            throw new Exception("Evaluation is not in draft state and cannot be modified");
        }

        if ($evaluation->evaluatorId !== $actor->id) {
            throw new Exception("You are not the evaluator for this evaluation");
        }

        $criterion = $this->evaluationRepository->findCriterionById($criterionId);
        if ($criterion === null || !$criterion->isActive) {
            throw new Exception("Invalid criterion");
        }

        if ($score < $criterion->minScore || $score > $criterion->maxScore) {
            throw new Exception(
                sprintf("Score must be between %d and %d", $criterion->minScore, $criterion->maxScore)
            );
        }

        // Rate limit: max 60 saves per minute per evaluation
        $rateKey = "eval:response:{$evaluationId}:{$actor->id}";
        if (!$this->rateLimiter->checkLimit($rateKey)) {
            throw new Exception("Too many response saves. Please slow down.");
        }
        $this->rateLimiter->recordAttempt($rateKey);

        $this->evaluationRepository->saveResponse($evaluationId, $criterionId, $score, $comments);

        $this->auditLogger->log(
            $actor->id,
            "evaluation.response.save",
            "evaluation",
            $evaluationId,
            null,
            ["criterion_id" => $criterionId, "score" => $score]
        );
    }

    /**
     * Submit a draft evaluation, computing the weighted total score.
     */
    public function submitEvaluation(int $evaluationId, User $actor): Evaluation
    {
        $evaluation = $this->evaluationRepository->findEvaluationById($evaluationId);
        if ($evaluation === null) {
            throw new Exception("Evaluation not found");
        }

        if (!$evaluation->isEditable()) {
            throw new Exception("Evaluation has already been submitted");
        }

        if ($evaluation->evaluatorId !== $actor->id) {
            throw new Exception("You are not the evaluator for this evaluation");
        }

        $responses = $this->evaluationRepository->getResponsesByEvaluation($evaluationId);
        $criteria = $this->evaluationRepository->getActiveCriteria();

        if (count($responses) === 0) {
            throw new Exception("Cannot submit an evaluation with no responses");
        }

        $criterionMap = [];
        foreach ($criteria as $c) {
            $criterionMap[$c->id] = $c;
        }

        $weightedSum = 0.0;
        $weightSum = 0.0;
        $missing = [];

        foreach ($responses as $response) {
            if (!isset($criterionMap[$response->criterionId])) {
                continue;
            }
            $c = $criterionMap[$response->criterionId];
            $weightedSum += $response->score * $c->weight;
            $weightSum += $c->weight;
        }

        // Check that every active criterion has a response
        foreach ($criteria as $c) {
            $hasResponse = false;
            foreach ($responses as $r) {
                if ($r->criterionId === $c->id) {
                    $hasResponse = true;
                    break;
                }
            }
            if (!$hasResponse) {
                $missing[] = $c->name;
            }
        }

        if (!empty($missing)) {
            throw new Exception("Missing scores for: " . implode(", ", $missing));
        }

        $totalScore = $weightSum > 0 ? round($weightedSum / $weightSum, 2) : null;

        $this->evaluationRepository->updateEvaluationStatus(
            $evaluationId,
            EvaluationStatus::Submitted->value,
            $totalScore
        );

        $this->auditLogger->log(
            $actor->id,
            "evaluation.submit",
            "evaluation",
            $evaluationId,
            null,
            ["total_score" => $totalScore]
        );

        return $this->evaluationRepository->findEvaluationById($evaluationId) ?? $evaluation;
    }

    /* ---------- Listings ---------- */

    /**
     * Evaluations the given user has given (as evaluator) in the active period.
     *
     * @return Evaluation[]
     */
    public function listMyEvaluations(User $user): array
    {
        $period = $this->evaluationRepository->getActivePeriod();
        if ($period === null) {
            return [];
        }
        return $this->evaluationRepository->findByPeriodAndEvaluator($period->id, $user->id);
    }

    /**
     * Evaluations the given user has received (as evaluatee) in the active period.
     *
     * @return Evaluation[]
     */
    public function listReceivedEvaluations(User $user): array
    {
        $period = $this->evaluationRepository->getActivePeriod();
        if ($period === null) {
            return [];
        }
        return $this->evaluationRepository->findAllByPeriodAndEvaluatee($period->id, $user->id);
    }

    /**
     * @return Evaluation[]
     */
    public function listAllForActivePeriod(): array
    {
        $period = $this->evaluationRepository->getActivePeriod();
        if ($period === null) {
            return [];
        }
        return $this->evaluationRepository->findAllByPeriod($period->id);
    }

    /* ---------- Admin oversight ---------- */

    public function listByStatus(string $status): array
    {
        return $this->evaluationRepository->findByPeriodAndStatus($status);
    }

    public function statsForActivePeriod(): array
    {
        $period = $this->evaluationRepository->getActivePeriod();
        if ($period === null) {
            return [
                "period" => null,
                "draft" => 0,
                "submitted" => 0,
                "reviewed" => 0,
                "approved" => 0,
                "total" => 0,
            ];
        }

        $draft = $this->evaluationRepository->countByPeriodAndStatus($period->id, EvaluationStatus::Draft->value);
        $submitted = $this->evaluationRepository->countByPeriodAndStatus($period->id, EvaluationStatus::Submitted->value);
        $reviewed = $this->evaluationRepository->countByPeriodAndStatus($period->id, EvaluationStatus::Reviewed->value);
        $approved = $this->evaluationRepository->countByPeriodAndStatus($period->id, EvaluationStatus::Approved->value);

        return [
            "period" => $period,
            "draft" => $draft,
            "submitted" => $submitted,
            "reviewed" => $reviewed,
            "approved" => $approved,
            "total" => $draft + $submitted + $reviewed + $approved,
        ];
    }

    public function changeEvaluationStatus(int $evaluationId, string $status, User $actor): Evaluation
    {
        if (!$actor->role->canManageUsers()) {
            throw new Exception("Insufficient permissions to change evaluation status");
        }

        $evaluation = $this->evaluationRepository->findEvaluationById($evaluationId);
        if ($evaluation === null) {
            throw new Exception("Evaluation not found");
        }

        $this->evaluationRepository->updateEvaluationStatus($evaluationId, $status);

        $this->auditLogger->log(
            $actor->id,
            "evaluation.status.change",
            "evaluation",
            $evaluationId,
            ["old" => $evaluation->status->value],
            ["new" => $status]
        );

        return $this->evaluationRepository->findEvaluationById($evaluationId) ?? $evaluation;
    }
}