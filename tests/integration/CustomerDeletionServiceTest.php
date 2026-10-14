<?php

use App\Services\CustomerDeletionService;
use App\Services\PdfDocumentService;
use CodeIgniter\Database\MySQLi\Connection;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;

// CodeIgniter derives these driver names from a Connection subclass name.
class_alias(\CodeIgniter\Database\MySQLi\Builder::class, 'CustomerDeletionTestBuilder');
class_alias(\CodeIgniter\Database\MySQLi\Result::class, 'CustomerDeletionTestResult');

/** Tests write ONLY to uniquely prefixed, connection-local TEMPORARY tables. */
final class CustomerDeletionServiceTest extends CIUnitTestCase
{
    private ?CustomerDeletionTestConnection $connection = null;
    private array $tables = ['customers', 'loan_applications', 'loans', 'installments', 'payments', 'notifications', 'audit_logs'];
    private string $directory = '';
    private CustomerDeletionTestDocuments $documents;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database')->default;
        if ($config['DBDriver'] !== 'MySQLi' || !extension_loaded('mysqli')) {
            $this->markTestSkipped('Requires MySQLi for isolated temporary-table integration tests.');
        }
        $config['DBPrefix'] = 'customer_delete_test_' . bin2hex(random_bytes(6)) . '_';
        $config['DBDebug'] = true;
        $this->connection = new CustomerDeletionTestConnection($config);
        // A connection failure is a test failure, never a demo fallback.
        $this->connection->initialize();
        $schemas = [
            'customers' => 'first_name VARCHAR(100), last_name VARCHAR(100), deleted_at DATETIME NULL',
            'loan_applications' => 'customer_guid VARCHAR(36), status VARCHAR(30), deleted_at DATETIME NULL',
            'loans' => 'customer_guid VARCHAR(36), application_guid VARCHAR(36), status VARCHAR(30), deleted_at DATETIME NULL',
            'installments' => 'loan_guid VARCHAR(36), deleted_at DATETIME NULL',
            'payments' => 'customer_guid VARCHAR(36) NULL, loan_guid VARCHAR(36) NULL, installment_guid VARCHAR(36) NULL, deleted_at DATETIME NULL',
            'notifications' => 'customer_guid VARCHAR(36) NULL, loan_guid VARCHAR(36) NULL',
            'audit_logs' => 'entity_type VARCHAR(100), entity_guid VARCHAR(36)',
        ];
        foreach ($schemas as $table => $columns) {
            $name = $this->connection->prefixTable($table);
            $this->connection->query("CREATE TEMPORARY TABLE `{$name}` (guid VARCHAR(36) PRIMARY KEY, {$columns}) ENGINE=InnoDB");
        }
        $this->directory = sys_get_temp_dir() . '/customer-delete-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
        $this->documents = new CustomerDeletionTestDocuments($this->directory);
        $this->admin = $this->createMock(User::class);
        $this->admin->method('can')->with('customers.delete')->willReturn(true);
        $this->admin->method('getPasswordHash')->willReturn(password_hash('test-admin-password', PASSWORD_DEFAULT));
        $this->seed();
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            $this->connection->failCustomerDelete = false;
            foreach ($this->tables as $table) {
                $name = $this->connection->prefixTable($table);
                $this->connection->query("DROP TEMPORARY TABLE IF EXISTS `{$name}`");
            }
            $this->connection->close();
        }
        if ($this->directory !== '' && is_dir($this->directory)) {
            foreach (glob($this->directory . '/*') as $path) {
                is_dir($path) ? rmdir($path) : unlink($path);
            }
            rmdir($this->directory);
        }
        parent::tearDown();
    }

    private function seed(): void
    {
        $rows = [
            'customers' => [
                ['guid' => 'target', 'first_name' => 'Cliente', 'last_name' => 'Prueba'],
                ['guid' => 'other', 'first_name' => 'Otro', 'last_name' => 'Cliente'],
            ],
            'loan_applications' => [
                ['guid' => 'app1', 'customer_guid' => 'target', 'status' => 'approved'],
                ['guid' => 'app2', 'customer_guid' => 'target', 'status' => 'rejected', 'deleted_at' => '2026-01-01 00:00:00'],
                ['guid' => 'other-app', 'customer_guid' => 'other', 'status' => 'approved'],
            ],
            'loans' => [
                ['guid' => 'loan1', 'customer_guid' => 'target', 'application_guid' => 'app1', 'status' => 'paid_off'],
                ['guid' => 'loan2', 'customer_guid' => 'target', 'application_guid' => 'app2', 'status' => 'active', 'deleted_at' => '2026-01-01 00:00:00'],
                ['guid' => 'other-loan', 'customer_guid' => 'other', 'application_guid' => 'other-app', 'status' => 'active'],
            ],
            'installments' => [
                ['guid' => 'inst1', 'loan_guid' => 'loan1'],
                ['guid' => 'inst2', 'loan_guid' => 'loan2', 'deleted_at' => '2026-01-01 00:00:00'],
                ['guid' => 'other-inst', 'loan_guid' => 'other-loan'],
            ],
            'payments' => [
                ['guid' => 'pay1', 'customer_guid' => 'target', 'loan_guid' => 'loan1', 'installment_guid' => 'inst1'],
                ['guid' => 'pay2', 'customer_guid' => null, 'loan_guid' => 'loan2', 'installment_guid' => 'inst2', 'deleted_at' => '2026-01-01 00:00:00'],
                ['guid' => 'pay3', 'customer_guid' => 'target'],
                ['guid' => 'pay4', 'customer_guid' => null, 'installment_guid' => 'inst1'],
                ['guid' => 'other-pay', 'customer_guid' => 'other', 'loan_guid' => 'other-loan', 'installment_guid' => 'other-inst'],
            ],
            'notifications' => [
                ['guid' => 'notice1', 'customer_guid' => 'target'],
                ['guid' => 'notice2', 'loan_guid' => 'loan2'],
                ['guid' => 'other-notice', 'customer_guid' => 'other', 'loan_guid' => 'other-loan'],
            ],
            'audit_logs' => [],
        ];
        $types = ['customers' => 'customer', 'loan_applications' => 'loan_application', 'loans' => 'loan', 'installments' => 'installment', 'payments' => 'payment', 'notifications' => 'notification'];
        foreach ($types as $table => $type) {
            foreach ($rows[$table] as $row) {
                $rows['audit_logs'][] = ['guid' => 'audit-' . $row['guid'], 'entity_type' => $type, 'entity_guid' => $row['guid']];
            }
        }
        // An unrelated entity with the same identifier must retain its audit record.
        $rows['audit_logs'][] = ['guid' => 'audit-unrelated', 'entity_type' => 'unrelated', 'entity_guid' => 'target'];
        foreach ($rows as $table => $records) {
            foreach ($records as $row) $this->connection->table($table)->insert($row);
        }
        file_put_contents($this->documents->contractPath('loan1'), 'test-contract');
        file_put_contents($this->documents->contractPath('other-loan'), 'other-contract');
    }

    private function snapshot(): array
    {
        $snapshot = [];
        foreach ($this->tables as $table) {
            $snapshot[$table] = $this->connection->table($table)->orderBy('guid')->get()->getResultArray();
        }
        return $snapshot;
    }

    public function testPreviewIncludesSoftDeletesAndPaidLoansWithoutChangingData(): void
    {
        $before = $this->snapshot();
        $summary = (new CustomerDeletionService($this->connection, $this->documents))->preview('target', $this->admin);
        $this->assertSame(2, $summary['applications']);
        $this->assertSame(2, $summary['loans']);
        $this->assertSame(2, $summary['installments']);
        $this->assertSame(4, $summary['payments']);
        $this->assertSame(2, $summary['notifications']);
        $this->assertSame($before, $this->snapshot());
    }

    public function testPhysicalCascadePreservesOtherCustomerAndDeletesContract(): void
    {
        $before = $this->snapshot();
        $result = (new CustomerDeletionService($this->connection, $this->documents))->delete('target', $this->admin, 'test-admin-password');
        $this->assertSame(0, $result['pending_contracts']);
        foreach ($this->snapshot() as $table => $rows) {
            $expected = array_values(array_filter($before[$table], static fn(array $row): bool => strpos($row['guid'], 'other') !== false || $row['guid'] === 'audit-unrelated'));
            $this->assertSame($expected, $rows, $table);
        }
        $this->assertFileDoesNotExist($this->documents->contractPath('loan1'));
        $this->assertFileExists($this->documents->contractPath('other-loan'));
    }

    public function testFailureAtFinalDeleteRollsBackAllDatabaseChangesAndKeepsContract(): void
    {
        $before = $this->snapshot();
        $this->connection->failCustomerDelete = true;
        try {
            (new CustomerDeletionService($this->connection, $this->documents))->delete('target', $this->admin, 'test-admin-password');
            $this->fail('Expected injected failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Injected customer delete failure', $exception->getMessage());
        }
        $this->connection->failCustomerDelete = false;
        $this->assertSame($before, $this->snapshot());
        $this->assertFileExists($this->documents->contractPath('loan1'));
    }

    public function testWrongPasswordAndMissingPermissionNeverDeleteRecords(): void
    {
        $before = $this->snapshot();
        $service = new CustomerDeletionService($this->connection, $this->documents);
        try {
            $service->delete('target', $this->admin, 'wrong');
            $this->fail('Wrong password accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('contraseña', $exception->getMessage());
        }
        $operator = $this->createMock(User::class);
        $operator->method('can')->willReturn(false);
        foreach ([$operator, null] as $actor) {
            try {
                $service->delete('target', $actor, 'test-admin-password');
                $this->fail('Unauthorized delete accepted.');
            } catch (DomainException $exception) {
                $this->assertStringContainsString('permiso', $exception->getMessage());
            }
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function testMissingCustomerDoesNotDeleteAnything(): void
    {
        $before = $this->snapshot();
        try {
            (new CustomerDeletionService($this->connection, $this->documents))->delete('missing', $this->admin, 'test-admin-password');
            $this->fail('Expected missing customer error.');
        } catch (OutOfBoundsException $exception) {
            $this->assertStringContainsString('no existe', $exception->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function testFileCleanupFailureIsReportedAfterCommit(): void
    {
        // An empty directory at the expected file path forces unlink to fail safely.
        mkdir($this->documents->contractPath('loan2'));
        $result = (new CustomerDeletionService($this->connection, $this->documents))->delete('target', $this->admin, 'test-admin-password');
        $this->assertSame(1, $result['pending_contracts']);
        $this->assertSame(0, $this->connection->table('customers')->where('guid', 'target')->countAllResults());
        $this->assertFileDoesNotExist($this->documents->contractPath('loan1'));
    }

    public function testCustomerWithoutDependentsCanBeRemovedWithoutTouchingOtherData(): void
    {
        $before = $this->snapshot();
        $this->connection->table('customers')->insert(['guid' => 'empty', 'first_name' => 'Sin', 'last_name' => 'Préstamos']);
        $result = (new CustomerDeletionService($this->connection, $this->documents))->delete('empty', $this->admin, 'test-admin-password');
        $this->assertSame(0, $result['applications']);
        $this->assertSame(0, $result['loans']);
        $this->assertSame(0, $result['pending_contracts']);
        $this->assertSame($before, $this->snapshot());
    }
}

final class CustomerDeletionTestConnection extends Connection
{
    public bool $failCustomerDelete = false;

    public function query(string $sql, $binds = null, bool $setEscapeFlags = true, string $queryClass = '')
    {
        if ($this->failCustomerDelete && stripos($sql, 'DELETE FROM') === 0 && strpos($sql, '`' . $this->prefixTable('customers') . '`') !== false) {
            throw new RuntimeException('Injected customer delete failure');
        }
        return parent::query($sql, $binds, $setEscapeFlags, $queryClass);
    }
}

final class CustomerDeletionTestDocuments extends PdfDocumentService
{
    private string $directory;

    public function __construct(string $directory) { $this->directory = $directory; }

    public function contractPath(string $loanGuid): string
    {
        return $this->directory . '/' . $loanGuid . '-contrato.pdf';
    }
}
