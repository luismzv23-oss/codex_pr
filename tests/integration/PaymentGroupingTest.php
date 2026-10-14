<?php

use App\Support\AppRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/** Fixtures are confined to connection-local TEMPORARY tables with a random prefix. */
final class PaymentGroupingTest extends CIUnitTestCase
{
    private ?BaseConnection $connection = null;
    private array $filters = ['buscar' => '', 'metodo' => '', 'moneda' => ''];
    private AppRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        helper('app');
        $config = config('Database')->default;
        if ($config['DBDriver'] !== 'MySQLi' || !extension_loaded('mysqli')) $this->markTestSkipped('Requires MySQLi.');
        $config['DBPrefix'] = 'payment_group_test_' . bin2hex(random_bytes(6)) . '_';
        $this->connection = Database::connect($config, false);
        $this->connection->initialize();
        foreach ([
            'customers' => 'first_name VARCHAR(100), last_name VARCHAR(100), deleted_at DATETIME NULL',
            'loans' => 'customer_guid VARCHAR(36), created_at DATETIME, disbursed_at DATETIME, deleted_at DATETIME NULL',
            'payments' => 'customer_guid VARCHAR(36) NOT NULL, loan_guid VARCHAR(36), amount DECIMAL(12,2), currency VARCHAR(3), payment_method VARCHAR(30), reference_number VARCHAR(100) NULL, created_at DATETIME, deleted_at DATETIME NULL',
        ] as $table => $columns) {
            $name = $this->connection->prefixTable($table);
            $this->connection->query("CREATE TEMPORARY TABLE `{$name}` (guid VARCHAR(36) PRIMARY KEY, {$columns}) ENGINE=InnoDB");
        }
        $this->repository = new AppRepository();
        for ($i = 1; $i <= 8; $i++) {
            $this->connection->table('customers')->insert(['guid' => 'c' . $i, 'first_name' => 'Cliente', 'last_name' => $i <= 2 ? 'Uno' : 'Prueba' . $i]);
            for ($j = 1; $j <= 2; $j++) {
                $this->connection->table('loans')->insert([
                    'guid' => "loan{$i}-{$j}", 'customer_guid' => 'c' . $i,
                    'created_at' => sprintf('2026-01-%02d 0%d:00:00', $i, $j), 'disbursed_at' => '2026-01-31 00:00:00',
                ]);
                $this->connection->table('payments')->insert([
                    'guid' => "pay{$i}-{$j}", 'customer_guid' => 'c' . $i, 'loan_guid' => "loan{$i}-{$j}",
                    'amount' => 100, 'currency' => $j === 1 ? 'ARS' : 'USD', 'payment_method' => $j === 1 ? 'transfer' : 'cash',
                    'reference_number' => "REF{$i}-{$j}", 'created_at' => sprintf('2026-02-%02d 0%d:00:00', $i, $j),
                ]);
            }
        }
        for ($i = 1; $i <= 10; $i++) $this->connection->table('payments')->insert([
            'guid' => 'extra' . $i, 'customer_guid' => 'c1', 'loan_guid' => 'loan1-1', 'amount' => 50,
            'currency' => 'USD', 'payment_method' => 'cash', 'reference_number' => 'Extra-' . $i,
            'created_at' => sprintf('2026-02-01 05:%02d:00', $i),
        ]);
        $this->connection->table('payments')->insert([
            'guid' => 'deleted-pay', 'customer_guid' => 'c8', 'loan_guid' => 'loan8-1', 'amount' => 100,
            'currency' => 'ARS', 'payment_method' => 'transfer', 'reference_number' => 'Deleted',
            'created_at' => '2026-12-01 00:00:00', 'deleted_at' => '2026-12-02 00:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            foreach (['payments', 'loans', 'customers'] as $table) {
                $name = $this->connection->prefixTable($table);
                $this->connection->query("DROP TEMPORARY TABLE IF EXISTS `{$name}`");
            }
            $this->connection->close();
        }
        parent::tearDown();
    }

    private function page(array $filters = [], int $page = 1): array
    {
        return $this->repository->getPaymentGroupPage(array_merge($this->filters, $filters), $page, $this->connection);
    }

    public function testSixCustomersPerPageWithoutSplittingTheirLoansOrPayments(): void
    {
        $first = $this->page();
        $second = $this->page([], 2);
        $this->assertCount(6, $first['groups']);
        $this->assertCount(2, $second['groups']);
        $this->assertSame(8, $first['pagination']['total']);
        $this->assertSame(['c8', 'c7', 'c6', 'c5', 'c4', 'c3'], array_column($first['groups'], 'guid'));
        $this->assertSame(['c2', 'c1'], array_column($second['groups'], 'guid'));
        $this->assertSame(12, $second['groups'][1]['payment_count']);
        $this->assertSame(2, $second['groups'][1]['loan_count']);
        $seen = [];
        foreach (array_merge($first['groups'], $second['groups']) as $customer) {
            foreach ($customer['loans'] as $loan) {
                foreach ($loan['payments'] as $payment) {
                    $this->assertSame($customer['guid'], $payment['customer_guid']);
                    $this->assertSame($loan['guid'], $payment['loan_guid']);
                    $this->assertArrayNotHasKey($payment['guid'], $seen);
                    $seen[$payment['guid']] = true;
                }
            }
        }
        $this->assertCount(26, $seen);
        $this->assertArrayNotHasKey('deleted-pay', $seen);
    }

    public function testSameNamedClientsStaySeparateAndFiltersOnlyReturnMatchingPayments(): void
    {
        $data = $this->page(['buscar' => 'Cliente Uno', 'metodo' => 'cash', 'moneda' => 'USD']);
        $this->assertSame(['c2', 'c1'], array_column($data['groups'], 'guid'));
        $this->assertSame(11, $data['groups'][1]['payment_count']);
        foreach ($data['groups'] as $customer) foreach ($customer['loans'] as $loan) foreach ($loan['payments'] as $payment) {
            $this->assertSame('cash', $payment['payment_method']);
            $this->assertSame('USD', $payment['currency']);
        }
    }

    public function testReferenceLoanIdAndAliasSearch(): void
    {
        $data = $this->page(['buscar' => 'REF3-1']);
        $this->assertSame(['c3'], array_column($data['groups'], 'guid'));
        $this->assertSame(1, $data['groups'][0]['payment_count']);
        $alias = loan_alias(1, 'Cliente Uno');
        $data = $this->page(['buscar' => $alias]);
        $this->assertSame(['loan1-1'], array_column($data['groups'][0]['loans'], 'guid'));
        $this->assertSame($alias, $data['groups'][0]['loans'][0]['label']);
        $this->assertSame(11, $data['groups'][0]['payment_count']);
        $this->assertSame(11, $this->page(['buscar' => 'loan1-1'])['groups'][0]['payment_count']);
        $this->assertSame(0, $this->page(['buscar' => '%'])['pagination']['total']);
        $this->assertSame(0, $this->page(['buscar' => "' OR 1=1 --"])['pagination']['total']);
    }

    public function testLatestMatchingPaymentOrdersGroupsAndPayments(): void
    {
        $data = $this->page(['buscar' => 'Cliente Uno']);
        $customer = $data['groups'][1];
        $this->assertSame('loan1-1', $customer['loans'][0]['guid']);
        $this->assertSame('extra10', $customer['loans'][0]['payments'][0]['guid']);
        $this->connection->table('payments')->where('guid', 'pay1-1')->update(['created_at' => '2026-03-01 00:00:00']);
        $this->assertSame('c1', $this->page()['groups'][0]['guid']);
    }

    public function testEmptyResultsAndShrinkingPageAreClamped(): void
    {
        $this->assertSame(1, $this->page([], -1)['pagination']['page']);
        $this->assertSame(2, $this->page([], 999)['pagination']['page']);
        $empty = $this->page(['buscar' => 'Missing'], 2);
        $this->assertSame([], $empty['groups']);
        $this->assertSame(['page' => 1, 'per_page' => 6, 'total' => 0, 'pages' => 1, 'from' => 0, 'to' => 0], $empty['pagination']);
        $this->connection->table('payments')->whereIn('customer_guid', ['c7', 'c8'])->delete();
        $this->assertSame(1, $this->page([], 2)['pagination']['page']);
        $this->assertCount(6, $this->page([], 2)['groups']);
    }

    public function testUnavailableCustomerOrLoanDoesNotHideTheirPayments(): void
    {
        $this->connection->table('customers')->where('guid', 'c1')->update(['deleted_at' => '2026-03-01 00:00:00']);
        $this->connection->table('loans')->where('guid', 'loan1-1')->update(['deleted_at' => '2026-03-01 00:00:00']);
        $data = $this->page(['buscar' => 'Cliente no disponible']);
        $this->assertSame(12, $data['groups'][0]['payment_count']);
        $this->assertSame('loan1-1', $data['groups'][0]['loans'][0]['label']);
    }
}
