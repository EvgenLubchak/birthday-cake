<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Service\HolidayService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

final class HolidayServiceTest extends TestCase
{
    private HolidayService $holidayService;

    protected function setUp(): void
    {
        $this->holidayService = new HolidayService();
    }

    public function testIsWorkingDayReturnsTrueForRegularWeekday(): void
    {
        // Arrange - Tuesday, January 3, 2024 (regular working day)
        $date = Carbon::create(2024, 1, 3);

        // Act
        $result = $this->holidayService->isWorkingDay($date);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsWorkingDayReturnsFalseForWeekend(): void
    {
        // Arrange - Saturday
        $saturday = Carbon::create(2024, 1, 6);

        // Act & Assert
        $this->assertFalse($this->holidayService->isWorkingDay($saturday));
    }

    public function testIsWorkingDayReturnsFalseForChristmas(): void
    {
        // Arrange - Christmas Day 2024
        $christmas = Carbon::create(2024, 12, 25);

        // Act
        $result = $this->holidayService->isWorkingDay($christmas);

        // Assert
        $this->assertFalse($result);
    }

    public function testIsWeekendReturnsTrueForSaturday(): void
    {
        // Arrange
        $saturday = Carbon::create(2024, 1, 6);

        // Act
        $result = $this->holidayService->isWeekend($saturday);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsWeekendReturnsTrueForSunday(): void
    {
        // Arrange
        $sunday = Carbon::create(2024, 1, 7);

        // Act
        $result = $this->holidayService->isWeekend($sunday);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsWeekendReturnsFalseForWeekday(): void
    {
        // Arrange
        $monday = Carbon::create(2024, 1, 1);

        // Act
        $result = $this->holidayService->isWeekend($monday);

        // Assert
        $this->assertFalse($result);
    }

    public function testIsHolidayReturnsTrueForFixedHolidays(): void
    {
        // Arrange
        $christmas = Carbon::create(2024, 12, 25);
        $boxingDay = Carbon::create(2024, 12, 26);
        $newYear = Carbon::create(2024, 1, 1);

        // Act & Assert
        $this->assertTrue($this->holidayService->isHoliday($christmas));
        $this->assertTrue($this->holidayService->isHoliday($boxingDay));
        $this->assertTrue($this->holidayService->isHoliday($newYear));
    }

    public function testIsHolidayReturnsFalseForRegularDay(): void
    {
        // Arrange
        $regularDay = Carbon::create(2024, 6, 15);

        // Act
        $result = $this->holidayService->isHoliday($regularDay);

        // Assert
        $this->assertFalse($result);
    }

    public function testGetNextWorkingDaySkipsWeekend(): void
    {
        // Arrange - Friday
        $friday = Carbon::create(2024, 1, 5);

        // Act
        $nextWorkingDay = $this->holidayService->getNextWorkingDay($friday);

        // Assert - Should be Monday
        $this->assertEquals(Carbon::create(2024, 1, 8), $nextWorkingDay);
        $this->assertTrue($nextWorkingDay->isMonday());
    }

    public function testGetNextWorkingDaySkipsHolidays(): void
    {
        // Arrange - December 24, 2024 (day before Christmas)
        $dayBeforeChristmas = Carbon::create(2024, 12, 24);

        // Act
        $nextWorkingDay = $this->holidayService->getNextWorkingDay($dayBeforeChristmas);

        // Assert - Should skip Christmas (25th) and Boxing Day (26th)
        $this->assertEquals(Carbon::create(2024, 12, 27), $nextWorkingDay);
    }

    public function testGetNextWorkingDayForRegularWeekday(): void
    {
        // Arrange - Tuesday
        $tuesday = Carbon::create(2024, 1, 2);

        // Act
        $nextWorkingDay = $this->holidayService->getNextWorkingDay($tuesday);

        // Assert - Should be Wednesday
        $this->assertEquals(Carbon::create(2024, 1, 3), $nextWorkingDay);
    }
}
