<?php

use App\Services\AmortizationService;
use App\Services\FirstCollectionDateService;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;

final class FirstCollectionDateTest extends CIUnitTestCase
{
    private function loan(string $date, string $system = 'french'): array
    {
        return ['principal_amount' => 100000, 'interest_rate' => 0.10, 'term_months' => 3,
            'amortization_type' => $system, 'disbursed_at' => '2026-05-20 10:00:00', 'first_collection_date' => $date];
    }

    public function testCustomDateChangesDatesOnlyForAllSystems(): void
    {
        $service = new AmortizationService();
        foreach (['french', 'german', 'american'] as $system) {
            $custom = $service->generateSchedule($this->loan('2026-11-20', $system));
            $normal = $service->generateSchedule($this->loan('', $system));
            $this->assertSame(['2026-11-20', '2026-12-20', '2027-01-20'], array_column($custom, 'due_date'));
            foreach ($custom as $i => $item) {
                unset($item['due_date'], $item['generation_date']);
                unset($normal[$i]['due_date'], $normal[$i]['generation_date']);
                $this->assertSame($normal[$i], $item);
            }
        }
    }

    public function testDay31ReturnsAfterFebruaryAndLeapYearIsRespected(): void
    {
        $service = new AmortizationService();
        $this->assertSame(['2027-01-31', '2027-02-28', '2027-03-31'], array_column($service->generateSchedule($this->loan('2027-01-31')), 'due_date'));
        $this->assertSame(['2028-01-31', '2028-02-29', '2028-03-31'], array_column($service->generateSchedule($this->loan('2028-01-31')), 'due_date'));
        $this->assertSame(['2027-01-30', '2027-02-28', '2027-03-30'], array_column($service->generateSchedule($this->loan('2027-01-30')), 'due_date'));
    }

    public function testRecalculationRetainsHistoricalCustomDateAndDefaultCalendar(): void
    {
        $service = new AmortizationService();
        $loan = $this->loan('2026-01-29');
        $this->assertSame($service->generateSchedule($loan), $service->recalculate($loan));
        $this->assertSame('2026-01-29', $service->recalculate($loan)[0]['due_date']);
        $this->assertSame(['2026-07-05', '2026-08-05', '2026-09-05'], array_column($service->generateSchedule($this->loan('')), 'due_date'));
    }

    public function testBadDateAndPastApprovalDateAreRejected(): void
    {
        $policy = new FirstCollectionDateService();
        foreach (['', '2026-02-30', '2026-2-01', '2026-10-13'] as $date) {
            try {
                $policy->validate($date, '2026-10-14');
                $this->fail('Invalid date accepted: ' . $date);
            } catch (InvalidArgumentException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
        }
        $this->assertSame('2026-10-14', $policy->validate('2026-10-14', '2026-10-14'));
    }

    public function testOnlyAdminGroupsHavePermissionAndUncheckedUsesAutomaticDates(): void
    {
        $matrix = config('AuthGroups')->matrix;
        $this->assertContains('applications.schedule', $matrix['admin']);
        $this->assertContains('applications.schedule', $matrix['superadmin']);
        $this->assertNotContains('applications.schedule', $matrix['operator']);
        $policy = new FirstCollectionDateService();
        $this->assertNull($policy->fromForm(false, '2099-01-31', null));
        $operator = $this->createMock(User::class);
        $operator->method('can')->willReturn(false);
        $this->expectException(InvalidArgumentException::class);
        $policy->fromForm(true, '2099-01-31', $operator);
    }

    public function testAuthorizedDateCanBeChosenAndInvalidScheduleIsRejected(): void
    {
        $admin = $this->createMock(User::class);
        $admin->method('can')->with('applications.schedule')->willReturn(true);
        $this->assertSame('2099-01-31', (new FirstCollectionDateService())->fromForm(true, '2099-01-31', $admin));
        $this->expectException(InvalidArgumentException::class);
        (new AmortizationService())->generateSchedule($this->loan('2026-02-30'));
    }
}
