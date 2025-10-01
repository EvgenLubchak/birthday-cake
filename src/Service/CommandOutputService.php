<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ProcessingResult;
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
    public function displaySummary(ProcessingResult $processingResult, float $fileSizeMB, SymfonyStyle $io): void
    {
        $io->section('Summary:');
        $io->definitionList(
            ['Input file size' => $fileSizeMB . ' MB'],
            ['Total cake days' => number_format($processingResult->getTotalCakeDays())],
            ['Small cakes' => number_format($processingResult->totalSmallCakes)],
            ['Large cakes' => number_format($processingResult->totalLargeCakes)],
            ['Memory usage' => $processingResult->getMemoryUsageMB() . ' MB'],
            ['Processing time' => $processingResult->getFormattedProcessingTime()]
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
