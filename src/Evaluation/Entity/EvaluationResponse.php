<?php

declare(strict_types=1);

namespace App\Evaluation\Entity;

use DateTimeImmutable;

final readonly class EvaluationResponse
{
    public function __construct(
        public int $id,
        public int $evaluationId,
        public int $criterionId,
        public int $score,
        public ?string $comments,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data["id"],
            evaluationId: (int)$data["evaluation_id"],
            criterionId: (int)$data["criterion_id"],
            score: (int)$data["score"],
            comments: $data["comments"] ? (string)$data["comments"] : null,
            createdAt: new DateTimeImmutable((string)$data["created_at"]),
            updatedAt: new DateTimeImmutable((string)$data["updated_at"]),
        );
    }
}