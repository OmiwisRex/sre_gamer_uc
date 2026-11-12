<?php

namespace Tests\Unit;

require_once __DIR__.'/../../app/Helpers/DateFormatter.php';

use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DateFormattingTest extends TestCase
{
    public function test_format_date_time_helper_formats_correctly()
    {
        $date = Carbon::parse('2026-11-13 14:30:00');
        $formatted = formatDateTime($date);

        $this->assertStringContainsString('13/11/2026', $formatted);
        $this->assertStringContainsString('02:30', $formatted);
        $this->assertStringContainsString('Pm', $formatted);
        $this->assertStringNotContainsString('undefined', $formatted);
        $this->assertStringNotContainsString('a.m.', $formatted);
    }

    public function test_format_date_time_morning_period()
    {
        $date = Carbon::parse('2026-11-13 09:15:00');
        $formatted = formatDateTime($date);

        $this->assertStringContainsString('13/11/2026', $formatted);
        $this->assertStringContainsString('09:15', $formatted);
        $this->assertStringContainsString('Am', $formatted);
        $this->assertStringNotContainsString('undefined', $formatted);
    }

    public function test_format_time_compact_no_undefined()
    {
        $date = Carbon::parse('2026-11-13 14:30:00');
        $formatted = formatTimeCompact($date);

        $this->assertStringContainsString('02:30Pm', $formatted);
        $this->assertStringNotContainsString('undefined', $formatted);
    }

    public function test_format_date_only()
    {
        $date = Carbon::parse('2026-11-13 14:30:00');
        $formatted = formatDateOnly($date);

        $this->assertEquals('13/11/2026', $formatted);
    }

    public function test_format_time_only()
    {
        $date = Carbon::parse('2026-11-13 14:30:00');
        $formatted = formatTimeOnly($date);

        $this->assertStringContainsString('02:30', $formatted);
        $this->assertStringContainsString('Pm', $formatted);
        $this->assertStringNotContainsString('undefined', $formatted);
    }

    public function test_format_midnight()
    {
        $date = Carbon::parse('2026-11-13 00:00:00');
        $formatted = formatDateTime($date);

        $this->assertStringContainsString('12:00', $formatted);
        $this->assertStringContainsString('Am', $formatted);
    }

    public function test_format_noon()
    {
        $date = Carbon::parse('2026-11-13 12:00:00');
        $formatted = formatDateTime($date);

        $this->assertStringContainsString('12:00', $formatted);
        $this->assertStringContainsString('Pm', $formatted);
    }

    public function test_format_null_fecha()
    {
        $formatted = formatDateTime(null);
        $this->assertEquals('Sin fecha', $formatted);
    }

    public function test_format_invalid_fecha()
    {
        $formatted = formatDateTime('invalid-date');
        $this->assertStringNotContainsString('undefined', $formatted);
        $this->assertIsString($formatted);
    }
}
