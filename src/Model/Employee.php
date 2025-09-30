<?php

declare(strict_types=1);

namespace App\Model;

use Carbon\Carbon;

/**
 * Represents an employee with their birth date
 */
final readonly class Employee
{
    public function __construct(
        public string $name,
        public Carbon $dateOfBirth
    ) {}

    /**
     * Get employee's birthday for a specific year (memory optimized)
     */
    public function getBirthdayForYear(int $year): Carbon
    {
        // Create new Carbon instance instead of copying to reduce memory footprint
        return Carbon::create(
            $year, 
            $this->dateOfBirth->month, 
            $this->dateOfBirth->day,
            0, 0, 0,
            $this->dateOfBirth->timezone
        );
    }

    /**
     * Create Employee from CSV line
     */
    public static function fromCsvLine(string $line): self
    {
        $parts = array_map('trim', explode(',', $line, 2));

        if (count($parts) !== 2) {
            throw new \InvalidArgumentException("Invalid CSV line format: {$line}");
        }

        [$name, $dateStr] = $parts;

        if (empty($name)) {
            throw new \InvalidArgumentException("Employee name cannot be empty");
        }

        try {
            $dateOfBirth = Carbon::createFromFormat('Y-m-d', $dateStr);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date format: {$dateStr}. Expected Y-m-d format.");
        }

        return new self($name, $dateOfBirth);
    }
}
