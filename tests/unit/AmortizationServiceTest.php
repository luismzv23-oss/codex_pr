<?php

use App\Services\AmortizationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AmortizationServiceTest extends CIUnitTestCase
{
    public function testFrenchAmortizationUsesStoredMonthlyDecimalRate(): void
    {
        $schedule = (new AmortizationService())->calculateFrench(100000, 0.10, 3);

        $this->assertCount(3, $schedule);
        $this->assertSame(10000.00, $schedule[0]['interest_amount']);
        $this->assertSame(40211.48, $schedule[0]['total_amount']);
        $this->assertSame(69788.52, $schedule[0]['remaining_balance']);
        $this->assertSame(0.00, $schedule[2]['remaining_balance']);
    }

    public function testFrenchAmortizationSupportsMonthlyRatesAboveOneHundredPercent(): void
    {
        $schedule = (new AmortizationService())->calculateFrench(1000000, 1.05, 3);

        $this->assertCount(3, $schedule);
        $this->assertSame(1050000.00, $schedule[0]['interest_amount']);
        $this->assertSame(1187883.49, $schedule[0]['total_amount']);
        $this->assertSame(862116.51, $schedule[0]['remaining_balance']);
        $this->assertSame(905222.34, $schedule[1]['interest_amount']);
        $this->assertSame(579455.36, $schedule[1]['remaining_balance']);
        $this->assertSame(608428.13, $schedule[2]['interest_amount']);
        $this->assertSame(0.00, $schedule[2]['remaining_balance']);
        $this->assertSame(3563650.47, round(array_sum(array_column($schedule, 'total_amount')), 2));
    }

    public function testFrenchAmortizationDoesNotTreatDecimalRateAboveOneAsPercentageAgain(): void
    {
        $schedule = (new AmortizationService())->calculateFrench(1000000, 1.05, 3);

        $this->assertNotSame(352655.28, $schedule[0]['total_amount']);
        $this->assertNotSame(10500.00, $schedule[0]['interest_amount']);
    }

    public function testInstallmentsStartNextMonthWhenLoanIsRequestedBeforeDayTwenty(): void
    {
        $schedule = (new AmortizationService())->generateSchedule([
            'principal_amount' => 100000,
            'interest_rate' => 0.10,
            'term_months' => 3,
            'amortization_type' => 'french',
            'disbursed_at' => '2026-05-19 10:00:00',
        ]);

        $this->assertSame('2026-06-01', $schedule[0]['generation_date']);
        $this->assertSame('2026-06-05', $schedule[0]['due_date']);
        $this->assertSame('2026-07-01', $schedule[1]['generation_date']);
        $this->assertSame('2026-07-05', $schedule[1]['due_date']);
        $this->assertSame('2026-08-01', $schedule[2]['generation_date']);
        $this->assertSame('2026-08-05', $schedule[2]['due_date']);
    }

    public function testInstallmentsStartFollowingMonthWhenLoanIsRequestedFromDayTwenty(): void
    {
        $schedule = (new AmortizationService())->generateSchedule([
            'principal_amount' => 100000,
            'interest_rate' => 0.10,
            'term_months' => 3,
            'amortization_type' => 'french',
            'disbursed_at' => '2026-05-20 10:00:00',
        ]);

        $this->assertSame('2026-07-01', $schedule[0]['generation_date']);
        $this->assertSame('2026-07-05', $schedule[0]['due_date']);
        $this->assertSame('2026-08-01', $schedule[1]['generation_date']);
        $this->assertSame('2026-08-05', $schedule[1]['due_date']);
        $this->assertSame('2026-09-01', $schedule[2]['generation_date']);
        $this->assertSame('2026-09-05', $schedule[2]['due_date']);
    }
}
