<?php

use App\Services\AmortizationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AmortizationServiceTest extends CIUnitTestCase
{
    public function testFrenchAmortizationUsesMonthlyRateFromFormPercentage(): void
    {
        $schedule = (new AmortizationService())->calculateFrench(100000, 10, 3);

        $this->assertCount(3, $schedule);
        $this->assertSame(10000.00, $schedule[0]['interest_amount']);
        $this->assertSame(40211.48, $schedule[0]['total_amount']);
        $this->assertSame(69788.52, $schedule[0]['remaining_balance']);
        $this->assertSame(0.00, $schedule[2]['remaining_balance']);
    }

    public function testFrenchAmortizationDoesNotConvertFormRateToAnnualMonthlyEquivalent(): void
    {
        $schedule = (new AmortizationService())->calculateFrench(100000, 10, 3);

        $this->assertNotSame(33890.43, $schedule[0]['total_amount']);
        $this->assertNotSame(101671.28, round(array_sum(array_column($schedule, 'total_amount')), 2));
    }

    public function testFrenchAmortizationAlsoSupportsStoredDecimalRate(): void
    {
        $schedule = (new AmortizationService())->calculateFrench(100000, 0.10, 3);

        $this->assertCount(3, $schedule);
        $this->assertSame(10000.00, $schedule[0]['interest_amount']);
        $this->assertSame(40211.48, $schedule[0]['total_amount']);
        $this->assertSame(3655.59, $schedule[2]['interest_amount']);
        $this->assertSame(0.00, $schedule[2]['remaining_balance']);
    }
}
