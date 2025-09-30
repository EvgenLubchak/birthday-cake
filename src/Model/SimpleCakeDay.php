<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Lightweight cake day representation without Carbon objects (for memory optimization)
 */
final readonly class SimpleCakeDay
{
    public function __construct(
        public int $timestamp,
        public int $smallCakes,
        public int $largeCakes,
        public array $employeeNames
    ) {}

    /**
     * Get formatted date without Carbon dependency
     */
    public function getFormattedDate(): string
    {
        return date('Y-m-d', $this->timestamp);
    }
}
