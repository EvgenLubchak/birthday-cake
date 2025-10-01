<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Model\Employee;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    public function testConstructorAndProperties(): void
    {
        // Arrange
        $name = 'John Doe';
        $birthDate = Carbon::create(1990, 1, 15);

        // Act
        $employee = new Employee($name, $birthDate);

        // Assert
        $this->assertEquals($name, $employee->name);
        $this->assertEquals($birthDate, $employee->dateOfBirth);
    }

    public function testGetBirthdayForYear(): void
    {
        // Arrange
        $birthDate = Carbon::create(1990, 6, 15); // June 15, 1990
        $employee = new Employee('John Doe', $birthDate);

        // Act
        $birthday2024 = $employee->getBirthdayForYear(2024);

        // Assert
        $this->assertEquals(2024, $birthday2024->year);
        $this->assertEquals(6, $birthday2024->month);
        $this->assertEquals(15, $birthday2024->day);
        $this->assertEquals('2024-06-15', $birthday2024->format('Y-m-d'));
    }

    public function testFromCsvLineWithValidData(): void
    {
        // Arrange
        $csvLine = 'John Doe,1990-01-15';

        // Act
        $employee = Employee::fromCsvLine($csvLine);

        // Assert
        $this->assertEquals('John Doe', $employee->name);
        $this->assertEquals('1990-01-15', $employee->dateOfBirth->format('Y-m-d'));
    }

    public function testFromCsvLineWithWhitespace(): void
    {
        // Arrange
        $csvLine = ' John Doe , 1990-01-15 ';

        // Act
        $employee = Employee::fromCsvLine($csvLine);

        // Assert
        $this->assertEquals('John Doe', $employee->name);
        $this->assertEquals('1990-01-15', $employee->dateOfBirth->format('Y-m-d'));
    }

    public function testFromCsvLineThrowsExceptionForInvalidFormat(): void
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CSV line format: John Doe');

        Employee::fromCsvLine('John Doe');
    }

    public function testFromCsvLineThrowsExceptionForEmptyName(): void
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Employee name cannot be empty');

        Employee::fromCsvLine(',1990-01-15');
    }

    public function testFromCsvLineThrowsExceptionForInvalidDate(): void
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format: invalid-date. Expected Y-m-d format.');

        Employee::fromCsvLine('John Doe,invalid-date');
    }

    public function testFromCsvLineThrowsExceptionForPartiallyValidDate(): void
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format: 1990-13-32. Expected Y-m-d format.');

        Employee::fromCsvLine('John Doe,1990-13-32'); // Invalid month and day
    }

    public function testGetBirthdayForYearPreservesTimezone(): void
    {
        // Arrange
        $timezone = new \DateTimeZone('Europe/London');
        $birthDate = Carbon::create(1990, 6, 15, 0, 0, 0, $timezone);
        $employee = new Employee('John Doe', $birthDate);

        // Act
        $birthday2024 = $employee->getBirthdayForYear(2024);

        // Assert
        $this->assertEquals($timezone->getName(), $birthday2024->timezone->getName());
    }
}
