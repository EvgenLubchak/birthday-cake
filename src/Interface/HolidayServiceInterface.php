<?php

declare(strict_types=1);

namespace App\Interface;

use Carbon\Carbon;

interface HolidayServiceInterface
{
    /**
     * Check if a date is a working day
     */
    public function isWorkingDay(Carbon $date): bool;

    /**
     * Check if a date is a weekend
     */
    public function isWeekend(Carbon $date): bool;

    /**
     * Check if a date is a holiday
     */
    public function isHoliday(Carbon $date): bool;

    /**
     * Get the next working day after the given date
     */
    public function getNextWorkingDay(Carbon $date): Carbon;
}
