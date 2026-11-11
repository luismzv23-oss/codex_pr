<?php

use App\Support\AppRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/** Fixtures are confined to connection-local temporary tables with a random prefix. */
final class ApprovedLoansSummaryTest extends CIUnitTestCase
{
    private ?BaseConnection $connection = null;
    private AppRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        helper('app');
        $config = config('Database')->default;
        if ($config['DBDriver'] !== 'MySQLi' || !extension_loaded('mysqli')) $this->markTestSkipped('Requires MySQLi.');
        $config['DBPrefix'] = 'approved_kpi_test_' . bin2hex(random_bytes(6)) . '_';
        $this->connection = Database::connect($config, false);
        $this->connection->initialize();
        $name = $this->connection->prefixTable('loans');
        $this->connection->query("CREATE TEMPORARY TABLE `{$name}` (guid VARCHAR(36) PRIMARY KEY, status VARCHAR(30), currency VARCHAR(3), principal_amount DECIMAL(12,2), total_interest DECIMAL(12,2), total_payable DECIMAL(12,2), outstanding_balance DECIMAL(12,2), disbursed_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB");
        $payments = $this->connection->prefixTable('payments');
        $this->connection->query("CREATE TEMPORARY TABLE `{$payments}` (guid VARCHAR(36) PRIMARY KEY, loan_guid VARCHAR(36), amount DECIMAL(12,2), created_at DATETIME NULL, deleted_at DATETIME NULL) ENGINE=InnoDB");
        $this->repository = new AppRepository();

        foreach ([
            ['l1', 'active', 'ARS', 1000, 200, '2026-03-01 00:00:00', null],
            ['l2', 'paid', 'ARS', 3000, 600, '2026-03-15 10:00:00', null],
            ['l3', 'defaulted', 'USD', 500, 50, '2026-03-31 23:59:59', null],
            ['l4', 'active', 'ARS', 9999, 1, '2026-04-01 00:00:00', null],
            ['l5', 'active', 'ARS', 7777, 1, '2026-02-28 23:59:59', null],
            ['l6', 'active', 'ARS', 8888, 1, '2026-03-10 00:00:00', '2026-03-11 00:00:00'],
            ['l7', 'active', 'USD', 1500, 150, '2026-05-20 09:00:00', null],
        ] as [$guid, $status, $currency, $principal, $interest, $date, $deleted]) {
            $this->connection->table('loans')->insert([
                'guid' => $guid, 'status' => $status, 'currency' => $currency,
                'principal_amount' => $principal, 'total_interest' => $interest,
                'total_payable' => $principal + $interest, 'outstanding_balance' => $status === 'paid' ? 0 : $principal,
                'disbursed_at' => $date, 'deleted_at' => $deleted,
            ]);
        }

        // Payments on any date count toward the loan; deleted payments and loans outside the range do not.
        foreach ([
            ['p1', 'l1', 300, '2026-04-01 10:00:00', null],
            ['p2', 'l2', 3600, '2026-09-01 10:00:00', null],
            ['p3', 'l1', 999, '2026-04-02 10:00:00', '2026-04-03 00:00:00'],
            ['p4', 'l4', 5000, '2026-04-05 10:00:00', null],
            ['p5', 'l3', 110, '2026-04-10 10:00:00', null],
        ] as [$guid, $loan, $amount, $date, $deleted]) {
            $this->connection->table('payments')->insert(['guid' => $guid, 'loan_guid' => $loan, 'amount' => $amount, 'created_at' => $date, 'deleted_at' => $deleted]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            foreach (['loans', 'payments'] as $table) {
                $name = $this->connection->prefixTable($table);
                $this->connection->query("DROP TEMPORARY TABLE IF EXISTS `{$name}`");
            }
            $this->connection->close();
        }
        parent::tearDown();
    }

    public function testRangeIsInclusiveAndExcludesDeletedLoans(): void
    {
        $summary = $this->repository->getApprovedLoansSummary('2026-03-01', '2026-03-31', $this->connection);

        $this->assertSame(3, $summary['count']);
        $this->assertSame(['ARS', 'USD'], array_keys($summary['by_currency']));
        $this->assertSame(2, $summary['by_currency']['ARS']['count']);
        $this->assertSame(4000.0, $summary['by_currency']['ARS']['principal']);
        $this->assertSame(800.0, $summary['by_currency']['ARS']['interest']);
        $this->assertSame(4800.0, $summary['by_currency']['ARS']['payable']);
        $this->assertSame(1000.0, $summary['by_currency']['ARS']['outstanding']);
        $this->assertSame(2000.0, $summary['by_currency']['ARS']['avg_ticket']);
        $this->assertSame(3900.0, $summary['by_currency']['ARS']['collected']);
        $this->assertSame(81.3, $summary['by_currency']['ARS']['collected_pct']);
        $this->assertSame(110.0, $summary['by_currency']['USD']['collected']);
        $this->assertSame(1, $summary['by_currency']['USD']['count']);
        $this->assertSame(500.0, $summary['by_currency']['USD']['principal']);
    }

    public function testShortRangesUseDailyBuckets(): void
    {
        $summary = $this->repository->getApprovedLoansSummary('2026-03-01', '2026-03-31', $this->connection);

        $this->assertSame('day', $summary['chart']['granularity']);
        $this->assertCount(31, $summary['chart']['labels']);
        $this->assertSame('01 Mar', $summary['chart']['labels'][0]);
        $ars = $summary['chart']['series'][0];
        $this->assertSame('ARS', $ars['name']);
        $this->assertSame(1000.0, $ars['data'][0]);
        $this->assertSame(3000.0, $ars['data'][14]);
    }

    public function testLongRangesUseMonthlyBuckets(): void
    {
        $summary = $this->repository->getApprovedLoansSummary('2026-01-01', '2026-06-30', $this->connection);

        $this->assertSame('month', $summary['chart']['granularity']);
        $this->assertSame(['Ene 2026', 'Feb 2026', 'Mar 2026', 'Abr 2026', 'May 2026', 'Jun 2026'], $summary['chart']['labels']);
        $usd = $summary['chart']['series'][1];
        $this->assertSame('USD', $usd['name']);
        $this->assertSame([0.0, 0.0, 500.0, 0.0, 1500.0, 0.0], $usd['data']);
    }

    public function testEmptyRangeReturnsZeroTotals(): void
    {
        $summary = $this->repository->getApprovedLoansSummary('2025-01-01', '2025-01-31', $this->connection);

        $this->assertSame(0, $summary['count']);
        $this->assertSame([], $summary['by_currency']);
        $this->assertSame([], $summary['chart']['series']);
    }
}
