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

    private readonly EmployeeParser $employeeParser;
    private readonly CakeDayCalculator $cakeDayCalculator;
    private readonly CsvExporter $csvExporter;

    public function __construct()
    {
        parent::__construct();

        // Initialize services
        $holidayService = new HolidayService();
        $this->employeeParser = new EmployeeParser();
        $this->cakeDayCalculator = new CakeDayCalculator($holidayService);
        $this->csvExporter = new CsvExporter();
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

        try {
            // Get arguments and options
            $inputFile = $input->getArgument('input-file');
            $outputFile = $input->getArgument('output-file');
            $year = (int) $input->getOption('year');

            $io->title('Cake Day Calculator');
            $io->section('Processing employee data...');

            // Parse employees from input file
            $employees = $this->employeeParser->parseFromFile($inputFile);
            $io->success(sprintf('Loaded %d employees from %s', count($employees), $inputFile));

            // Display employee list
            if ($io->isVerbose()) {
                $io->section('Employees:');
                foreach ($employees as $employee) {
                    $io->text(sprintf(
                        '• %s (born %s)',
                        $employee->name,
                        $employee->dateOfBirth->format('Y-m-d')
                    ));
                }
            }

            // Calculate cake days
            $io->section(sprintf('Calculating cake days for year %d...', $year));
            $cakeDays = $this->cakeDayCalculator->calculateCakeDays($employees, $year);

            if (empty($cakeDays)) {
                $io->warning('No cake days calculated for the given year.');
                return Command::SUCCESS;
            }

            $io->success(sprintf('Calculated %d cake days', count($cakeDays)));

            // Display cake days
            if ($io->isVerbose()) {
                $io->section('Cake Days:');
                foreach ($cakeDays as $cakeDay) {
                    $cakeType = $cakeDay->largeCakes > 0 ? 'Large' : 'Small';
                    $io->text(sprintf(
                        '• %s: %s cake for %s',
                        $cakeDay->date->format('Y-m-d (l)'),
                        $cakeType,
                        implode(', ', $cakeDay->getEmployeeNames())
                    ));
                }
            }

            // Export to CSV
            $io->section('Exporting to CSV...');
            $this->csvExporter->exportToFile($cakeDays, $outputFile);
            $io->success(sprintf('Cake days exported to %s', $outputFile));

            // Summary
            $totalSmallCakes = array_sum(array_map(fn($cd) => $cd->smallCakes, $cakeDays));
            $totalLargeCakes = array_sum(array_map(fn($cd) => $cd->largeCakes, $cakeDays));

            $io->section('Summary:');
            $io->definitionList(
                ['Total cake days' => count($cakeDays)],
                ['Small cakes' => $totalSmallCakes],
                ['Large cakes' => $totalLargeCakes],
                ['Total employees with cakes' => count($employees)]
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
}
