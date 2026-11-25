<?php

declare(strict_types=1);

use App\Shared\Validation;

require_once __DIR__ . "/../../vendor/autoload.php";

final class ValidationTest extends \PHPUnit\Framework\TestCase
{
    public function testEmployeeIdValid(): void
    {
        $this->assertTrue(Validation::employeeId("2024-0001"));
        $this->assertTrue(Validation::employeeId("1999-9999"));
    }

    public function testEmployeeIdInvalid(): void
    {
        $this->assertFalse(Validation::employeeId("20240001"));
        $this->assertFalse(Validation::employeeId("2024-1"));
        $this->assertFalse(Validation::employeeId("abcd-1234"));
        $this->assertFalse(Validation::employeeId(""));
        $this->assertFalse(Validation::employeeId("2024-0001-extra"));
    }

    public function testEmailDomainValid(): void
    {
        $this->assertTrue(Validation::emailDomain("faculty@dorsu.edu.ph", "dorsu.edu.ph"));
        $this->assertTrue(Validation::emailDomain("FACULTY@DORSU.EDU.PH", "dorsu.edu.ph"));
    }

    public function testEmailDomainInvalid(): void
    {
        $this->assertFalse(Validation::emailDomain("faculty@gmail.com", "dorsu.edu.ph"));
        $this->assertFalse(Validation::emailDomain("faculty@dorsu.edu.com", "dorsu.edu.ph"));
        $this->assertFalse(Validation::emailDomain("not-an-email", "dorsu.edu.ph"));
        $this->assertFalse(Validation::emailDomain("", "dorsu.edu.ph"));
    }

    public function testNameValid(): void
    {
        $this->assertTrue(Validation::name("Juan"));
        $this->assertTrue(Validation::name("Maria Dela Cruz"));
        $this->assertTrue(Validation::name("José"));
    }

    public function testNameInvalid(): void
    {
        $this->assertFalse(Validation::name(""));
        $this->assertFalse(Validation::name("Juan123"));
        $this->assertFalse(Validation::name(str_repeat("a", 101)));
    }

    public function testDepartmentValid(): void
    {
        $depts = ["Faculty of Computing, Engineering, and Technology", "Faculty of Nursing and Allied Health Sciences"];
        $this->assertTrue(Validation::department("Faculty of Computing, Engineering, and Technology", $depts));
    }

    public function testDepartmentInvalid(): void
    {
        $depts = ["Faculty of Computing, Engineering, and Technology", "Faculty of Nursing and Allied Health Sciences"];
        $this->assertFalse(Validation::department("Unknown College", $depts));
        $this->assertFalse(Validation::department("", $depts));
    }

    public function testPasswordValid(): void
    {
        $this->assertEmpty(Validation::password("SecurePass123!"));
        $this->assertEmpty(Validation::password("MyStr@ngP@ss1"));
    }

    public function testPasswordTooShort(): void
    {
        $errors = Validation::password("Short1!");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("at least 12 characters", $errors[0]);
    }

    public function testPasswordMissingUppercase(): void
    {
        $errors = Validation::password("securepass123!");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("uppercase", $errors[0]);
    }

    public function testPasswordMissingLowercase(): void
    {
        $errors = Validation::password("SECUREPASS123!");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("lowercase", $errors[0]);
    }

    public function testPasswordMissingDigit(): void
    {
        $errors = Validation::password("SecurePassword!");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("digit", $errors[0]);
    }

    public function testPasswordMissingSpecial(): void
    {
        $errors = Validation::password("SecurePass123");
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("special", $errors[0]);
    }
}
