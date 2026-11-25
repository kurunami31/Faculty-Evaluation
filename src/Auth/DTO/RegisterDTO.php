<?php

declare(strict_types=1);

namespace App\Auth\DTO;

use App\Shared\Validation;

final readonly class RegisterDTO
{
    public function __construct(
        public string $employeeId,
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $department,
        public string $password,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            employeeId: trim($data["employee_id"] ?? ""),
            email: strtolower(trim($data["email"] ?? "")),
            firstName: trim($data["first_name"] ?? ""),
            lastName: trim($data["last_name"] ?? ""),
            department: trim($data["department"] ?? ""),
            password: $data["password"] ?? "",
        );
    }

    public function validate(array $allowedDepartments, string $allowedDomain): array
    {
        $errors = [];

        if (!Validation::employeeId($this->employeeId)) {
            $errors["employee_id"] = "Employee ID must be in format XXXX-YYYY (e.g., 2024-0001)";
        }

        if (!Validation::emailDomain($this->email, $allowedDomain)) {
            $errors["email"] = "Email must be a valid @" . $allowedDomain . " address";
        }

        if (!Validation::name($this->firstName)) {
            $errors["first_name"] = "First name is required (max 100 characters, letters only)";
        }

        if (!Validation::name($this->lastName)) {
            $errors["last_name"] = "Last name is required (max 100 characters, letters only)";
        }

        if (!Validation::department($this->department, $allowedDepartments)) {
            $errors["department"] = "Please select a valid department";
        }

        $passwordErrors = Validation::password($this->password);
        if (!empty($passwordErrors)) {
            $errors["password"] = $passwordErrors;
        }

        return $errors;
    }

    public function hasErrors(array $allowedDepartments, string $allowedDomain): bool
    {
        return !empty($this->validate($allowedDepartments, $allowedDomain));
    }
}
