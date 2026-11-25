<?php

declare(strict_types=1);

namespace App\Evaluation\Repository;

use App\Evaluation\Entity\Evaluation;
use App\Evaluation\Entity\EvaluationCriterion;
use App\Evaluation\Entity\EvaluationPeriod;
use App\Evaluation\Entity\EvaluationResponse;
use App\Evaluation\Entity\EvaluationStatus;
use App\Shared\Database;
use DateTimeImmutable;
use PDO;

/**
 * Repository for evaluation data access.
 *
 * All queries use prepared statements and parameter binding to prevent SQL injection.
 */
final class EvaluationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /* ---------- Periods ---------- */

    public function getActivePeriod(): ?EvaluationPeriod
    {
        $stmt = $this->db->prepare("
            SELECT * FROM evaluation_periods
            WHERE is_active = 1
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? EvaluationPeriod::fromArray($row) : null;
    }

    public function getAllPeriods(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM evaluation_periods
            ORDER BY start_date DESC, id DESC
        ");
        $rows = $stmt->fetchAll();
        return array_map(fn (array $r) => EvaluationPeriod::fromArray($r), $rows);
    }

    public function createPeriod(array $data): EvaluationPeriod
    {
        $stmt = $this->db->prepare("
            INSERT INTO evaluation_periods (
                name, academic_year, semester, start_date, end_date,
                is_active, created_at, updated_at
            ) VALUES (
                :name, :academic_year, :semester, :start_date, :end_date,
                :is_active, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ");
        $stmt->execute([
            "name" => $data["name"],
            "academic_year" => $data["academic_year"],
            "semester" => $data["semester"],
            "start_date" => $data["start_date"],
            "end_date" => $data["end_date"],
            "is_active" => (int)($data["is_active"] ?? 0),
        ]);
        $id = (int)$this->db->lastInsertId();
        $period = $this->findPeriodById($id);
        if ($period === null) {
            throw new \RuntimeException("Failed to create evaluation period");
        }
        return $period;
    }

    public function findPeriodById(int $id): ?EvaluationPeriod
    {
        $stmt = $this->db->prepare("SELECT * FROM evaluation_periods WHERE id = :id LIMIT 1");
        $stmt->execute(["id" => $id]);
        $row = $stmt->fetch();
        return $row ? EvaluationPeriod::fromArray($row) : null;
    }

    public function setActivePeriod(int $periodId): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->exec("UPDATE evaluation_periods SET is_active = 0, updated_at = CURRENT_TIMESTAMP");
            $stmt = $this->db->prepare("UPDATE evaluation_periods SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute(["id" => $periodId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ---------- Criteria ---------- */

    public function getActiveCriteria(): array
    {
        $stmt = $this->db->query("
            SELECT * FROM evaluation_criteria
            WHERE is_active = 1
            ORDER BY display_order ASC, id ASC
        ");
        $rows = $stmt->fetchAll();
        return array_map(fn (array $r) => EvaluationCriterion::fromArray($r), $rows);
    }

    public function createCriterion(array $data): EvaluationCriterion
    {
        $stmt = $this->db->prepare("
            INSERT INTO evaluation_criteria (
                category, name, description, weight, min_score, max_score,
                is_active, display_order, created_at, updated_at
            ) VALUES (
                :category, :name, :description, :weight, :min_score, :max_score,
                :is_active, :display_order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ");
        $stmt->execute([
            "category" => $data["category"],
            "name" => $data["name"],
            "description" => $data["description"] ?? null,
            "weight" => $data["weight"] ?? 1.0,
            "min_score" => $data["min_score"] ?? 1,
            "max_score" => $data["max_score"] ?? 5,
            "is_active" => (int)($data["is_active"] ?? 1),
            "display_order" => $data["display_order"] ?? 0,
        ]);
        $id = (int)$this->db->lastInsertId();
        $criterion = $this->findCriterionById($id);
        if ($criterion === null) {
            throw new \RuntimeException("Failed to create criterion");
        }
        return $criterion;
    }

    public function findCriterionById(int $id): ?EvaluationCriterion
    {
        $stmt = $this->db->prepare("SELECT * FROM evaluation_criteria WHERE id = :id LIMIT 1");
        $stmt->execute(["id" => $id]);
        $row = $stmt->fetch();
        return $row ? EvaluationCriterion::fromArray($row) : null;
    }

    /* ---------- Evaluations ---------- */

    public function createEvaluation(int $periodId, int $evaluateeId, int $evaluatorId): Evaluation
    {
        $stmt = $this->db->prepare("
            INSERT INTO evaluations (
                period_id, evaluatee_id, evaluator_id, status, total_score, submitted_at,
                created_at, updated_at
            ) VALUES (
                :period_id, :evaluatee_id, :evaluator_id, 'draft', NULL, NULL,
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ");
        $stmt->execute([
            "period_id" => $periodId,
            "evaluatee_id" => $evaluateeId,
            "evaluator_id" => $evaluatorId,
        ]);
        $id = (int)$this->db->lastInsertId();
        $evaluation = $this->findEvaluationById($id);
        if ($evaluation === null) {
            throw new \RuntimeException("Failed to create evaluation");
        }
        return $evaluation;
    }

    public function findEvaluationById(int $id): ?Evaluation
    {
        $stmt = $this->db->prepare("
            SELECT e.*, CONCAT(u1.first_name, ' ', u1.last_name) AS evaluatee_name,
                   u1.email AS evaluatee_email,
                   CONCAT(u2.first_name, ' ', u2.last_name) AS evaluator_name,
                   p.name AS period_name
            FROM evaluations e
            JOIN users u1 ON u1.id = e.evaluatee_id
            JOIN users u2 ON u2.id = e.evaluator_id
            JOIN evaluation_periods p ON p.id = e.period_id
            WHERE e.id = :id
            LIMIT 1
        ");
        $stmt->execute(["id" => $id]);
        $row = $stmt->fetch();
        return $row ? Evaluation::fromArray($row) : null;
    }

    public function findByPeriodAndEvaluatee(int $periodId, int $evaluateeId): ?Evaluation
    {
        $stmt = $this->db->prepare("
            SELECT e.*, CONCAT(u1.first_name, ' ', u1.last_name) AS evaluatee_name,
                   u1.email AS evaluatee_email,
                   CONCAT(u2.first_name, ' ', u2.last_name) AS evaluator_name,
                   p.name AS period_name
            FROM evaluations e
            JOIN users u1 ON u1.id = e.evaluatee_id
            JOIN users u2 ON u2.id = e.evaluator_id
            JOIN evaluation_periods p ON p.id = e.period_id
            WHERE e.period_id = :period_id AND e.evaluatee_id = :evaluatee_id
            LIMIT 1
        ");
        $stmt->execute(["period_id" => $periodId, "evaluatee_id" => $evaluateeId]);
        $row = $stmt->fetch();
        return $row ? Evaluation::fromArray($row) : null;
    }

    public function findByPeriodEvaluatorEvaluatee(int $periodId, int $evaluatorId, int $evaluateeId): ?Evaluation
    {
        $stmt = $this->db->prepare("
            SELECT e.*, CONCAT(u1.first_name, ' ', u1.last_name) AS evaluatee_name,
                   u1.email AS evaluatee_email,
                   CONCAT(u2.first_name, ' ', u2.last_name) AS evaluator_name,
                   p.name AS period_name
            FROM evaluations e
            JOIN users u1 ON u1.id = e.evaluatee_id
            JOIN users u2 ON u2.id = e.evaluator_id
            JOIN evaluation_periods p ON p.id = e.period_id
            WHERE e.period_id = :period_id AND e.evaluator_id = :evaluator_id AND e.evaluatee_id = :evaluatee_id
            LIMIT 1
        ");
        $stmt->execute([
            "period_id" => $periodId,
            "evaluator_id" => $evaluatorId,
            "evaluatee_id" => $evaluateeId,
        ]);
        $row = $stmt->fetch();
        return $row ? Evaluation::fromArray($row) : null;
    }

    /**
     * @return Evaluation[]
     */
    public function findAllByPeriodAndEvaluatee(int $periodId, int $evaluateeId): array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, CONCAT(u1.first_name, ' ', u1.last_name) AS evaluatee_name,
                   u1.email AS evaluatee_email,
                   CONCAT(u2.first_name, ' ', u2.last_name) AS evaluator_name,
                   p.name AS period_name
            FROM evaluations e
            JOIN users u1 ON u1.id = e.evaluatee_id
            JOIN users u2 ON u2.id = e.evaluator_id
            JOIN evaluation_periods p ON p.id = e.period_id
            WHERE e.period_id = :period_id AND e.evaluatee_id = :evaluatee_id
            ORDER BY e.updated_at DESC
        ");
        $stmt->execute(["period_id" => $periodId, "evaluatee_id" => $evaluateeId]);
        return array_map(fn (array $r) => Evaluation::fromArray($r), $stmt->fetchAll());
    }

    /**
     * @return Evaluation[]
     */
    public function findAllByPeriod(int $periodId): array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, CONCAT(u1.first_name, ' ', u1.last_name) AS evaluatee_name,
                   u1.email AS evaluatee_email,
                   CONCAT(u2.first_name, ' ', u2.last_name) AS evaluator_name,
                   p.name AS period_name
            FROM evaluations e
            JOIN users u1 ON u1.id = e.evaluatee_id
            JOIN users u2 ON u2.id = e.evaluator_id
            JOIN evaluation_periods p ON p.id = e.period_id
            WHERE e.period_id = :period_id
            ORDER BY e.updated_at DESC
        ");
        $stmt->execute(["period_id" => $periodId]);
        return array_map(fn (array $r) => Evaluation::fromArray($r), $stmt->fetchAll());
    }

    public function findByPeriodAndEvaluator(int $periodId, int $evaluatorId): array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, CONCAT(u1.first_name, ' ', u1.last_name) AS evaluatee_name,
                   u1.email AS evaluatee_email,
                   CONCAT(u2.first_name, ' ', u2.last_name) AS evaluator_name,
                   p.name AS period_name
            FROM evaluations e
            JOIN users u1 ON u1.id = e.evaluatee_id
            JOIN users u2 ON u2.id = e.evaluator_id
            JOIN evaluation_periods p ON p.id = e.period_id
            WHERE e.period_id = :period_id AND e.evaluator_id = :evaluator_id
            ORDER BY e.updated_at DESC
        ");
        $stmt->execute(["period_id" => $periodId, "evaluator_id" => $evaluatorId]);
        $rows = $stmt->fetchAll();
        return array_map(fn (array $r) => Evaluation::fromArray($r), $rows);
    }

    public function findByPeriodAndStatus(string $status): array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, CONCAT(u1.first_name, ' ', u1.last_name) AS evaluatee_name,
                   u1.email AS evaluatee_email,
                   CONCAT(u2.first_name, ' ', u2.last_name) AS evaluator_name,
                   p.name AS period_name
            FROM evaluations e
            JOIN users u1 ON u1.id = e.evaluatee_id
            JOIN users u2 ON u2.id = e.evaluator_id
            JOIN evaluation_periods p ON p.id = e.period_id
            WHERE e.status = :status
            ORDER BY e.updated_at DESC
        ");
        $stmt->execute(["status" => $status]);
        $rows = $stmt->fetchAll();
        return array_map(fn (array $r) => Evaluation::fromArray($r), $rows);
    }

    public function countByPeriodAndStatus(int $periodId, string $status): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS c FROM evaluations
            WHERE period_id = :period_id AND status = :status
        ");
        $stmt->execute(["period_id" => $periodId, "status" => $status]);
        $row = $stmt->fetch();
        return (int)$row["c"];
    }

    public function updateEvaluationStatus(int $id, string $status, ?float $totalScore = null): void
    {
        $submittedAt = $status !== "draft" ? (new DateTimeImmutable())->format("Y-m-d H:i:s") : NULL;
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE evaluations
                SET status = :status, total_score = :total_score,
                    submitted_at = :submitted_at, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                "status" => $status,
                "total_score" => $totalScore,
                "submitted_at" => $submittedAt,
                "id" => $id,
            ]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ---------- Responses ---------- */

    public function saveResponse(int $evaluationId, int $criterionId, int $score, ?string $comments): void
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === "mysql") {
            $sql = "
                INSERT INTO evaluation_responses (evaluation_id, criterion_id, score, comments, created_at, updated_at)
                VALUES (:evaluation_id, :criterion_id, :score, :comments, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE
                    score = VALUES(score),
                    comments = VALUES(comments),
                    updated_at = CURRENT_TIMESTAMP
            ";
        } else {
            $sql = "
                INSERT INTO evaluation_responses (evaluation_id, criterion_id, score, comments, created_at, updated_at)
                VALUES (:evaluation_id, :criterion_id, :score, :comments, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ON CONFLICT(evaluation_id, criterion_id) DO UPDATE SET
                    score = excluded.score,
                    comments = excluded.comments,
                    updated_at = CURRENT_TIMESTAMP
            ";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            "evaluation_id" => $evaluationId,
            "criterion_id" => $criterionId,
            "score" => $score,
            "comments" => $comments,
        ]);
    }

    public function getResponsesByEvaluation(int $evaluationId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM evaluation_responses
            WHERE evaluation_id = :evaluation_id
            ORDER BY criterion_id ASC
        ");
        $stmt->execute(["evaluation_id" => $evaluationId]);
        $rows = $stmt->fetchAll();
        return array_map(fn (array $r) => EvaluationResponse::fromArray($r), $rows);
    }

    public function hasCompletedEvaluation(int $periodId, int $evaluateeId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS c FROM evaluations
            WHERE period_id = :period_id AND evaluatee_id = :evaluatee_id
              AND status IN ('submitted', 'reviewed', 'approved')
        ");
        $stmt->execute(["period_id" => $periodId, "evaluatee_id" => $evaluateeId]);
        $row = $stmt->fetch();
        return (int)$row["c"] > 0;
    }
}