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
     * Convert to regular CakeDay with Carbon object
     */
    public function toCakeDay(): CakeDay
    {
        $date = \Carbon\Carbon::createFromTimestamp($this->timestamp);

        // Create minimal Employee objects
        $employees = [];
        $fakeDate = new \DateTime('2000-01-01');
        $carbonFakeDate = \Carbon\Carbon::instance($fakeDate);

        foreach ($this->employeeNames as $name) {
            $employees[] = new Employee($name, $carbonFakeDate);
        }

        return new CakeDay(
            $date,
            $this->smallCakes,
            $this->largeCakes,
            $employees
        );
    }

    /**
     * Get formatted date string without creating Carbon object
     */
    public function getFormattedDate(): string
    {
        return date('Y-m-d (l)', $this->timestamp);
    }
}
