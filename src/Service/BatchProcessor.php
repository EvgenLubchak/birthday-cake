<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Service for processing large files in batches with progress tracking
 */
final class BatchProcessor
{
    public function __construct(
        private readonly EmployeeParser $employeeParser = new EmployeeParser(),
        private readonly CakeDayCalculator $cakeDayCalculator = new CakeDayCalculator(new HolidayService()),
        private readonly TempFileManager $tempFileManager = new TempFileManager()
    ) {}

    /**
     * Process employees in batches with progress tracking
     */
    public function processInBatches(string $inputFile, int $year, SymfonyStyle $io): array
    {
        $tempFile = $this->tempFileManager->create('cake_days_');
        $progressBar = $this->createProgressBar($io);

        $processedCount = 0;
        $totalCount = 0;

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

        // Clean up temp file
        $this->tempFileManager->cleanup($tempFile);

        return $finalResults;
    }

    /**
     * Create progress bar for batch processing
     */
    private function createProgressBar(SymfonyStyle $io): \Symfony\Component\Console\Helper\ProgressBar
    {
        $progressBar = $io->createProgressBar();
        $progressBar->setFormat(' %current% employees processed [%bar%] %elapsed%');
        $progressBar->start();
        return $progressBar;
    }
}
