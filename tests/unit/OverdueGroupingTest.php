<?php

use App\Support\AppRepository;
use CodeIgniter\Test\CIUnitTestCase;

final class OverdueGroupingTest extends CIUnitTestCase
{
    private const TODAY = '2026-11-11';

    private function loans(): array
    {
        return [
            ['guid' => 'L1', 'alias' => '#1 · SR', 'customer_guid' => 'C1', 'customer_name' => 'Sofia Ramirez', 'currency' => 'ARS'],
            ['guid' => 'L2', 'alias' => '#2 · SR', 'customer_guid' => 'C1', 'customer_name' => 'Sofia Ramirez', 'currency' => 'USD'],
            ['guid' => 'L3', 'alias' => '#3 · AB', 'customer_guid' => 'C2', 'customer_name' => 'Ana Bel', 'currency' => 'ARS'],
        ];
    }

    private function installment(string $loan, int $number, string $due, float $total, float $paid, string $status = 'overdue', float $fee = 0): array
    {
        return ['loan_guid' => $loan, 'installment_number' => $number, 'due_date' => $due, 'total_amount' => $total,
            'late_fee' => $fee, 'paid_amount' => $paid, 'status' => $status];
    }

    public function testGroupsByCustomerThenLoanWithPaymentStatus(): void
    {
        $groups = AppRepository::groupOverdueInstallments([
            $this->installment('L1', 5, '2026-10-01', 1000, 400, 'overdue', 100),
            $this->installment('L1', 4, '2026-09-01', 1000, 0),
            $this->installment('L2', 1, '2026-10-15', 200, 0),
            $this->installment('L1', 6, '2026-12-01', 1000, 0, 'pending'),   // not overdue
            $this->installment('GONE', 1, '2026-01-01', 9999, 0),             // unknown/deleted loan
            $this->installment('L3', 2, '2026-11-01', 500, 0),
        ], $this->loans(), self::TODAY);

        $this->assertSame(['C1', 'C2'], array_column($groups, 'guid'));
        $sofia = $groups[0];
        $this->assertSame('2026-09-01', $sofia['oldest_due']);
        $this->assertSame(2, $sofia['loan_count']);
        $this->assertSame(3, $sofia['installment_count']);
        $this->assertSame(['ARS' => 1700.0, 'USD' => 200.0], $sofia['owed_by_currency']);

        $ars = $sofia['loans'][0];
        $this->assertSame('#1 · SR', $ars['label']);
        $this->assertSame('ARS', $ars['currency']);
        $this->assertSame(1700.0, $ars['owed']);
        $this->assertSame([4, 5], array_column($ars['installments'], 'installment_number'));

        [$none, $partial] = $ars['installments'];
        $this->assertSame('none', $none['payment_status']);
        $this->assertSame(1000.0, $none['owed']);
        $this->assertSame(71, $none['days_late']);
        $this->assertSame('partial', $partial['payment_status']);
        $this->assertSame(1100.0, $partial['total']);
        $this->assertSame(400.0, $partial['paid']);
        $this->assertSame(700.0, $partial['owed']);
    }

    public function testOverdueReportOnlyUsesOpenLoans(): void
    {
        $source = file_get_contents(APPPATH . 'Support/AppRepository.php');
        $this->assertMatchesRegularExpression(
            "/groupOverdueInstallments\\(\\\$this->getInstallments\\(\\), \\\$this->getLoansByStatus\\('active'\\)\\)/",
            $source
        );
    }

    public function testEmptyWhenNothingOverdue(): void
    {
        $this->assertSame([], AppRepository::groupOverdueInstallments(
            [$this->installment('L1', 1, '2026-12-01', 1000, 0, 'pending')], $this->loans(), self::TODAY
        ));
    }

    public function testPaginationCountsCustomersNotInstallments(): void
    {
        $loans = [];
        $installments = [];
        for ($c = 1; $c <= 12; $c++) {
            $loans[] = ['guid' => "L$c", 'alias' => "#$c", 'customer_guid' => "C$c", 'customer_name' => "Cliente $c", 'currency' => 'ARS'];
            for ($i = 1; $i <= 3; $i++) {
                $installments[] = $this->installment("L$c", $i, sprintf('2026-%02d-01', $i), 100, 0);
            }
        }
        $groups = AppRepository::groupOverdueInstallments($installments, $loans, self::TODAY);
        $meta = AppRepository::paginationMeta(count($groups), 2, 10);

        $this->assertCount(12, $groups);
        $this->assertSame(['page' => 2, 'per_page' => 10, 'total' => 12, 'pages' => 2, 'from' => 11, 'to' => 12], $meta);
        $this->assertSame(3, $groups[11]['installment_count']);
    }
}
