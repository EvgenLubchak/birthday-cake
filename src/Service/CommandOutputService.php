<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Service for handling command output and display
 */
final class CommandOutputService
{
    /**
     * Display cake day results in verbose mode
     */
    public function displayResults(array $cakeDays, SymfonyStyle $io): void
    {
        if (!$io->isVerbose()) {
            return;
        }

        $displayLimit = min(10, count($cakeDays));
        $io->section(sprintf('First %d cake days:', $displayLimit));

        for ($i = 0; $i < $displayLimit; $i++) {
            $cakeDay = $cakeDays[$i];
            $cakeType = $cakeDay->largeCakes > 0 ? 'Large' : 'Small';
            $io->text(sprintf(
                '• %s: %s cake for %s',
                $cakeDay->getFormattedDate(),
                $cakeType,
                implode(', ', $cakeDay->employeeNames)
            ));
        }

        if (count($cakeDays) > $displayLimit) {
            $io->text(sprintf('... and %d more cake days', count($cakeDays) - $displayLimit));
        }
    }

    /**
     * Display processing summary
     */
    public function displaySummary(array $cakeDays, float $fileSizeMB, SymfonyStyle $io): void
    {
        $totalSmallCakes = array_sum(array_map(fn($cd) => $cd->smallCakes, $cakeDays));
        $totalLargeCakes = array_sum(array_map(fn($cd) => $cd->largeCakes, $cakeDays));

        $io->section('Summary:');
        $io->definitionList(
            ['Input file size' => $fileSizeMB . ' MB'],
            ['Total cake days' => number_format(count($cakeDays))],
            ['Small cakes' => number_format($totalSmallCakes)],
            ['Large cakes' => number_format($totalLargeCakes)],
            ['Memory usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'],
            ['Processing time' => sprintf('%.2f seconds', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'])]
        );
    }

    /**
     * Handle CSV export with progress
     */
    public function exportWithProgress(SimpleCsvExporter $csvExporter, array $cakeDays, string $outputFile, SymfonyStyle $io): void
    {
        $io->section('Exporting to CSV...');

        $exportProgress = $io->createProgressBar(count($cakeDays));
        $exportProgress->setFormat('verbose');

        $csvExporter->exportWithProgress($cakeDays, $outputFile, function ($processed, $total) use ($exportProgress) {
            $exportProgress->setProgress($processed);
        });

        $exportProgress->finish();
        $io->newLine(2);
        $io->success(sprintf('Cake days exported to %s', $outputFile));
    }
}
