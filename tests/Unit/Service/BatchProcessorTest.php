<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Dto\ProcessingResult;
use App\Interface\CakeDayCalculatorInterface;
use App\Interface\EmployeeParserInterface;
use App\Interface\TempFileManagerInterface;
use App\Model\Employee;
use App\Model\SimpleCakeDay;
use App\Service\BatchProcessor;
use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

final class BatchProcessorTest extends TestCase
{
    private BatchProcessor $batchProcessor;
    private MockObject|EmployeeParserInterface $mockEmployeeParser;
    private MockObject|CakeDayCalculatorInterface $mockCakeDayCalculator;
    private MockObject|TempFileManagerInterface $mockTempFileManager;
    private SymfonyStyle $io;

    protected function setUp(): void
    {
        $this->mockEmployeeParser = $this->createMock(EmployeeParserInterface::class);
        $this->mockCakeDayCalculator = $this->createMock(CakeDayCalculatorInterface::class);
        $this->mockTempFileManager = $this->createMock(TempFileManagerInterface::class);

        // Use a real SymfonyStyle that writes to NullOutput to avoid ProgressBar mocking
        $this->io = new SymfonyStyle(new ArrayInput([]), new NullOutput());

        $this->batchProcessor = new BatchProcessor(
            $this->mockEmployeeParser,
            $this->mockCakeDayCalculator,
            $this->mockTempFileManager
        );
    }

    public function testProcessInBatchesReturnsProcessingResult(): void
    {
        // Arrange
        $employees = [
            new Employee('John Doe', Carbon::create(1990, 1, 15)),
            new Employee('Jane Smith', Carbon::create(1985, 6, 20))
        ];

        $cakeDays = [
            new SimpleCakeDay(strtotime('2024-01-15'), 1, 0, ['John Doe']),
            new SimpleCakeDay(strtotime('2024-06-20'), 0, 1, ['Jane Smith'])
        ];

        $this->mockEmployeeParser
            ->method('parseInBatches')
            ->willReturnCallback(function() use ($employees) {
                if (false) { yield []; }
                yield $employees;
            });

        $this->mockCakeDayCalculator
            ->method('calculateCakeDays')
            ->with($employees, 2024)
            ->willReturn($cakeDays);

        $this->mockTempFileManager
            ->method('create')
            ->willReturn('/tmp/test_file');

        $this->mockTempFileManager
            ->method('consolidateResults')
            ->willReturn($cakeDays);

        // Act
        $result = $this->batchProcessor->processInBatches('/path/to/file.txt', 2024, $this->io);

        // Assert
        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertEquals($cakeDays, $result->cakeDays);
        $this->assertEquals(2, $result->totalEmployeesProcessed);
        $this->assertEquals(1, $result->totalSmallCakes);
        $this->assertEquals(1, $result->totalLargeCakes);
        $this->assertEquals(2, $result->getTotalCakeDays());
    }

    public function testProcessInBatchesHandlesEmptyResults(): void
    {
        // Arrange
        $this->mockEmployeeParser
            ->method('parseInBatches')
            ->willReturnCallback(function() {
                if (false) { yield []; }
            });

        $this->mockTempFileManager
            ->method('create')
            ->willReturn('/tmp/test_file');

        $this->mockTempFileManager
            ->method('consolidateResults')
            ->willReturn([]);

        // Act
        $result = $this->batchProcessor->processInBatches('/path/to/file.txt', 2024, $this->io);

        // Assert
        $this->assertInstanceOf(ProcessingResult::class, $result);
        $this->assertEmpty($result->cakeDays);
        $this->assertEquals(0, $result->totalEmployeesProcessed);
        $this->assertEquals(0, $result->totalSmallCakes);
        $this->assertEquals(0, $result->totalLargeCakes);
    }

    public function testProcessInBatchesCallsTempFileManagerMethods(): void
    {
        // Arrange
        $tempFile = '/tmp/test_file';
        $employees = [new Employee('John', Carbon::create(1990, 1, 15))];
        $cakeDays = [new SimpleCakeDay(strtotime('2024-01-15'), 1, 0, ['John'])];

        $this->mockEmployeeParser
            ->method('parseInBatches')
            ->willReturnCallback(function() use ($employees) {
                if (false) { yield []; }
                yield $employees;
            });

        $this->mockCakeDayCalculator
            ->method('calculateCakeDays')
            ->willReturn($cakeDays);

        $this->mockTempFileManager
            ->expects($this->once())
            ->method('create')
            ->with('cake_days_')
            ->willReturn($tempFile);

        $this->mockTempFileManager
            ->expects($this->once())
            ->method('writeBatch')
            ->with($tempFile, $cakeDays);

        $this->mockTempFileManager
            ->expects($this->once())
            ->method('consolidateResults')
            ->with($tempFile)
            ->willReturn($cakeDays);

        $this->mockTempFileManager
            ->expects($this->once())
            ->method('cleanup')
            ->with($tempFile);

        // Act
        $this->batchProcessor->processInBatches('/path/to/file.txt', 2024, $this->io);
    }
}
