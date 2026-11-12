<?php

use App\Support\AppRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/** Fixtures are confined to connection-local temporary tables with a random prefix. */
final class PortfolioSnapshotTest extends CIUnitTestCase
{
    private ?BaseConnection $connection = null;
    private AppRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        helper('app');
        $config = config('Database')->default;
        if ($config['DBDriver'] !== 'MySQLi' || !extension_loaded('mysqli')) $this->markTestSkipped('Requires MySQLi.');
        $config['DBPrefix'] = 'snapshot_kpi_test_' . bin2hex(random_bytes(6)) . '_';
        $this->connection = Database::connect($config, false);
        $this->connection->initialize();
        foreach ([
            'customers' => 'created_at DATETIME NULL, deleted_at DATETIME NULL',
            'loans' => 'currency VARCHAR(3), disbursed_at DATETIME NULL, closed_at DATETIME NULL, deleted_at DATETIME NULL',
            'installments' => 'loan_guid VARCHAR(36), due_date DATE, total_amount DECIMAL(12,2), late_fee DECIMAL(12,2), paid_amount DECIMAL(12,2), deleted_at DATETIME NULL',
            'payments' => 'installment_guid VARCHAR(36), amount DECIMAL(12,2), created_at DATETIME NULL, deleted_at DATETIME NULL',
        ] as $table => $columns) {
            $name = $this->connection->prefixTable($table);
            $this->connection->query("CREATE TEMPORARY TABLE `{$name}` (guid VARCHAR(36) PRIMARY KEY, {$columns}) ENGINE=InnoDB");
        }
        $this->repository = new AppRepository();

        foreach ([
            ['c1', '2026-01-01 10:00:00', null],
            ['c2', '2026-03-15 23:59:59', null],
            ['c3', '2026-03-16 00:00:00', null],            // created after asOf
            ['c4', '2026-01-01 10:00:00', '2026-02-01 00:00:00'], // deleted
        ] as [$guid, $created, $deleted]) {
            $this->connection->table('customers')->insert(['guid' => $guid, 'created_at' => $created, 'deleted_at' => $deleted]);
        }

        foreach ([
            ['ars', 'ARS', '2026-01-05 10:00:00', null, null],
            ['usd', 'USD', '2026-02-05 10:00:00', '2026-04-01 00:00:00', null], // closed after asOf: still open
            ['closed', 'ARS', '2026-01-05 10:00:00', '2026-03-10 00:00:00', null], // closed before asOf
            ['future', 'ARS', '2026-03-20 10:00:00', null, null],                 // disbursed after asOf
            ['gone', 'ARS', '2026-01-05 10:00:00', null, '2026-02-01 00:00:00'],
        ] as [$guid, $currency, $disbursed, $closed, $deleted]) {
            $this->connection->table('loans')->insert(['guid' => $guid, 'currency' => $currency, 'disbursed_at' => $disbursed, 'closed_at' => $closed, 'deleted_at' => $deleted]);
        }

        foreach ([
            // Fully paid today, but the payment came after asOf: overdue at asOf.
            ['i1', 'ars', '2026-03-01', 1000, 100, 1100],
            // Partially paid before asOf: 400 still owed.
            ['i2', 'ars', '2026-03-05', 1000, 0, 600],
            // Paid before asOf: not overdue.
            ['i3', 'ars', '2026-02-01', 1000, 0, 1000],
            ['i4', 'usd', '2026-03-10', 200, 0, 0],
            // Due on asOf: neither overdue nor upcoming.
            ['i5', 'ars', '2026-03-15', 500, 0, 0],
            // Upcoming window (asOf, asOf+7]: i6 and i7 count; i8 is paid; i9 is outside the window.
            ['i6', 'ars', '2026-03-16', 500, 0, 0],
            ['i7', 'usd', '2026-03-22', 500, 0, 0],
            ['i8', 'ars', '2026-03-20', 500, 0, 500],
            ['i9', 'ars', '2026-03-23', 500, 0, 0],
            // Installment of a deleted loan: ignored.
            ['i10', 'gone', '2026-03-01', 9999, 0, 0],
        ] as [$guid, $loan, $due, $total, $fee, $paid]) {
            $this->connection->table('installments')->insert([
                'guid' => $guid, 'loan_guid' => $loan, 'due_date' => $due, 'total_amount' => $total,
                'late_fee' => $fee, 'paid_amount' => $paid, 'deleted_at' => null,
            ]);
        }

        foreach ([
            ['p1', 'i1', 1100, '2026-03-20 10:00:00', null],
            ['p2', 'i2', 600, '2026-03-06 10:00:00', null],
            ['p3', 'i3', 1000, '2026-02-01 10:00:00', null],
            ['p4', 'i8', 500, '2026-03-14 10:00:00', null],
            // Deleted later payment must not be subtracted.
            ['p5', 'i3', 1000, '2026-03-30 10:00:00', '2026-03-31 00:00:00'],
        ] as [$guid, $installment, $amount, $date, $deleted]) {
            $this->connection->table('payments')->insert(['guid' => $guid, 'installment_guid' => $installment, 'amount' => $amount, 'created_at' => $date, 'deleted_at' => $deleted]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            foreach (['customers', 'loans', 'installments', 'payments'] as $table) {
                $name = $this->connection->prefixTable($table);
                $this->connection->query("DROP TEMPORARY TABLE IF EXISTS `{$name}`");
            }
            $this->connection->close();
        }
        parent::tearDown();
    }

    public function testSnapshotAsOfDate(): void
    {
        $snapshot = $this->repository->getPortfolioSnapshot('2026-03-15', $this->connection);

        $this->assertSame('2026-03-15', $snapshot['as_of']);
        $this->assertSame('2026-03-22', $snapshot['upcoming_to']);
        $this->assertSame(2, $snapshot['customers']);
        $this->assertSame(2, $snapshot['active_loans']);
        $this->assertSame(['ARS' => 1, 'USD' => 1], $snapshot['active_loans_by_currency']);
        $this->assertSame(['ARS' => 1500.0, 'USD' => 200.0], $snapshot['overdue_by_currency']);
        $this->assertSame(2, $snapshot['upcoming_installments']);
    }

    public function testSnapshotLaterIncludesPaymentsMade(): void
    {
        $snapshot = $this->repository->getPortfolioSnapshot('2026-03-31', $this->connection);

        // i1 paid on 03-20; remaining overdue ARS: i2 400 + i5 500 + i6 500 + i9 500.
        $this->assertSame(['ARS' => 1900.0, 'USD' => 700.0], $snapshot['overdue_by_currency']);
        $this->assertSame(3, $snapshot['customers']);
        $this->assertSame(3, $snapshot['active_loans']);
    }

    public function testReportTabValidation(): void
    {
        $this->assertSame('situacion', AppRepository::normalizeReportTab(null));
        $this->assertSame('situacion', AppRepository::normalizeReportTab('<script>'));
        $this->assertSame('cobranza', AppRepository::normalizeReportTab('cobranza'));
        $this->assertSame(['situacion', 'aprobados', 'cobranza'], array_keys(AppRepository::REPORT_TABS));
    }
}
