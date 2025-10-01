<?php

declare(strict_types=1);

namespace App\Interface;

interface CsvExporterInterface
{
    /**
     * Export cake days to CSV with progress callback
     * 
     * @param \App\Model\SimpleCakeDay[] $cakeDays
     * @param callable|null $progressCallback function(int $processed, int $total): void
     */
    public function exportWithProgress(array $cakeDays, string $outputFile, ?callable $progressCallback = null): void;
}
