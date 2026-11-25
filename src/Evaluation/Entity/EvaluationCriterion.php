<?php

declare(strict_types=1);

namespace App\Evaluation\Entity;

use DateTimeImmutable;

final readonly class EvaluationCriterion
{
    public function __construct(
        public int $id,
        public string $category,
        public string $name,
        public ?string $description,
        public float $weight,
        public int $minScore,
        public int $maxScore,
        public bool $isActive,
        public int $displayOrder,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    public function getLabel(): string
    {
        return $this->category . " \u2013 " . $this->name;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data["id"],
            category: (string)$data["category"],
            name: (string)$data["name"],
            description: $data["description"] ? (string)$data["description"] : null,
            weight: (float)$data["weight"],
            minScore: (int)$data["min_score"],
            maxScore: (int)$data["max_score"],
            isActive: (bool)$data["is_active"],
            displayOrder: (int)$data["display_order"],
            createdAt: new DateTimeImmutable((string)$data["created_at"]),
            updatedAt: new DateTimeImmutable((string)$data["updated_at"]),
        );
    }
}