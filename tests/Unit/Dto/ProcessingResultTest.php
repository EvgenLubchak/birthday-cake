<?php

declare(strict_types=1);

namespace Tests\Unit\Dto;

use App\Dto\ProcessingResult;
use App\Model\SimpleCakeDay;
use PHPUnit\Framework\TestCase;

final class ProcessingResultTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        // Arrange
        $cakeDays = [
            new SimpleCakeDay(strtotime('2024-01-15'), 1, 0, ['John']),
            new SimpleCakeDay(strtotime('2024-06-20'), 0, 1, ['Jane', 'Bob'])
        ];

        $result = new ProcessingResult(
            cakeDays: $cakeDays,
            totalEmployeesProcessed: 100,
            processingTimeSeconds: 1.25,
            memoryUsageBytes: 52428800, // 50MB
            totalSmallCakes: 5,
            totalLargeCakes: 3
        );

        // Act & Assert
        $this->assertSame($cakeDays, $result->cakeDays);
        $this->assertEquals(100, $result->totalEmployeesProcessed);
        $this->assertEquals(1.25, $result->processingTimeSeconds);
        $this->assertEquals(52428800, $result->memoryUsageBytes);
        $this->assertEquals(5, $result->totalSmallCakes);
        $this->assertEquals(3, $result->totalLargeCakes);
    }

    public function testGetTotalCakeDays(): void
    {
        // Arrange
        $cakeDays = [
            new SimpleCakeDay(strtotime('2024-01-15'), 1, 0, ['John']),
            new SimpleCakeDay(strtotime('2024-06-20'), 0, 1, ['Jane'])
        ];

        $result = new ProcessingResult($cakeDays, 0, 0.0, 0, 0, 0);

        // Act
        $total = $result->getTotalCakeDays();

        // Assert
        $this->assertEquals(2, $total);
    }

    public function testGetMemoryUsageMB(): void
    {
        // Arrange
        $result = new ProcessingResult([], 0, 0.0, 52428800, 0, 0); // 50MB

        // Act
        $memoryMB = $result->getMemoryUsageMB();

        // Assert
        $this->assertEquals(50.0, $memoryMB);
    }

    public function testGetFormattedProcessingTime(): void
    {
        // Arrange
        $result = new ProcessingResult([], 0, 1.23456, 0, 0, 0);

        // Act
        $formatted = $result->getFormattedProcessingTime();

        // Assert
        $this->assertEquals('1.23 seconds', $formatted);
    }
}
