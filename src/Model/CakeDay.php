<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;

/**
 * Represents a day when cake is provided
 */
final readonly class CakeDay
{
    /**
     * @param Employee[] $employees
     */
    public function __construct(
        public Carbon $date,
        public int $smallCakes,
        public int $largeCakes,
        public array $employees
    ) {}

    /**
     * Get total number of cakes
     */
    public function getTotalCakes(): int
    {
        return $this->smallCakes + $this->largeCakes;
    }

    /**
     * Get names of employees getting cake
     */
    public function getEmployeeNames(): array
    {
        return array_map(fn(Employee $employee) => $employee->name, $this->employees);
    }

    /**
     * Convert to CSV row format
     */
    public function toCsvRow(): array
    {
        return [
            $this->date->format('Y-m-d'),
            (string) $this->smallCakes,
            (string) $this->largeCakes,
            implode(', ', $this->getEmployeeNames())
        ];
    }
}
