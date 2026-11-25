<?php

declare(strict_types=1);

namespace App\Auth\DTO;

use App\Shared\Validation;

final readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            email: strtolower(trim($data["email"] ?? "")),
            password: $data["password"] ?? "",
            rememberMe: isset($data["remember_me"]) && filter_var($data["remember_me"], FILTER_VALIDATE_BOOLEAN),
        );
    }

    public function validate(string $allowedDomain): array
    {
        $errors = [];

        if (!Validation::emailDomain($this->email, $allowedDomain)) {
            $errors["email"] = "Please enter a valid @" . $allowedDomain . " email address";
        }

        if ($this->password === "") {
            $errors["password"] = "Password is required";
        }

        return $errors;
    }

    public function hasErrors(string $allowedDomain): bool
    {
        return !empty($this->validate($allowedDomain));
    }
}
