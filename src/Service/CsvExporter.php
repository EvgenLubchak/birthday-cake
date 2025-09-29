<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\CakeDay;

/**
 * Service for exporting cake days to CSV format
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
     * Export cake days to CSV file
     * 
     * @param CakeDay[] $cakeDays
     */
    public function exportToFile(array $cakeDays, string $filePath): void
    {
        $csvContent = $this->generateCsvContent($cakeDays);

        if (file_put_contents($filePath, $csvContent) === false) {
            throw new \RuntimeException("Could not write to file: {$filePath}");
        }
    }

    /**
     * Generate CSV content as string
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
