<?php

declare(strict_types=1);

namespace App\Service;

use App\Interface\CsvExporterInterface;
use App\Model\SimpleCakeDay;

/**
 * CSV exporter optimized for SimpleCakeDay objects (no Carbon overhead)
 */
final class SimpleCsvExporter implements CsvExporterInterface
{
    private const CSV_HEADERS = ['Date', 'Small Cakes', 'Large Cakes', 'Employees'];

    /**
     * Export SimpleCakeDay objects to CSV with progress callback
     * 
     * @param SimpleCakeDay[] $cakeDays
     * @param callable|null $progressCallback function(int $processed, int $total): void
     */
    public function exportWithProgress(array $cakeDays, string $outputFile, ?callable $progressCallback = null): void
    {
        $handle = fopen($outputFile, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Cannot create output file: {$outputFile}");
        }

        try {
            // Write CSV headers
            fputcsv($handle, self::CSV_HEADERS);

            $total = count($cakeDays);
            $processed = 0;

            // Write data rows
            foreach ($cakeDays as $cakeDay) {
                $row = [
                    $cakeDay->getFormattedDate(), // Uses date() internally, no Carbon
                    (string) $cakeDay->smallCakes,
                    (string) $cakeDay->largeCakes,
                    implode(', ', $cakeDay->employeeNames)
                ];

                fputcsv($handle, $row);

                $processed++;

                // Progress callback
                if ($progressCallback !== null) {
                    $progressCallback($processed, $total);
                }

                // Memory cleanup for large files
                if ($processed % 1000 === 0) {
                    gc_collect_cycles();
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
