<?php

declare(strict_types=1);

namespace App\Service;

use App\Interface\HolidayServiceInterface;
use Carbon\Carbon;

/**
 * Service for managing holidays and working days
 */
final class HolidayService implements HolidayServiceInterface
{
    /**
     * Fixed holidays (month-day)
     */
    private const FIXED_HOLIDAYS = [
        '12-25', // Christmas Day
        '12-26', // Boxing Day
        '01-01', // New Year's Day
    ];

    /**
     * Check if a date is a working day
     */
    public function isWorkingDay(Carbon $date): bool
    {
        return !$this->isWeekend($date) && !$this->isHoliday($date);
    }

    /**
     * Check if a date is a weekend
     */
    public function isWeekend(Carbon $date): bool
    {
        return $date->isWeekend();
    }

    /**
     * Check if a date is a holiday
     */
    public function isHoliday(Carbon $date): bool
    {
        $monthDay = $date->format('m-d');
        return in_array($monthDay, self::FIXED_HOLIDAYS, true);
    }

    /**
     * Get the next working day after the given date
     */
    public function getNextWorkingDay(Carbon $date): Carbon
    {
        $nextDay = $date->copy()->addDay();

        while (!$this->isWorkingDay($nextDay)) {
            $nextDay->addDay();
        }

        return $nextDay;
    }
}
