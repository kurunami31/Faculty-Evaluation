<?php

declare(strict_types=1);

namespace App\Evaluation\Entity;

use DateTimeImmutable;

final readonly class Evaluation
{
    public function __construct(
        public int $id,
        public int $periodId,
        public int $evaluateeId,
        public int $evaluatorId,
        public EvaluationStatus $status,
        public ?float $totalScore,
        public ?DateTimeImmutable $submittedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        // Joined fields (optional)
        public ?string $evaluateeName = null,
        public ?string $evaluateeEmail = null,
        public ?string $evaluatorName = null,
        public ?string $periodName = null,
    ) {
    }

    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    public function isSubmitted(): bool
    {
        return $this->status->isSubmitted();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data["id"],
            periodId: (int)$data["period_id"],
            evaluateeId: (int)$data["evaluatee_id"],
            evaluatorId: (int)$data["evaluator_id"],
            status: EvaluationStatus::from((string)$data["status"]),
            totalScore: $data["total_score"] !== null ? (float)$data["total_score"] : null,
            submittedAt: $data["submitted_at"] ? new DateTimeImmutable((string)$data["submitted_at"]) : null,
            createdAt: new DateTimeImmutable((string)$data["created_at"]),
            updatedAt: new DateTimeImmutable((string)$data["updated_at"]),
            evaluateeName: $data["evaluatee_name"] ?? null,
            evaluateeEmail: $data["evaluatee_email"] ?? null,
            evaluatorName: $data["evaluator_name"] ?? null,
            periodName: $data["period_name"] ?? null,
        );
    }
}