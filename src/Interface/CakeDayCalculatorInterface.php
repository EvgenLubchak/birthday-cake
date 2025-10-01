<?php

declare(strict_types=1);

namespace App\Interface;

interface CakeDayCalculatorInterface
{
    /**
     * Calculate cake days for given employees and year
     * 
     * @param \App\Model\Employee[] $employees
     * @return \App\Model\SimpleCakeDay[]
     */
    public function calculateCakeDays(array $employees, int $year): array;
}
