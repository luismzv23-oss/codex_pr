<?php

use App\Support\AppRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/** Fixtures are confined to connection-local temporary tables with a random prefix. */
final class CollectionSummaryTest extends CIUnitTestCase
{
    private const TODAY = '2026-03-15';

    private ?BaseConnection $connection = null;
    private AppRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        helper('app');
        $config = config('Database')->default;
        if ($config['DBDriver'] !== 'MySQLi' || !extension_loaded('mysqli')) $this->markTestSkipped('Requires MySQLi.');
        $config['DBPrefix'] = 'collection_kpi_test_' . bin2hex(random_bytes(6)) . '_';
        $this->connection = Database::connect($config, false);
        $this->connection->initialize();
        foreach ([
            'loans' => 'status VARCHAR(30), currency VARCHAR(3), principal_amount DECIMAL(12,2), total_interest DECIMAL(12,2), total_payable DECIMAL(12,2), outstanding_balance DECIMAL(12,2), disbursed_at DATETIME NULL, deleted_at DATETIME NULL',
            'installments' => 'loan_guid VARCHAR(36), due_date DATE, total_amount DECIMAL(12,2), late_fee DECIMAL(12,2), paid_amount DECIMAL(12,2), deleted_at DATETIME NULL',
            'payments' => 'loan_guid VARCHAR(36), currency VARCHAR(3), amount DECIMAL(12,2), created_at DATETIME NULL, deleted_at DATETIME NULL',
        ] as $table => $columns) {
            $name = $this->connection->prefixTable($table);
            $this->connection->query("CREATE TEMPORARY TABLE `{$name}` (guid VARCHAR(36) PRIMARY KEY, {$columns}) ENGINE=InnoDB");
        }
        $this->repository = new AppRepository();

        foreach ([
            ['ars1', 'ARS', 5000, '2026-03-02 10:00:00', null],
            ['usd1', 'USD', 800, '2026-01-10 10:00:00', null],
            ['gone', 'ARS', 9999, '2026-03-03 10:00:00', '2026-03-04 00:00:00'],
        ] as [$guid, $currency, $principal, $date, $deleted]) {
            $this->connection->table('loans')->insert([
                'guid' => $guid, 'status' => 'active', 'currency' => $currency, 'principal_amount' => $principal,
                'total_interest' => 0, 'total_payable' => $principal, 'outstanding_balance' => $principal,
                'disbursed_at' => $date, 'deleted_at' => $deleted,
            ]);
        }

        foreach ([
            // Overdue: due before TODAY, partially paid; late fee counts toward expected.
            ['i1', 'ars1', '2026-03-01', 1000, 100, 400, null],
            // Overpaid: collected is capped at total.
            ['i2', 'ars1', '2026-03-05', 500, 0, 800, null],
            // Due today counts as upcoming, not overdue.
            ['i3', 'ars1', '2026-03-15', 1000, 0, 0, null],
            ['i4', 'usd1', '2026-03-31', 200, 0, 50, null],
            // Outside the range, deleted installment, and installment of a deleted loan: excluded.
            ['i5', 'ars1', '2026-04-01', 7000, 0, 0, null],
            ['i6', 'ars1', '2026-03-10', 7000, 0, 0, '2026-03-11 00:00:00'],
            ['i7', 'gone', '2026-03-10', 7000, 0, 0, null],
        ] as [$guid, $loan, $due, $total, $fee, $paid, $deleted]) {
            $this->connection->table('installments')->insert([
                'guid' => $guid, 'loan_guid' => $loan, 'due_date' => $due, 'total_amount' => $total,
                'late_fee' => $fee, 'paid_amount' => $paid, 'deleted_at' => $deleted,
            ]);
        }

        foreach ([
            ['p1', 'ars1', 'ARS', 1200, '2026-03-05 09:00:00', null],
            ['p2', 'usd1', 'USD', 50, '2026-03-31 23:59:00', null],
            ['p3', 'ars1', 'ARS', 999, '2026-03-06 09:00:00', '2026-03-07 00:00:00'],
            ['p4', 'ars1', 'ARS', 999, '2026-02-28 09:00:00', null],
        ] as [$guid, $loan, $currency, $amount, $date, $deleted]) {
            $this->connection->table('payments')->insert([
                'guid' => $guid, 'loan_guid' => $loan, 'currency' => $currency, 'amount' => $amount,
                'created_at' => $date, 'deleted_at' => $deleted,
            ]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            foreach (['loans', 'installments', 'payments'] as $table) {
                $name = $this->connection->prefixTable($table);
                $this->connection->query("DROP TEMPORARY TABLE IF EXISTS `{$name}`");
            }
            $this->connection->close();
        }
        parent::tearDown();
    }

    private function summary(string $from = '2026-03-01', string $to = '2026-03-31'): array
    {
        return $this->repository->getCollectionSummary($from, $to, $this->connection, self::TODAY);
    }

    public function testRingSplitsDueInstallmentsIntoCollectedOverdueAndUpcoming(): void
    {
        $ars = $this->summary()['by_currency']['ARS'];

        $this->assertSame(2600.0, $ars['expected']);
        $this->assertSame(900.0, $ars['collected']);
        $this->assertSame(700.0, $ars['overdue']);
        $this->assertSame(1000.0, $ars['upcoming']);
        $this->assertSame(34.6, $ars['collected_pct']);
        $this->assertSame(26.9, $ars['overdue_pct']);
    }

    public function testCashFlowUsesPaymentsAndApprovedCapitalOfThePeriod(): void
    {
        $byCurrency = $this->summary()['by_currency'];

        $this->assertSame(['ARS', 'USD'], array_keys($byCurrency));
        $this->assertSame(1200.0, $byCurrency['ARS']['income']);
        $this->assertSame(5000.0, $byCurrency['ARS']['outflow']);
        $this->assertSame(-3800.0, $byCurrency['ARS']['net']);
        $this->assertSame(50.0, $byCurrency['USD']['income']);
        $this->assertSame(0.0, $byCurrency['USD']['outflow']);
        $this->assertSame(200.0, $byCurrency['USD']['expected']);
        $this->assertSame(150.0, $byCurrency['USD']['upcoming']);
    }

    public function testEmptyPeriodHasNoCurrencies(): void
    {
        $this->assertSame([], $this->summary('2025-01-01', '2025-01-31')['by_currency']);
    }

    public function testCurrencyWithOnlyOutflowHasZeroPercentages(): void
    {
        $ars = $this->summary('2026-03-02', '2026-03-02')['by_currency']['ARS'];

        $this->assertSame(5000.0, $ars['outflow']);
        $this->assertSame(0.0, $ars['expected']);
        $this->assertSame(0.0, $ars['collected_pct']);
    }
}
