<?php

declare(strict_types=1);

namespace App\Evaluation\Entity;

use DateTimeImmutable;

final readonly class EvaluationPeriod
{
    public function __construct(
        public int $id,
        public string $name,
        public string $academicYear,
        public string $semester,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public bool $isActive,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    public function isOpen(): bool
    {
        $now = new DateTimeImmutable();
        return $this->isActive && $this->startDate <= $now && $this->endDate >= $now;
    }

    public function isUpcoming(): bool
    {
        return $this->isActive && $this->startDate > new DateTimeImmutable();
    }

    public function isClosed(): bool
    {
        return !$this->isActive || $this->endDate < new DateTimeImmutable();
    }

    public function getDateRange(): string
    {
        return $this->startDate->format("M d, Y") . " \u2013 " . $this->endDate->format("M d, Y");
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)$data["id"],
            name: (string)$data["name"],
            academicYear: (string)$data["academic_year"],
            semester: (string)$data["semester"],
            startDate: new DateTimeImmutable((string)$data["start_date"]),
            endDate: new DateTimeImmutable((string)$data["end_date"]),
            isActive: (bool)$data["is_active"],
            createdAt: new DateTimeImmutable((string)$data["created_at"]),
            updatedAt: new DateTimeImmutable((string)$data["updated_at"]),
        );
    }
}