<?php

declare(strict_types=1);

namespace Tests\Service;

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

    public function testSimpleCakeDay(): void
    {
        // Dave's birthday is 13th June 1986, should get cake on Monday 16th June 2025
        $employee = new Employee('Dave', Carbon::parse('1986-06-13'));
        $cakeDays = $this->calculator->calculateCakeDays([$employee], 2025);

        $this->assertCount(1, $cakeDays);
        $this->assertEquals('2025-06-16', $cakeDays[0]->date->format('Y-m-d'));
        $this->assertEquals(1, $cakeDays[0]->smallCakes);
        $this->assertEquals(0, $cakeDays[0]->largeCakes);
        $this->assertEquals(['Dave'], $cakeDays[0]->getEmployeeNames());
    }

    public function testWeekendBirthdayGetsMondayOff(): void
    {
        // Rob's birthday is Sunday 6th July 2025, should get Monday off and cake on Tuesday 8th July 2025
        $employee = new Employee('Rob', Carbon::parse('1950-07-06'));
        $cakeDays = $this->calculator->calculateCakeDays([$employee], 2025);

        $this->assertCount(1, $cakeDays);
        $this->assertEquals('2025-07-08', $cakeDays[0]->date->format('Y-m-d'));
        $this->assertEquals(1, $cakeDays[0]->smallCakes);
        $this->assertEquals(0, $cakeDays[0]->largeCakes);
    }

    public function testTwoPeopleGetLargeCake(): void
    {
        // Sam's birthday Monday 14th July, Kate's Tuesday 15th July
        // They should share large cake on Wednesday 16th July 2025
        $sam = new Employee('Sam', Carbon::parse('1990-07-14'));
        $kate = new Employee('Kate', Carbon::parse('1991-07-15'));

        $cakeDays = $this->calculator->calculateCakeDays([$sam, $kate], 2025);

        $this->assertCount(1, $cakeDays);
        $this->assertEquals('2025-07-16', $cakeDays[0]->date->format('Y-m-d'));
        $this->assertEquals(0, $cakeDays[0]->smallCakes);
        $this->assertEquals(1, $cakeDays[0]->largeCakes);
        $this->assertEqualsCanonicalizing(['Sam', 'Kate'], $cakeDays[0]->getEmployeeNames());
    }

    public function testConsecutiveDaysRule(): void
    {
        // Test the complex scenario: Alex (21st), Jen (22nd), Pete (23rd July)
        // Alex and Jen should share large cake on 23rd, Pete gets small cake on 25th
        $alex = new Employee('Alex', Carbon::parse('1990-07-21'));
        $jen = new Employee('Jen', Carbon::parse('1991-07-22'));
        $pete = new Employee('Pete', Carbon::parse('1992-07-23'));

        $cakeDays = $this->calculator->calculateCakeDays([$alex, $jen, $pete], 2025);

        $this->assertCount(2, $cakeDays);

        // Sort by date for consistent testing
        usort($cakeDays, fn($a, $b) => $a->date->timestamp <=> $b->date->timestamp);

        // First cake day: Alex and Jen share large cake on Wednesday 23rd
        $this->assertEquals('2025-07-23', $cakeDays[0]->date->format('Y-m-d'));
        $this->assertEquals(0, $cakeDays[0]->smallCakes);
        $this->assertEquals(1, $cakeDays[0]->largeCakes);
        $this->assertEqualsCanonicalizing(['Alex', 'Jen'], $cakeDays[0]->getEmployeeNames());

        // Second cake day: Pete gets small cake on Friday 25th (Thursday 24th is cake-free day)
        $this->assertEquals('2025-07-25', $cakeDays[1]->date->format('Y-m-d'));
        $this->assertEquals(1, $cakeDays[1]->smallCakes);
        $this->assertEquals(0, $cakeDays[1]->largeCakes);
        $this->assertEquals(['Pete'], $cakeDays[1]->getEmployeeNames());
    }

    public function testHolidayBirthdayPostponement(): void
    {
        // Test birthday on Christmas Day (25th December)
        $employee = new Employee('Christmas Baby', Carbon::parse('1990-12-25'));
        $cakeDays = $this->calculator->calculateCakeDays([$employee], 2025);

        $this->assertCount(1, $cakeDays);
        // Should get cake on first working day after Boxing Day (which is also holiday)
        // Dec 25, 2025 is Thursday (Christmas), Dec 26 is Friday (Boxing Day)
        // Weekend: Dec 28-29 (Sat-Sun)
        // So cake should be on Monday Dec 30, 2025
        $this->assertEquals('2025-12-30', $cakeDays[0]->date->format('Y-m-d'));
    }

    public function testNewYearBirthday(): void
    {
        // Test birthday on New Year's Day
        $employee = new Employee('New Year Baby', Carbon::parse('1990-01-01'));
        $cakeDays = $this->calculator->calculateCakeDays([$employee], 2025);

        $this->assertCount(1, $cakeDays);
        // Jan 1, 2025 is Wednesday (New Year's Day holiday)
        // Employee gets Thursday Jan 2 off (next working day after holiday)
        // So cake should be on Friday Jan 3, 2025
        $this->assertEquals('2025-01-03', $cakeDays[0]->date->format('Y-m-d'));
    }

    public function testEmptyEmployeesList(): void
    {
        $cakeDays = $this->calculator->calculateCakeDays([], 2025);
        $this->assertEmpty($cakeDays);
    }

    public function testSingleEmployeeMultipleYears(): void
    {
        $employee = new Employee('Test', Carbon::parse('1990-06-15'));

        $cakeDays2024 = $this->calculator->calculateCakeDays([$employee], 2024);
        $cakeDays2025 = $this->calculator->calculateCakeDays([$employee], 2025);

        $this->assertCount(1, $cakeDays2024);
        $this->assertCount(1, $cakeDays2025);

        // Dates should be different years
        $this->assertEquals('2024', $cakeDays2024[0]->date->format('Y'));
        $this->assertEquals('2025', $cakeDays2025[0]->date->format('Y'));
    }
}
