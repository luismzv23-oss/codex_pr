<?php

use App\Support\AppRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/** Fixtures are confined to connection-local temporary tables with a random prefix. */
final class LoanPaginationTest extends CIUnitTestCase
{
    private ?BaseConnection $connection = null;
    private array $filters = ['estado' => 'active', 'buscar' => '', 'estado_detalle' => '', 'moneda' => ''];
    private AppRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        helper('app');
        $config = config('Database')->default;
        if ($config['DBDriver'] !== 'MySQLi' || !extension_loaded('mysqli')) $this->markTestSkipped('Requires MySQLi.');
        $config['DBPrefix'] = 'loan_page_test_' . bin2hex(random_bytes(6)) . '_';
        $this->connection = Database::connect($config, false);
        $this->connection->initialize();
        foreach ([
            'customers' => 'first_name VARCHAR(100), last_name VARCHAR(100), deleted_at DATETIME NULL',
            'loans' => 'customer_guid VARCHAR(36), status VARCHAR(30), currency VARCHAR(3), principal_amount DECIMAL(12,2), outstanding_balance DECIMAL(12,2), next_due_date DATE NULL, created_at DATETIME, disbursed_at DATETIME NULL, deleted_at DATETIME NULL',
        ] as $table => $columns) {
            $name = $this->connection->prefixTable($table);
            $this->connection->query("CREATE TEMPORARY TABLE `{$name}` (guid VARCHAR(36) PRIMARY KEY, {$columns}) ENGINE=InnoDB");
        }
        $this->repository = new AppRepository();
        foreach ([['guid' => 'alice', 'first_name' => 'Alice', 'last_name' => 'Prueba'], ['guid' => 'bob', 'first_name' => 'Bob', 'last_name' => 'Prueba']] as $customer) {
            $this->connection->table('customers')->insert($customer);
        }
        for ($i = 1; $i <= 22; $i++) {
            $status = $i <= 10 ? 'active' : ($i <= 12 ? 'defaulted' : ($i <= 14 ? 'restructured' : ($i <= 18 ? 'paid' : 'paid_off')));
            $this->connection->table('loans')->insert([
                'guid' => sprintf('loan%02d', $i), 'customer_guid' => $i % 2 ? 'alice' : 'bob',
                'status' => $status, 'currency' => $i % 2 ? 'ARS' : 'USD',
                'principal_amount' => 1000, 'outstanding_balance' => $i <= 14 ? 1500 : 0,
                'created_at' => sprintf('2026-01-%02d 00:00:00', $i),
                'disbursed_at' => sprintf('2026-02-%02d 00:00:00', $i),
                'next_due_date' => null, 'deleted_at' => $i === 22 ? '2026-03-01 00:00:00' : null,
            ]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            foreach (['loans', 'customers'] as $table) {
                $name = $this->connection->prefixTable($table);
                $this->connection->query("DROP TEMPORARY TABLE IF EXISTS `{$name}`");
            }
            $this->connection->close();
        }
        parent::tearDown();
    }

    private function page(array $filters = [], int $page = 1): array
    {
        return $this->repository->getLoanPage(array_merge($this->filters, $filters), $page, $this->connection);
    }

    public function testActiveAndCancelledGroupsHaveSixPerPageWithoutDuplicates(): void
    {
        foreach (['active' => [6, 6, 2], 'cancelled' => [6, 1]] as $group => $sizes) {
            $seen = [];
            foreach ($sizes as $index => $size) {
                $data = $this->page(['estado' => $group], $index + 1);
                $this->assertCount($size, $data['loans']);
                $this->assertSame(array_sum($sizes), $data['pagination']['total']);
                $this->assertSame(6, $data['pagination']['per_page']);
                foreach ($data['loans'] as $loan) {
                    $this->assertArrayNotHasKey($loan['guid'], $seen);
                    $seen[$loan['guid']] = true;
                    $this->assertArrayHasKey('outstanding_balance', $loan);
                }
            }
            $this->assertCount(array_sum($sizes), $seen);
            $this->assertArrayNotHasKey('loan22', $seen);
        }
        $this->assertSame('loan14', $this->page()['loans'][0]['guid']);
    }

    public function testAliasSearchUsesGlobalNumberAcrossGroupsAndPages(): void
    {
        $alias = loan_alias(7, 'Alice Prueba');
        $data = $this->page(['buscar' => $alias]);
        $this->assertSame(['loan07'], array_column($data['loans'], 'guid'));
        $this->assertSame($alias, $data['loans'][0]['alias']);
        $paidAlias = loan_alias(17, 'Alice Prueba');
        $this->assertSame(['loan17'], array_column($this->page(['estado' => 'cancelled', 'buscar' => $paidAlias])['loans'], 'guid'));
        $this->assertSame(0, $this->page(['buscar' => $paidAlias])['pagination']['total']);
    }

    public function testCustomerIdDetailedStatusAndCurrencyFilters(): void
    {
        $data = $this->page(['buscar' => 'Alice Prueba', 'estado_detalle' => 'defaulted', 'moneda' => 'ARS']);
        $this->assertSame(['loan11'], array_column($data['loans'], 'guid'));
        $this->assertSame(['loan04'], array_column($this->page(['buscar' => 'loan04'])['loans'], 'guid'));
        $this->assertSame(3, $this->page(['estado' => 'cancelled', 'estado_detalle' => 'paid_off'])['pagination']['total']);
        $this->assertSame(0, $this->page(['buscar' => '%'])['pagination']['total']);
        $this->assertSame(0, $this->page(['buscar' => "' OR 1=1 --"])['pagination']['total']);
    }

    public function testEmptyResultsAndOutOfRangePages(): void
    {
        $this->assertSame(1, $this->page([], -2)['pagination']['page']);
        $this->assertSame(3, $this->page([], 999)['pagination']['page']);
        $data = $this->page(['buscar' => 'Missing'], 5);
        $this->assertSame([], $data['loans']);
        $this->assertSame(['page' => 1, 'per_page' => 6, 'total' => 0, 'pages' => 1, 'from' => 0, 'to' => 0], $data['pagination']);
    }

    public function testPaidLoanMovesGroupsAndReducedResultsClampThePage(): void
    {
        $this->connection->table('loans')->where('guid', 'loan14')->update(['status' => 'paid', 'outstanding_balance' => 0]);
        $this->assertSame(13, $this->page()['pagination']['total']);
        $this->assertSame(8, $this->page(['estado' => 'cancelled'])['pagination']['total']);
        $this->connection->table('loans')->where('guid', 'loan13')->delete();
        $data = $this->page([], 3);
        $this->assertSame(2, $data['pagination']['page']);
        $this->assertSame(12, $data['pagination']['total']);
        $this->assertCount(6, $data['loans']);
    }

    public function testDeletedCustomerUsesExistingUnavailableName(): void
    {
        $this->connection->table('customers')->where('guid', 'alice')->update(['deleted_at' => '2026-03-01 00:00:00']);
        $data = $this->page(['buscar' => 'Cliente no disponible']);
        $this->assertSame(7, $data['pagination']['total']);
        $this->assertSame('Cliente no disponible', $data['loans'][0]['customer_name']);
    }
}
