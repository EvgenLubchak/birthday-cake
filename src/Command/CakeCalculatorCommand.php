<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\EmployeeParser;
use App\Service\CakeDayCalculator;
use App\Service\CsvExporter;
use App\Service\HolidayService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command for calculating cake days
 */
final class CakeCalculatorCommand extends Command
{
    protected static $defaultName = 'calculate-cakes';
    protected static $defaultDescription = 'Calculate cake days for employees based on their birthdays';

    public function __construct(
        private readonly EmployeeParser $employeeParser = new EmployeeParser(),
        private readonly CakeDayCalculator $cakeDayCalculator = new CakeDayCalculator(new HolidayService()),
        private readonly CsvExporter $csvExporter = new CsvExporter())
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'input-file',
                InputArgument::REQUIRED,
                'Path to the input file containing employee data'
            )
            ->addArgument(
                'output-file',
                InputArgument::REQUIRED,
                'Path to the output CSV file'
            )
            ->addOption(
                'year',
                'y',
                InputOption::VALUE_OPTIONAL,
                'Year to calculate cake days for',
                (string) date('Y')
            )
            ->setHelp(
                'This command calculates cake days for employees based on their birthdays and outputs a CSV file.' . PHP_EOL .
                'Input file format: [Person Name],[Date of Birth (yyyy-mm-dd)]' . PHP_EOL .
                'Example: Steve,1992-10-14'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Increase memory limit for very large file processing
        ini_set('memory_limit', '512M');

        try {
            // Get arguments and options
            $inputFile = $input->getArgument('input-file');
            $outputFile = $input->getArgument('output-file');
            $year = (int) $input->getOption('year');

            $io->title('Cake Day Calculator (Optimized for Large Files)');

            // Get file info
            $fileSize = filesize($inputFile);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            $io->text(sprintf('Input file size: %s MB', $fileSizeMB));

            $io->section('Processing employee data with streaming...');

            // Calculate cake days using streaming approach with dynamic progress
            $io->text(sprintf('Processing employees and calculating cake days for year %d...', $year));
            $io->newLine();

            $cakeDays = $this->processEmployeesInStreamWithProgress($inputFile, $year, $io);

            if (empty($cakeDays)) {
                $io->warning('No cake days calculated for the given year.');
                return Command::SUCCESS;
            }

            $io->success(sprintf('Calculated %d cake days', count($cakeDays)));

            // Export to CSV with progress
            $io->section('Exporting to CSV...');

            $exportProgress = $io->createProgressBar(count($cakeDays));
            $exportProgress->setFormat('verbose');

            $this->csvExporter->exportWithProgress($cakeDays, $outputFile, function ($processed, $total) use ($exportProgress) {
                $exportProgress->setProgress($processed);
            });

            $exportProgress->finish();
            $io->newLine(2);
            $io->success(sprintf('Cake days exported to %s', $outputFile));

            // Summary
            $totalSmallCakes = array_sum(array_map(fn($cd) => $cd->smallCakes, $cakeDays));
            $totalLargeCakes = array_sum(array_map(fn($cd) => $cd->largeCakes, $cakeDays));

            $io->section('Summary:');
            $io->definitionList(
                ['Input file size' => $fileSizeMB . ' MB'],
                //['Total employees processed' => number_format($totalEmployees)],
                ['Total cake days' => number_format(count($cakeDays))],
                ['Small cakes' => number_format($totalSmallCakes)],
                ['Large cakes' => number_format($totalLargeCakes)],
                ['Memory usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'],
                ['Processing time' => sprintf('%.2f seconds', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'])]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());

            if ($io->isVeryVerbose()) {
                $io->section('Stack trace:');
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Process employees with single file pass and dynamic progress bar
     */
    private function processEmployeesInStreamWithProgress(string $inputFile, int $year, $io): array
    {
        $processedCount = 0;
        $totalCount = 0;
        $tempFile = tempnam(sys_get_temp_dir(), 'cake_days_');
        $tempHandle = fopen($tempFile, 'w');

        if (!$tempHandle) {
            throw new \RuntimeException('Unable to create temporary file');
        }

        // Create progress bar with unknown total initially
        $progressBar = $io->createProgressBar();
        $progressBar->setFormat(' %current% employees processed [%bar%] %elapsed%');
        $progressBar->start();

        // Process in batches with progress updates
        foreach ($this->employeeParser->parseInBatches($inputFile, 100) as $batch) {
            // Calculate cake days for this batch only
            $batchCakeDays = $this->cakeDayCalculator->calculateCakeDays($batch, $year);

            // Write batch results to temp file immediately
            foreach ($batchCakeDays as $cakeDay) {
                $line = sprintf(
                    "%s|%d|%d|%s\n",
                    $cakeDay->date->getTimestamp(),
                    $cakeDay->smallCakes,
                    $cakeDay->largeCakes,
                    implode(',', array_map(fn($emp) => $emp->name, $cakeDay->employees))
                );
                fwrite($tempHandle, $line);
            }

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

        fclose($tempHandle);

        // Consolidate results
        $io->text('Consolidating cake days...');
        $finalResults = $this->consolidateFromTempFile($tempFile, $year);

        // Clean up temp file
        unlink($tempFile);

        return $finalResults;
    }

        /**
         * Consolidate results from temporary file WITHOUT any Carbon objects
         */
        private function consolidateFromTempFile(string $tempFile, int $year): array
        {
            $dateGroups = [];
            $handle = fopen($tempFile, 'r');

            if (!$handle) {
                throw new \RuntimeException('Unable to open temporary file for reading');
            }

            $lineCount = 0;

            // Read and group by timestamp (NO Carbon objects at all)
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) continue;

                $parts = explode('|', $line, 4);
                if (count($parts) !== 4) continue;

                [$timestamp, $smallCakes, $largeCakes, $employeeNames] = $parts;

                if (!isset($dateGroups[$timestamp])) {
                    $dateGroups[$timestamp] = [];
                }

                // Just collect names without ANY objects
                $names = !empty($employeeNames) ? explode(',', $employeeNames) : [];
                foreach ($names as $name) {
                    $dateGroups[$timestamp][] = $name;
                }

                $lineCount++;

                // Very frequent cleanup
                if ($lineCount % 1000 === 0) {
                    gc_collect_cycles();
                }
            }

            fclose($handle);

            // Create lightweight SimpleCakeDay objects (NO Carbon yet)
            $simpleCakeDays = [];
            $processedGroups = 0;

            foreach ($dateGroups as $timestamp => $employeeNames) {
                $employeeCount = count($employeeNames);

                $simpleCakeDays[] = new \App\Model\SimpleCakeDay(
                    (int)$timestamp,
                    $employeeCount >= 2 ? 0 : 1,  // small cakes
                    $employeeCount >= 2 ? 1 : 0,  // large cakes
                    $employeeNames
                );

                $processedGroups++;

                // Very aggressive cleanup
                if ($processedGroups % 50 === 0) {
                    gc_collect_cycles();
                }
            }

            // Clear all intermediate data
            unset($dateGroups, $timestamp, $employeeNames);
            gc_collect_cycles();

            // Sort by timestamp (no Carbon needed)
            usort($simpleCakeDays, static fn($a, $b) => $a->timestamp <=> $b->timestamp);

            // ONLY NOW convert to Carbon objects for final output (in small batches)
            $finalResults = [];
            $batchSize = 50; // Very small batches for Carbon conversion
            $batches = array_chunk($simpleCakeDays, $batchSize);

            foreach ($batches as $batch) {
                foreach ($batch as $simpleCakeDay) {
                    try {
                        $finalResults[] = $simpleCakeDay->toCakeDay();
                    } catch (\Exception $e) {
                        // Skip invalid dates
                        continue;
                    }
                }

                // Clear batch
                unset($batch);
                gc_collect_cycles();
            }

            // Clear simple cake days
            unset($simpleCakeDays, $batches);
            gc_collect_cycles();

            return $finalResults;
        }

}
