<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Model\Employee;
use App\Service\CakeDayCalculator;
use App\Service\HolidayService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class CakeDayCalculatorTest extends TestCase
{
    private CakeDayCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CakeDayCalculator(new HolidayService());
    }

    public function testSingleEmployeeOnWorkingDayGetsCakeNextWorkingDay(): void
    {
        $employees = [
            new Employee('Alice', Carbon::create(1990, 1, 15)) // 2024-01-15 is Monday
        ];

        $result = $this->calculator->calculateCakeDays($employees, 2024);

        $this->assertCount(1, $result);
        $this->assertSame('2024-01-16', $result[0]->getFormattedDate(), 'Cake should be on the next working day');
        $this->assertSame(1, $result[0]->smallCakes);
        $this->assertSame(0, $result[0]->largeCakes);
        $this->assertSame(['Alice'], $result[0]->employeeNames);
    }

    public function testWeekendBirthdayShiftsDayOffAndCakeDay(): void
    {
        $employees = [
            new Employee('Bob', Carbon::create(1990, 6, 22)) // 2024-06-22 is Saturday
        ];

        $result = $this->calculator->calculateCakeDays($employees, 2024);

        // Birthday Saturday 2024-06-22 -> day off Monday 2024-06-24 -> cake Tuesday 2024-06-25
        $this->assertCount(1, $result);
        $this->assertSame('2024-06-25', $result[0]->getFormattedDate());
        $this->assertSame(1, $result[0]->smallCakes);
        $this->assertSame(0, $result[0]->largeCakes);
    }

    public function testMultipleEmployeesSameCakeDateResultsInLargeCake(): void
    {
        $employees = [
            new Employee('John', Carbon::create(1990, 1, 15)),
            new Employee('Jane', Carbon::create(1985, 1, 15)),
        ];

        $result = $this->calculator->calculateCakeDays($employees, 2024);

        $this->assertCount(1, $result);
        $this->assertSame('2024-01-16', $result[0]->getFormattedDate());
        $this->assertSame(0, $result[0]->smallCakes);
        $this->assertSame(1, $result[0]->largeCakes);
        $this->assertEqualsCanonicalizing(['John', 'Jane'], $result[0]->employeeNames);
    }

    public function testConsecutiveWorkingDaysAreCombinedIntoSecondDayLargeCake(): void
    {
        $employees = [
            // Birthday Friday 2024-01-19 -> cake Monday 2024-01-22
            new Employee('E1', Carbon::create(1990, 1, 19)),
            // Birthday Monday 2024-01-22 -> cake Tuesday 2024-01-23
            new Employee('E2', Carbon::create(1991, 1, 22)),
        ];

        $result = $this->calculator->calculateCakeDays($employees, 2024);

        // Consecutive working days (Mon 22 -> Tue 23) are combined into Tue 23 with a large cake
        $this->assertCount(1, $result);
        $this->assertSame('2024-01-23', $result[0]->getFormattedDate());
        $this->assertSame(0, $result[0]->smallCakes);
        $this->assertSame(1, $result[0]->largeCakes);
        $this->assertEqualsCanonicalizing(['E1', 'E2'], $result[0]->employeeNames);
    }

    public function testBatchingPathConsolidatesAcrossBatches(): void
    {
        // MAX_EMPLOYEES_IN_MEMORY = 2000, so use 2001 to force batching
        $employees = [];
        for ($i = 1; $i <= 2001; $i++) {
            $employees[] = new Employee('Emp'.$i, Carbon::create(1990, 1, 15)); // Cake date 2024-01-16
        }

        $result = $this->calculator->calculateCakeDays($employees, 2024);

        $this->assertCount(1, $result, 'All employees should consolidate into one cake day across batches');
        $this->assertSame('2024-01-16', $result[0]->getFormattedDate());
        $this->assertSame(0, $result[0]->smallCakes);
        $this->assertSame(1, $result[0]->largeCakes);
        $this->assertCount(2001, $result[0]->employeeNames);
    }

    public function testResultsAreSortedByDate(): void
    {
        $employees = [
            // This one will produce cake on 2024-01-16
            new Employee('A', Carbon::create(1990, 1, 15)),
            // This one will produce cake on 2024-02-02 (birthday Thu 2024-02-01 -> cake Fri 2024-02-02)
            new Employee('B', Carbon::create(1992, 2, 1)),
            // This one will produce cake on 2024-01-23 (birthday Mon 2024-01-22 -> cake Tue 23)
            new Employee('C', Carbon::create(1993, 1, 22)),
        ];

        $result = $this->calculator->calculateCakeDays($employees, 2024);

        $dates = array_map(fn($cd) => $cd->getFormattedDate(), $result);
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates, 'Cake days should be sorted chronologically');
    }
}
