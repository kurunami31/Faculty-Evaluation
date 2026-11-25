<?php

declare(strict_types=1);

namespace App\Evaluation\Entity;

enum EvaluationStatus: string
{
    case Draft = "draft";
    case Submitted = "submitted";
    case Reviewed = "reviewed";
    case Approved = "approved";

    public function label(): string
    {
        return match ($this) {
            self::Draft => "Draft",
            self::Submitted => "Submitted",
            self::Reviewed => "Reviewed",
            self::Approved => "Approved",
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isSubmitted(): bool
    {
        return in_array($this, [self::Submitted, self::Reviewed, self::Approved], true);
    }
}