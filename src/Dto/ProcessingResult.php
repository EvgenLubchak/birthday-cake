<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * DTO for cake day processing results
 */
final readonly class ProcessingResult
{
    public function __construct(
        public array $cakeDays,
        public int $totalEmployeesProcessed,
        public float $processingTimeSeconds,
        public int $memoryUsageBytes,
        public int $totalSmallCakes,
        public int $totalLargeCakes
    ) {}

    public function getTotalCakeDays(): int
    {
        return count($this->cakeDays);
    }

    public function getMemoryUsageMB(): float
    {
        return round($this->memoryUsageBytes / 1024 / 1024, 2);
    }

    public function getFormattedProcessingTime(): string
    {
        return sprintf('%.2f seconds', $this->processingTimeSeconds);
    }
}
