<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Model\Employee;
use App\Service\EmployeeParser;
use PHPUnit\Framework\TestCase;

final class EmployeeParserTest extends TestCase
{
    private EmployeeParser $parser;
    private string $tempFile;

    protected function setUp(): void
    {
        $this->parser = new EmployeeParser();
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test_employees_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testParseFromFileStreamWithValidData(): void
    {
        // Arrange
        $content = "John Doe,1990-01-15\nJane Smith,1985-06-20\nBob Johnson,1992-12-05";
        file_put_contents($this->tempFile, $content);

        // Act
        $employees = iterator_to_array($this->parser->parseFromFileStream($this->tempFile));

        // Assert
        $this->assertCount(3, $employees);
        $this->assertInstanceOf(Employee::class, $employees[0]);
        $this->assertEquals('John Doe', $employees[0]->name);
        $this->assertEquals('1990-01-15', $employees[0]->dateOfBirth->format('Y-m-d'));
    }

    public function testParseFromFileStreamSkipsEmptyLines(): void
    {
        // Arrange
        $content = "John Doe,1990-01-15\n\n# This is a comment\nJane Smith,1985-06-20";
        file_put_contents($this->tempFile, $content);

        // Act
        $employees = iterator_to_array($this->parser->parseFromFileStream($this->tempFile));

        // Assert
        $this->assertCount(2, $employees);
        $this->assertEquals('John Doe', $employees[0]->name);
        $this->assertEquals('Jane Smith', $employees[1]->name);
    }

    public function testParseFromFileStreamThrowsExceptionForNonExistentFile(): void
    {
        // Arrange
        $nonExistentFile = '/path/to/non/existent/file.txt';

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("File does not exist: {$nonExistentFile}");

        iterator_to_array($this->parser->parseFromFileStream($nonExistentFile));
    }

    public function testParseFromFileStreamThrowsExceptionForInvalidDateFormat(): void
    {
        // Arrange
        $content = "John Doe,invalid-date-format";
        file_put_contents($this->tempFile, $content);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Error on line 1: Invalid date format: invalid-date-format. Expected Y-m-d format.');

        iterator_to_array($this->parser->parseFromFileStream($this->tempFile));
    }

    public function testParseFromFileStreamThrowsExceptionForInvalidCsvFormat(): void
    {
        // Arrange
        $content = "John Doe"; // Missing comma and date
        file_put_contents($this->tempFile, $content);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Error on line 1: Invalid CSV line format: John Doe');

        iterator_to_array($this->parser->parseFromFileStream($this->tempFile));
    }

    public function testParseInBatchesWithSmallBatchSize(): void
    {
        // Arrange
        $content = "Employee1,1990-01-01\nEmployee2,1991-01-01\nEmployee3,1992-01-01";
        file_put_contents($this->tempFile, $content);

        // Act
        $batches = iterator_to_array($this->parser->parseInBatches($this->tempFile, 2));

        // Assert
        $this->assertCount(2, $batches); // 2 batches: [2 employees], [1 employee]
        $this->assertCount(2, $batches[0]); // First batch has 2 employees
        $this->assertCount(1, $batches[1]); // Second batch has 1 employee
    }

    public function testParseInBatchesWithLargeBatchSize(): void
    {
        // Arrange
        $content = "Employee1,1990-01-01\nEmployee2,1991-01-01";
        file_put_contents($this->tempFile, $content);

        // Act
        $batches = iterator_to_array($this->parser->parseInBatches($this->tempFile, 10));

        // Assert
        $this->assertCount(1, $batches); // Only 1 batch
        $this->assertCount(2, $batches[0]); // Batch contains all 2 employees
    }
}
