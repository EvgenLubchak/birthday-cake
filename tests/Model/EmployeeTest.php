<?php

declare(strict_types=1);

namespace Tests\Model;

use App\Model\Employee;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    public function testEmployeeCreation(): void
    {
        $name = 'John Doe';
        $dateOfBirth = Carbon::parse('1990-05-15');

        $employee = new Employee($name, $dateOfBirth);

        $this->assertEquals($name, $employee->name);
        $this->assertTrue($dateOfBirth->equalTo($employee->dateOfBirth));
    }

    public function testGetBirthdayForYear(): void
    {
        $employee = new Employee('Test', Carbon::parse('1990-06-15'));

        $birthday2024 = $employee->getBirthdayForYear(2024);
        $birthday2025 = $employee->getBirthdayForYear(2025);

        $this->assertEquals('2024-06-15', $birthday2024->format('Y-m-d'));
        $this->assertEquals('2025-06-15', $birthday2025->format('Y-m-d'));
    }

    public function testFromCsvLineValid(): void
    {
        $csvLine = 'Steve,1992-10-14';
        $employee = Employee::fromCsvLine($csvLine);

        $this->assertEquals('Steve', $employee->name);
        $this->assertEquals('1992-10-14', $employee->dateOfBirth->format('Y-m-d'));
    }

    public function testFromCsvLineWithWhitespace(): void
    {
        $csvLine = ' John Doe , 1985-12-25 ';
        $employee = Employee::fromCsvLine($csvLine);

        $this->assertEquals('John Doe', $employee->name);
        $this->assertEquals('1985-12-25', $employee->dateOfBirth->format('Y-m-d'));
    }

    public function testFromCsvLineInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CSV line format');

        Employee::fromCsvLine('InvalidLine');
    }

    public function testFromCsvLineEmptyName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Employee name cannot be empty');

        Employee::fromCsvLine(',1990-01-01');
    }

    public function testFromCsvLineInvalidDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');

        Employee::fromCsvLine('John,invalid-date');
    }
}
