<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\CakeDay;

/**
 * Service for exporting cake days to CSV format with streaming support
 */
final class CsvExporter
{
    private const CSV_HEADERS = [
        'Date',
        'Number of Small Cakes',
        'Number of Large Cakes',
        'Names of people getting cake'
    ];

    /**
     * Export cake days to CSV file using streaming (memory efficient)
     * 
     * @param CakeDay[] $cakeDays
     */
    public function exportToFile(array $cakeDays, string $filePath): void
    {
        $this->exportToFileStream($cakeDays, $filePath);
    }

    /**
     * Stream export for large datasets
     * 
     * @param CakeDay[] $cakeDays
     */
    public function exportToFileStream(array $cakeDays, string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Could not create file: {$filePath}");
        }

        try {
            // Write headers
            fputcsv($handle, self::CSV_HEADERS);

            // Stream write data
            foreach ($cakeDays as $cakeDay) {
                fputcsv($handle, $cakeDay->toCsvRow());
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Export with progress callback for monitoring
     * 
     * @param CakeDay[] $cakeDays
     */
    public function exportWithProgress(array $cakeDays, string $filePath, ?callable $progressCallback = null): void
    {
        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Could not create file: {$filePath}");
        }

        $total = count($cakeDays);
        $processed = 0;

        try {
            fputcsv($handle, self::CSV_HEADERS);

            foreach ($cakeDays as $cakeDay) {
                fputcsv($handle, $cakeDay->toCsvRow());
                $processed++;

                if ($progressCallback && $processed % 100 === 0) {
                    $progressCallback($processed, $total);
                }
            }

            // Final callback for completion
            if ($progressCallback && $processed > 0) {
                $progressCallback($processed, $total);
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Generate CSV content as string (legacy method for small datasets)
     * 
     * @param CakeDay[] $cakeDays
     */
    public function generateCsvContent(array $cakeDays): string
    {
        $output = fopen('php://memory', 'r+');

        // Write headers
        fputcsv($output, self::CSV_HEADERS);

        // Write cake days
        foreach ($cakeDays as $cakeDay) {
            fputcsv($output, $cakeDay->toCsvRow());
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Export cake days to array format (for testing)
     * 
     * @param CakeDay[] $cakeDays
     */
    public function exportToArray(array $cakeDays): array
    {
        $result = [];
        $result[] = self::CSV_HEADERS;

        foreach ($cakeDays as $cakeDay) {
            $result[] = $cakeDay->toCsvRow();
        }

        return $result;
    }
}
