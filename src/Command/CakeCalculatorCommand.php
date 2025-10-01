<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\BatchProcessor;
use App\Service\CommandOutputService;
use App\Service\FileService;
use App\Service\SimpleCsvExporter;
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
        private readonly BatchProcessor $batchProcessor = new BatchProcessor(),
        private readonly SimpleCsvExporter $csvExporter = new SimpleCsvExporter(),
        private readonly CommandOutputService $outputService = new CommandOutputService(),
        private readonly FileService $fileService = new FileService()
    ) {
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
        ini_set('memory_limit', '512M');

        try {
            $inputFile = $input->getArgument('input-file');
            $outputFile = $input->getArgument('output-file');
            $year = (int) $input->getOption('year');

            $io->title('Cake Day Calculator (Optimized for Large Files with Streaming Support)');

            // Validate input file using FileService
            $fileInfo = $this->fileService->validateInputFile($inputFile);
            $io->text(sprintf('Input file size: %.2f MB', $fileInfo->getSizeMB()));

            // Ensure output directory exists
            $this->fileService->ensureOutputDirectory($outputFile);

            $io->section('Processing employee data with streaming...');
            $io->text(sprintf('Processing employees and calculating cake days for year %d...', $year));
            $io->newLine();

            // Process data using BatchProcessor - returns ProcessingResult DTO
            $processingResult = $this->batchProcessor->processInBatches($inputFile, $year, $io);

            if ($processingResult->getTotalCakeDays() === 0) {
                $io->warning('No cake days calculated for the given year.');
                return Command::SUCCESS;
            }

            $io->success(sprintf('Calculated %d cake days', $processingResult->getTotalCakeDays()));

            // Display results
            $this->outputService->displayResults($processingResult->cakeDays, $io);

            // Export results
            $this->outputService->exportWithProgress($this->csvExporter, $processingResult->cakeDays, $outputFile, $io);

            // Display summary (using array from DTO and file size)
            $this->outputService->displaySummary($processingResult->cakeDays, $fileInfo->getSizeMB(), $io);

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
}
