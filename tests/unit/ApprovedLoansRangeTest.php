<?php

use App\Support\AppRepository;
use CodeIgniter\Test\CIUnitTestCase;

final class ApprovedLoansRangeTest extends CIUnitTestCase
{
    public function testMissingDatesDefaultToCurrentMonth(): void
    {
        $this->assertSame(['2026-11-01', '2026-11-09'], AppRepository::normalizeDateRange(null, '', '2026-11-09'));
    }

    public function testInvalidDatesFallBack(): void
    {
        $this->assertSame(['2026-11-01', '2026-11-09'], AppRepository::normalizeDateRange('2026-02-30', 'abc', '2026-11-09'));
        $this->assertSame(['2026-11-01', '2026-11-09'], AppRepository::normalizeDateRange("2026-01-01' OR 1=1", '2026-13-01', '2026-11-09'));
    }

    public function testReversedRangeIsSwapped(): void
    {
        $this->assertSame(['2026-01-10', '2026-03-05'], AppRepository::normalizeDateRange('2026-03-05', '2026-01-10', '2026-11-09'));
    }

    public function testPresets(): void
    {
        $presets = AppRepository::approvedLoansPresets('2026-03-15');

        $this->assertSame(['2026-03-01', '2026-03-15'], $presets['Este mes']);
        $this->assertSame(['2026-02-01', '2026-02-28'], $presets['Mes anterior']);
        $this->assertSame(['2026-02-14', '2026-03-15'], $presets['Ultimos 30 dias']);
        $this->assertSame(['2026-01-01', '2026-03-15'], $presets['Este año']);
    }
}
