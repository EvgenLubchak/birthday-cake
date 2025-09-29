<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Employee;
use App\Model\CakeDay;
use Carbon\Carbon;

/**
 * Service for calculating cake days based on complex business rules
 */
final class CakeDayCalculator
{
    public function __construct(
        private readonly HolidayService $holidayService
    ) {}

    /**
     * Calculate all cake days for employees in a given year
     * 
     * @param Employee[] $employees
     * @return CakeDay[]
     */
    public function calculateCakeDays(array $employees, int $year): array
    {
        // Step 1: Calculate initial cake dates for each employee
        $employeeCakeDates = $this->calculateInitialCakeDates($employees, $year);

        // Step 2: Group employees by their cake dates
        $groupedByDate = $this->groupEmployeesByDate($employeeCakeDates);

        // Step 3: Apply consolidation rules (2+ people = large cake)
        $consolidatedCakes = $this->applyConsolidationRules($groupedByDate);

        // Step 4: Apply consecutive day rules (2 days in a row = large cake on second day)
        $consecutiveAdjusted = $this->applyConsecutiveDayRules($consolidatedCakes);

        // Step 5: Apply cake-free day rules (day after cake must be cake-free)
        $cakeFreeDayAdjusted = $this->applyCakeFreeDayRules($consecutiveAdjusted);

        // Step 6: Sort by date
        usort($cakeFreeDayAdjusted, fn(CakeDay $a, CakeDay $b) => $a->date <=> $b->date);

        return $cakeFreeDayAdjusted;
    }

    /**
     * Calculate initial cake dates for each employee
     */
    private function calculateInitialCakeDates(array $employees, int $year): array
    {
        $employeeCakeDates = [];

        foreach ($employees as $employee) {
            $birthday = $employee->getBirthdayForYear($year);

            // Employee gets birthday off
            // If birthday is on non-working day, they get next working day off
            $dayOff = $this->holidayService->isWorkingDay($birthday) 
                ? $birthday 
                : $this->holidayService->getNextWorkingDay($birthday);

            // Cake is provided on first working day after their day off
            $cakeDate = $this->holidayService->getNextWorkingDay($dayOff);

            $employeeCakeDates[] = [
                'employee' => $employee,
                'cakeDate' => $cakeDate,
                'originalBirthday' => $birthday
            ];
        }

        return $employeeCakeDates;
    }

    /**
     * Group employees by their cake dates
     */
    private function groupEmployeesByDate(array $employeeCakeDates): array
    {
        $grouped = [];

        foreach ($employeeCakeDates as $data) {
            $dateKey = $data['cakeDate']->format('Y-m-d');
            if (!isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [
                    'date' => $data['cakeDate'],
                    'employees' => []
                ];
            }
            $grouped[$dateKey]['employees'][] = $data['employee'];
        }

        return $grouped;
    }

    /**
     * Apply consolidation rules: 2+ people = large cake
     */
    private function applyConsolidationRules(array $groupedByDate): array
    {
        $cakeDays = [];

        foreach ($groupedByDate as $dateKey => $data) {
            $employeeCount = count($data['employees']);

            if ($employeeCount >= 2) {
                // Large cake for 2+ people
                $cakeDays[] = new CakeDay(
                    $data['date'],
                    0, // small cakes
                    1, // large cakes
                    $data['employees']
                );
            } else {
                // Small cake for 1 person
                $cakeDays[] = new CakeDay(
                    $data['date'],
                    1, // small cakes
                    0, // large cakes
                    $data['employees']
                );
            }
        }

        return $cakeDays;
    }

    /**
     * Apply consecutive day rules: cake 2 days in a row = large cake on second day
     */
    private function applyConsecutiveDayRules(array $cakeDays): array
    {
        if (count($cakeDays) <= 1) {
            return $cakeDays;
        }

        // Sort by date first
        usort($cakeDays, fn(CakeDay $a, CakeDay $b) => $a->date <=> $b->date);

        $adjusted = [];
        $i = 0;

        while ($i < count($cakeDays)) {
            $current = $cakeDays[$i];

            // Check if next day also has cake
            if ($i + 1 < count($cakeDays)) {
                $next = $cakeDays[$i + 1];

                if ($this->areConsecutiveWorkingDays($current->date, $next->date)) {
                    // Combine into large cake on second day
                    $combinedEmployees = array_merge($current->employees, $next->employees);

                    $adjusted[] = new CakeDay(
                        $next->date,
                        0, // small cakes
                        1, // large cakes
                        $combinedEmployees
                    );

                    $i += 2; // Skip both days
                    continue;
                }
            }

            // No consecutive day, keep current cake as is
            $adjusted[] = $current;
            $i++;
        }

        return $adjusted;
    }

    /**
     * Apply cake-free day rules: day after cake must be cake-free
     */
    private function applyCakeFreeDayRules(array $cakeDays): array
    {
        if (empty($cakeDays)) {
            return $cakeDays;
        }

        // Sort by date
        usort($cakeDays, fn(CakeDay $a, CakeDay $b) => $a->date <=> $b->date);

        $adjusted = [];
        $lastCakeDate = null;

        foreach ($cakeDays as $cakeDay) {
            if ($lastCakeDate === null) {
                // First cake day
                $adjusted[] = $cakeDay;
                $lastCakeDate = $cakeDay->date;
                continue;
            }

            $nextWorkingDayAfterLast = $this->holidayService->getNextWorkingDay($lastCakeDate);

            if ($cakeDay->date->equalTo($nextWorkingDayAfterLast)) {
                // This cake falls on cake-free day, postpone it
                $postponedDate = $this->holidayService->getNextWorkingDay($cakeDay->date);

                $adjusted[] = new CakeDay(
                    $postponedDate,
                    $cakeDay->smallCakes,
                    $cakeDay->largeCakes,
                    $cakeDay->employees
                );

                $lastCakeDate = $postponedDate;
            } else {
                // This cake is fine
                $adjusted[] = $cakeDay;
                $lastCakeDate = $cakeDay->date;
            }
        }

        return $adjusted;
    }

    /**
     * Check if two dates are consecutive working days
     */
    private function areConsecutiveWorkingDays(Carbon $date1, Carbon $date2): bool
    {
        return $this->holidayService->getNextWorkingDay($date1)->equalTo($date2);
    }
}
