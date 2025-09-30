<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Employee;
use App\Model\CakeDay;
use Carbon\Carbon;

/**
 * Service for calculating cake days with memory optimization
 */
final class CakeDayCalculator
{
    private const MAX_EMPLOYEES_IN_MEMORY = 2000;

    public function __construct(
        private readonly HolidayService $holidayService
    ) {}

    /**
     * Calculate all cake days for employees in a given year (memory optimized)
     * 
     * @param Employee[] $employees
     * @return CakeDay[]
     */
    public function calculateCakeDays(array $employees, int $year): array
    {
        $employeeCount = count($employees);

        // For large datasets, use batch processing
        if ($employeeCount > self::MAX_EMPLOYEES_IN_MEMORY) {
            return $this->calculateCakeDaysInBatches($employees, $year);
        }

        // Optimized logic for all datasets
        return $this->calculateCakeDaysOptimized($employees, $year);
    }

    /**
     * Optimized calculation for better memory usage
     */
    private function calculateCakeDaysOptimized(array $employees, int $year): array
    {
        // Use timestamp-based grouping to avoid expensive string operations
        $dateGroups = [];

        // Step 1: Calculate cake dates directly into groups with memory cleanup
        foreach ($employees as $index => $employee) {
            $birthday = $employee->getBirthdayForYear($year);

            $dayOff = $this->holidayService->isWorkingDay($birthday) 
                ? $birthday 
                : $this->holidayService->getNextWorkingDay($birthday);

            $cakeDate = $this->holidayService->getNextWorkingDay($dayOff);

            // Use timestamp as key to avoid expensive string formatting
            $dateKey = $cakeDate->getTimestamp();

            if (!isset($dateGroups[$dateKey])) {
                $dateGroups[$dateKey] = [
                    'date' => $cakeDate,
                    'employees' => []
                ];
            }

            $dateGroups[$dateKey]['employees'][] = $employee;

            // Clear temporary Carbon objects to reduce memory pressure
            unset($birthday, $dayOff);

            // Garbage collection every 100 employees to handle Carbon objects
            if ($index % 100 === 0) {
                gc_collect_cycles();
            }
        }

        // Step 2-5: Process groups with minimal memory allocation
        $cakeDays = $this->processGroupsOptimized($dateGroups);

        // Clear intermediate data
        unset($dateGroups);
        gc_collect_cycles();

        // Step 6: Sort only once at the end
        usort($cakeDays, static fn(CakeDay $a, CakeDay $b) => $a->date <=> $b->date);

        return $cakeDays;
    }

    /**
     * Process groups with minimal memory allocation
     */
    private function processGroupsOptimized(array $dateGroups): array
    {
        $cakeDays = [];

        // Convert to cake days with consolidation
        foreach ($dateGroups as $data) {
            $employeeCount = count($data['employees']);

            $cakeDays[] = new CakeDay(
                $data['date'],
                $employeeCount >= 2 ? 0 : 1,  // small cakes
                $employeeCount >= 2 ? 1 : 0,  // large cakes
                $data['employees']
            );
        }

        // Apply rules in-place to avoid memory copies
        $cakeDays = $this->applyConsecutiveRulesOptimized($cakeDays);
        $cakeDays = $this->applyCakeFreeDayRulesOptimized($cakeDays);

        return $cakeDays;
    }

    /**
     * Batch processing for very large datasets
     */
    private function calculateCakeDaysInBatches(array $employees, int $year): array
    {
        $batches = array_chunk($employees, self::MAX_EMPLOYEES_IN_MEMORY);
        $allCakeDays = [];

        foreach ($batches as $batch) {
            $batchCakeDays = $this->calculateCakeDaysOptimized($batch, $year);
            $allCakeDays = array_merge($allCakeDays, $batchCakeDays);

            // Force garbage collection after each batch
            gc_collect_cycles();
        }

        // Final consolidation and rule application
        return $this->consolidateBatchResults($allCakeDays);
    }

    /**
     * Consolidate results from multiple batches
     */
    private function consolidateBatchResults(array $allCakeDays): array
    {
        // Group cake days from different batches by date
        $consolidated = [];
        $dateGroups = [];

        foreach ($allCakeDays as $cakeDay) {
            $dateKey = $cakeDay->date->getTimestamp();

            if (!isset($dateGroups[$dateKey])) {
                $dateGroups[$dateKey] = [
                    'date' => $cakeDay->date,
                    'employees' => []
                ];
            }

            $dateGroups[$dateKey]['employees'] = array_merge(
                $dateGroups[$dateKey]['employees'], 
                $cakeDay->employees
            );
        }

        // Recreate cake days with proper consolidation
        foreach ($dateGroups as $data) {
            $employeeCount = count($data['employees']);

            $consolidated[] = new CakeDay(
                $data['date'],
                $employeeCount >= 2 ? 0 : 1,
                $employeeCount >= 2 ? 1 : 0,
                $data['employees']
            );
        }

        // Apply final rules
        $consolidated = $this->applyConsecutiveRulesOptimized($consolidated);
        $consolidated = $this->applyCakeFreeDayRulesOptimized($consolidated);

        usort($consolidated, static fn(CakeDay $a, CakeDay $b) => $a->date <=> $b->date);

        return $consolidated;
    }

    /**
     * Memory optimized consecutive day rules
     */
    private function applyConsecutiveRulesOptimized(array $cakeDays): array
    {
        if (count($cakeDays) <= 1) {
            return $cakeDays;
        }

        // Sort by date first
        usort($cakeDays, static fn(CakeDay $a, CakeDay $b) => $a->date <=> $b->date);

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
     * Memory optimized cake-free day rules
     */
    private function applyCakeFreeDayRulesOptimized(array $cakeDays): array
    {
        if (empty($cakeDays)) {
            return $cakeDays;
        }

        // Sort by date
        usort($cakeDays, static fn(CakeDay $a, CakeDay $b) => $a->date <=> $b->date);

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
