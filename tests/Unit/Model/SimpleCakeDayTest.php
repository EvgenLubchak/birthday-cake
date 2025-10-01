<?php

declare(strict_types=1);

namespace Tests\Unit\Model;

use App\Model\SimpleCakeDay;
use PHPUnit\Framework\TestCase;

final class SimpleCakeDayTest extends TestCase
{
    public function testConstructorAndProperties(): void
    {
        // Arrange
        $timestamp = strtotime('2024-01-15 09:00:00');
        $employeeNames = ['John Doe', 'Jane Smith'];

        // Act
        $cakeDay = new SimpleCakeDay(
            timestamp: $timestamp,
            smallCakes: 1,
            largeCakes: 0,
            employeeNames: $employeeNames
        );

        // Assert
        $this->assertEquals($timestamp, $cakeDay->timestamp);
        $this->assertEquals(1, $cakeDay->smallCakes);
        $this->assertEquals(0, $cakeDay->largeCakes);
        $this->assertEquals($employeeNames, $cakeDay->employeeNames);
    }

    public function testGetFormattedDate(): void
    {
        // Arrange
        $timestamp = strtotime('2024-01-15 09:00:00');
        $cakeDay = new SimpleCakeDay($timestamp, 1, 0, ['John']);

        // Act
        $formattedDate = $cakeDay->getFormattedDate();

        // Assert
        $this->assertEquals('2024-01-15', $formattedDate);
    }

    public function testWithDifferentTimezones(): void
    {
        // Arrange - Create timestamp for specific date
        $date = new \DateTime('2024-12-25', new \DateTimeZone('UTC'));
        $timestamp = $date->getTimestamp();

        $cakeDay = new SimpleCakeDay($timestamp, 0, 1, ['Santa']);

        // Act
        $formattedDate = $cakeDay->getFormattedDate();

        // Assert
        $this->assertEquals('2024-12-25', $formattedDate);
    }

    public function testWithMultipleEmployees(): void
    {
        // Arrange
        $timestamp = strtotime('2024-06-15');
        $employees = ['Alice Johnson', 'Bob Wilson', 'Charlie Brown'];

        // Act
        $cakeDay = new SimpleCakeDay($timestamp, 0, 1, $employees);

        // Assert
        $this->assertEquals($employees, $cakeDay->employeeNames);
        $this->assertEquals(0, $cakeDay->smallCakes);
        $this->assertEquals(1, $cakeDay->largeCakes);
    }
}
