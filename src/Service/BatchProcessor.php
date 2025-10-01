<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ProcessingResult;
use App\Interface\CakeDayCalculatorInterface;
use App\Interface\EmployeeParserInterface;
use App\Interface\TempFileManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Service for processing large files in batches with progress tracking
 */
final class BatchProcessor
{
    public function __construct(
        private readonly EmployeeParserInterface $employeeParser = new EmployeeParser(),
        private readonly CakeDayCalculatorInterface $cakeDayCalculator = new CakeDayCalculator(new HolidayService()),
        private readonly TempFileManagerInterface $tempFileManager = new TempFileManager()
    ) {}

    /**
     * Process employees in batches with progress tracking
     */
    public function processInBatches(string $inputFile, int $year, SymfonyStyle $io): ProcessingResult
    {
        $startTime = microtime(true);
        $progressBar = $this->createProgressBar($io);

        $processedCount = 0;
        $totalCount = 0;
        $tempFile = null;

        try {
            // Create temp file inside try to ensure cleanup in finally
            $tempFile = $this->tempFileManager->create('cake_days_');

            // Process in batches with progress updates
            foreach ($this->employeeParser->parseInBatches($inputFile, 100) as $batch) {
                // Calculate cake days for this batch only
                $batchCakeDays = $this->cakeDayCalculator->calculateCakeDays($batch, $year);

                // Write batch results to temp file immediately
                $this->tempFileManager->writeBatch($tempFile, $batchCakeDays);

                // Update progress
                $batchSize = count($batch);
                $processedCount += $batchSize;
                $totalCount += $batchSize;

                $progressBar->advance($batchSize);

                // Show progress info
                if ($processedCount % 1000 === 0) {
                    $progressBar->setMessage(sprintf(' [%s processed]', number_format($processedCount)));
                }

                // Aggressive cleanup
                unset($batch, $batchCakeDays);

                // Garbage collection
                if ($processedCount % 500 === 0) {
                    gc_collect_cycles();
                }
            }

            $progressBar->finish();
            $io->newLine(2);
            $io->success(sprintf('Processed %s employees', number_format($totalCount)));

            // Consolidate results
            $io->text('Consolidating cake days...');
            $finalResults = $this->tempFileManager->consolidateResults($tempFile);

            // Compute totals
            $totalSmallCakes = array_sum(array_map(static fn($cd) => $cd->smallCakes, $finalResults));
            $totalLargeCakes = array_sum(array_map(static fn($cd) => $cd->largeCakes, $finalResults));

            $processingTimeSeconds = microtime(true) - $startTime;
            $memoryUsageBytes = memory_get_peak_usage(true);

            return new ProcessingResult(
                cakeDays: $finalResults,
                totalEmployeesProcessed: $totalCount,
                processingTimeSeconds: $processingTimeSeconds,
                memoryUsageBytes: $memoryUsageBytes,
                totalSmallCakes: $totalSmallCakes,
                totalLargeCakes: $totalLargeCakes
            );
        } finally {
            // Ensure temp file is always cleaned up even if exceptions occur
            if ($tempFile !== null) {
                $this->tempFileManager->cleanup($tempFile);
            }
        }
    }

    /**
     * Create progress bar for batch processing
     */
    private function createProgressBar(SymfonyStyle $io)
    {
        $progressBar = $io->createProgressBar();
        $progressBar->setFormat(' %current% employees processed [%bar%] %elapsed%');
        $progressBar->start();
        return $progressBar;
    }
}
