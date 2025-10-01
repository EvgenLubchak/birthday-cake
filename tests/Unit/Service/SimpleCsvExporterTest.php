<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Model\SimpleCakeDay;
use App\Service\SimpleCsvExporter;
use PHPUnit\Framework\TestCase;

final class SimpleCsvExporterTest extends TestCase
{
    private SimpleCsvExporter $exporter;
    private string $tempFile;

    protected function setUp(): void
    {
        $this->exporter = new SimpleCsvExporter();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_export_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testExportWithProgressCreatesValidCsvFile(): void
    {
        // Arrange
        $cakeDays = [
            new SimpleCakeDay(strtotime('2024-01-15'), 1, 0, ['John Doe']),
            new SimpleCakeDay(strtotime('2024-06-20'), 0, 1, ['Jane Smith', 'Bob Johnson'])
        ];

        // Act
        $this->exporter->exportWithProgress($cakeDays, $this->tempFile);

        // Assert
        $this->assertFileExists($this->tempFile);

        $content = file_get_contents($this->tempFile);
        $lines = explode("\n", trim($content));

        $this->assertCount(3, $lines); // Header + 2 data rows
        $this->assertStringContainsString('Date,"Small Cakes","Large Cakes",Employees', $lines[0]);
        $this->assertStringContainsString('2024-01-15,1,0,"John Doe"', $lines[1]);
        $this->assertStringContainsString('2024-06-20,0,1,"Jane Smith, Bob Johnson"', $lines[2]);
    }

    public function testExportWithProgressCallsProgressCallback(): void
    {
        // Arrange
        $cakeDays = [
            new SimpleCakeDay(strtotime('2024-01-15'), 1, 0, ['John Doe']),
            new SimpleCakeDay(strtotime('2024-06-20'), 0, 1, ['Jane Smith'])
        ];

        $progressCalls = [];
        $progressCallback = function (int $processed, int $total) use (&$progressCalls) {
            $progressCalls[] = [$processed, $total];
        };

        // Act
        $this->exporter->exportWithProgress($cakeDays, $this->tempFile, $progressCallback);

        // Assert
        $this->assertCount(2, $progressCalls);
        $this->assertEquals([1, 2], $progressCalls[0]);
        $this->assertEquals([2, 2], $progressCalls[1]);
    }

    public function testExportWithProgressWorksWithoutProgressCallback(): void
    {
        // Arrange
        $cakeDays = [
            new SimpleCakeDay(strtotime('2024-01-15'), 1, 0, ['John Doe'])
        ];

        // Act & Assert - Should not throw exception
        $this->exporter->exportWithProgress($cakeDays, $this->tempFile, null);

        $this->assertFileExists($this->tempFile);
    }

    public function testExportWithProgressThrowsExceptionForInvalidPath(): void
    {
        // Arrange
        $cakeDays = [new SimpleCakeDay(strtotime('2024-01-15'), 1, 0, ['John'])];
        $invalidPath = '/invalid/path/file.csv';

        // Act & Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cannot create output file: {$invalidPath}");

        $this->exporter->exportWithProgress($cakeDays, $invalidPath);
    }

    public function testExportWithProgressHandlesEmptyArray(): void
    {
        // Arrange
        $cakeDays = [];

        // Act
        $this->exporter->exportWithProgress($cakeDays, $this->tempFile);

        // Assert
        $this->assertFileExists($this->tempFile);

        $content = file_get_contents($this->tempFile);
        $lines = explode("\n", trim($content));

        $this->assertCount(1, $lines); // Only header
        $this->assertStringContainsString('Date,"Small Cakes","Large Cakes",Employees', $lines[0]);
    }
}
