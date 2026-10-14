<?php

use App\Support\AppRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/** All fixtures live in randomly prefixed, connection-local TEMPORARY tables. */
final class ApplicationPaginationTest extends CIUnitTestCase
{
    private ?BaseConnection $connection = null;
    private array $tables = ['customers', 'loan_applications', 'loans'];
    private array $filters = ['buscar' => '', 'estado' => '', 'moneda' => '', 'sistema' => ''];
    private AppRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database')->default;
        if ($config['DBDriver'] !== 'MySQLi' || !extension_loaded('mysqli')) {
            $this->markTestSkipped('Requires MySQLi for temporary-table pagination tests.');
        }
        $config['DBPrefix'] = 'application_page_test_' . bin2hex(random_bytes(6)) . '_';
        $this->connection = Database::connect($config, false);
        $this->connection->initialize();
        $schemas = [
            'customers' => 'first_name VARCHAR(100), last_name VARCHAR(100), deleted_at DATETIME NULL',
            'loan_applications' => 'customer_guid VARCHAR(36), status VARCHAR(30), currency VARCHAR(3), amortization_type VARCHAR(30), requested_amount DECIMAL(12,2), interest_rate DECIMAL(8,4), term_months INT, created_at DATETIME, deleted_at DATETIME NULL',
            'loans' => 'customer_guid VARCHAR(36), application_guid VARCHAR(36) NULL, status VARCHAR(30), outstanding_balance DECIMAL(12,4), deleted_at DATETIME NULL',
        ];
        foreach ($schemas as $table => $columns) {
            $name = $this->connection->prefixTable($table);
            $this->connection->query("CREATE TEMPORARY TABLE `{$name}` (guid VARCHAR(36) PRIMARY KEY, {$columns}) ENGINE=InnoDB");
        }
        $this->repository = new AppRepository();
        $this->connection->table('customers')->insert(['guid' => 'alice', 'first_name' => 'Alice', 'last_name' => 'Prueba']);
        $this->connection->table('customers')->insert(['guid' => 'bob', 'first_name' => 'Bob', 'last_name' => 'Prueba']);
        for ($i = 1; $i <= 20; $i++) {
            $status = $i <= 9 ? 'approved' : ($i <= 12 ? 'draft' : ($i <= 14 ? 'evaluation' : ($i === 15 ? 'rejected' : ($i === 16 ? 'paid' : 'approved'))));
            $this->connection->table('loan_applications')->insert([
                'guid' => sprintf('app%02d', $i), 'customer_guid' => ($i >= 10 && $i <= 12) || $i === 15 || $i === 18 || $i === 19 ? 'bob' : 'alice',
                'status' => $status, 'currency' => $i >= 9 && $i <= 12 ? 'USD' : 'ARS',
                'amortization_type' => $i >= 9 && $i <= 12 ? 'german' : ($i === 13 || $i === 14 ? 'american' : 'french'),
                'requested_amount' => 1000, 'interest_rate' => 0.25, 'term_months' => 3,
                'created_at' => '2026-01-01 00:00:00', 'deleted_at' => $i === 20 ? '2026-01-02 00:00:00' : null,
            ]);
        }
        foreach ([
            ['guid' => 'active', 'customer_guid' => 'alice', 'application_guid' => 'app01', 'status' => 'active', 'outstanding_balance' => 10],
            ['guid' => 'paid', 'customer_guid' => 'alice', 'application_guid' => 'app17', 'status' => 'paid_off', 'outstanding_balance' => 0],
            ['guid' => 'zero', 'customer_guid' => 'bob', 'application_guid' => 'app18', 'status' => 'active', 'outstanding_balance' => 0],
            ['guid' => 'rounded', 'customer_guid' => 'bob', 'application_guid' => 'app19', 'status' => 'active', 'outstanding_balance' => 0.004],
            ['guid' => 'unrelated-paid', 'customer_guid' => 'alice', 'application_guid' => null, 'status' => 'paid', 'outstanding_balance' => 0],
            ['guid' => 'soft-paid', 'customer_guid' => 'alice', 'application_guid' => 'app02', 'status' => 'paid_off', 'outstanding_balance' => 0, 'deleted_at' => '2026-01-02 00:00:00'],
            ['guid' => 'second-active', 'customer_guid' => 'alice', 'application_guid' => 'app01', 'status' => 'active', 'outstanding_balance' => 10],
        ] as $loan) {
            $this->connection->table('loans')->insert($loan);
        }
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            foreach ($this->tables as $table) {
                $name = $this->connection->prefixTable($table);
                $this->connection->query("DROP TEMPORARY TABLE IF EXISTS `{$name}`");
            }
            $this->connection->close();
        }
        parent::tearDown();
    }

    private function page(array $filters = [], int $page = 1): array
    {
        return $this->repository->getApplicationPage(array_merge($this->filters, $filters), $page, $this->connection);
    }

    public function testSixPerPageWithNoDuplicatesAndSummariesAcrossAllResults(): void
    {
        $seen = [];
        foreach ([1 => 6, 2 => 6, 3 => 3] as $number => $size) {
            $data = $this->page([], $number);
            $this->assertCount($size, $data['applications']);
            $this->assertSame(15, $data['pagination']['total']);
            $this->assertSame(6, $data['pagination']['per_page']);
            $this->assertSame(['draft' => 3, 'evaluation' => 2, 'approved' => 9], $data['summary']);
            foreach ($data['applications'] as $application) {
                $this->assertArrayNotHasKey($application['guid'], $seen);
                $seen[$application['guid']] = true;
            }
        }
        $this->assertCount(15, $seen);
        foreach (['app16', 'app17', 'app18', 'app19', 'app20'] as $excluded) $this->assertArrayNotHasKey($excluded, $seen);
        $this->assertArrayHasKey('app02', $seen, 'A soft-deleted closed loan must not hide its application.');
    }

    public function testCountsIncludePaidLoansAndExcludeDeletedLoans(): void
    {
        foreach ([['buscar' => 'Alice', 'count' => 4], ['buscar' => 'Bob', 'count' => 2]] as $case) {
            foreach ($this->page(['buscar' => $case['buscar']])['applications'] as $application) {
                $this->assertSame($case['count'], $application['loan_count']);
            }
        }
    }

    public function testCombinedFiltersAndLiteralSearch(): void
    {
        $data = $this->page(['buscar' => 'Alice Prueba', 'estado' => 'approved', 'moneda' => 'USD', 'sistema' => 'german']);
        $this->assertSame(['app09'], array_column($data['applications'], 'guid'));
        $this->assertSame(['draft' => 0, 'evaluation' => 0, 'approved' => 1], $data['summary']);
        $this->assertSame(0, $this->page(['buscar' => '%'])['pagination']['total']);
        $this->assertSame(0, $this->page(['buscar' => "' OR 1=1 --"])['pagination']['total']);
    }

    public function testEmptyResultsAndOutOfRangePages(): void
    {
        $this->assertSame(1, $this->page([], -5)['pagination']['page']);
        $this->assertSame(3, $this->page([], 999)['pagination']['page']);
        $data = $this->page(['buscar' => 'Missing'], 99);
        $this->assertSame([], $data['applications']);
        $this->assertSame(['draft' => 0, 'evaluation' => 0, 'approved' => 0], $data['summary']);
        $this->assertSame(['page' => 1, 'per_page' => 6, 'total' => 0, 'pages' => 1, 'from' => 0, 'to' => 0], $data['pagination']);
    }

    public function testModificationUpdatesCountsAndShrinkingResultsClampThePage(): void
    {
        $this->connection->table('loan_applications')->where('guid', 'app01')->update(['status' => 'paid']);
        $this->assertSame(8, $this->page()['summary']['approved']);
        $this->connection->table('loan_applications')->whereIn('guid', ['app12', 'app13', 'app14', 'app15'])->delete();
        $data = $this->page([], 3);
        $this->assertSame(10, $data['pagination']['total']);
        $this->assertSame(2, $data['pagination']['page']);
        $this->assertCount(4, $data['applications']);
    }

    public function testClientWithoutLoansAndUnavailableClientNames(): void
    {
        $this->connection->table('customers')->insert(['guid' => 'new-client', 'first_name' => 'Nuevo', 'last_name' => 'Cliente']);
        $this->connection->table('loan_applications')->insert([
            'guid' => 'new-app', 'customer_guid' => 'new-client', 'status' => 'draft', 'currency' => 'ARS',
            'amortization_type' => 'french', 'created_at' => '2026-02-01 00:00:00',
        ]);
        $this->assertSame(0, $this->page(['buscar' => 'Nuevo'])['applications'][0]['loan_count']);
        $this->connection->table('customers')->where('guid', 'new-client')->update(['deleted_at' => '2026-02-02 00:00:00']);
        $data = $this->page(['buscar' => 'Cliente no disponible']);
        $this->assertSame(['new-app'], array_column($data['applications'], 'guid'));
    }
}
