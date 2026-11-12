<?php

use App\Support\AppRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/** Fixtures are confined to connection-local temporary tables with a random prefix. */
final class AuditPaginationTest extends CIUnitTestCase
{
    private ?BaseConnection $connection = null;
    private AppRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        helper('app');
        $config = config('Database')->default;
        if ($config['DBDriver'] !== 'MySQLi' || !extension_loaded('mysqli')) $this->markTestSkipped('Requires MySQLi.');
        $config['DBPrefix'] = 'audit_page_test_' . bin2hex(random_bytes(6)) . '_';
        $this->connection = Database::connect($config, false);
        $this->connection->initialize();
        $name = $this->connection->prefixTable('audit_logs');
        $this->connection->query("CREATE TEMPORARY TABLE `{$name}` (guid VARCHAR(36) PRIMARY KEY, action VARCHAR(100), entity_type VARCHAR(50), entity_guid VARCHAR(36), created_at DATETIME) ENGINE=InnoDB");
        $this->repository = new AppRepository();
        for ($i = 1; $i <= 23; $i++) {
            $this->connection->table('audit_logs')->insert([
                'guid' => sprintf('log%02d', $i), 'action' => 'action.' . $i, 'entity_type' => 'loan',
                'entity_guid' => 'e' . $i, 'created_at' => sprintf('2026-03-%02d 10:00:00', $i),
            ]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            $name = $this->connection->prefixTable('audit_logs');
            $this->connection->query("DROP TEMPORARY TABLE IF EXISTS `{$name}`");
            $this->connection->close();
        }
        parent::tearDown();
    }

    public function testFirstPageHasTenNewestEvents(): void
    {
        $data = $this->repository->getAuditPage(1, 10, $this->connection);

        $this->assertCount(10, $data['logs']);
        $this->assertSame('action.23', $data['logs'][0]['action']);
        $this->assertSame('action.14', $data['logs'][9]['action']);
        $this->assertSame(['page' => 1, 'per_page' => 10, 'total' => 23, 'pages' => 3, 'from' => 1, 'to' => 10], $data['pagination']);
    }

    public function testLastPageAndOutOfRangePage(): void
    {
        $last = $this->repository->getAuditPage(3, 10, $this->connection);
        $this->assertCount(3, $last['logs']);
        $this->assertSame('action.1', $last['logs'][2]['action']);

        $clamped = $this->repository->getAuditPage(99, 10, $this->connection);
        $this->assertSame(3, $clamped['pagination']['page']);
        $this->assertSame($last['logs'], $clamped['logs']);
    }

    public function testPaginationMetaForEmptyList(): void
    {
        $this->assertSame(
            ['page' => 1, 'per_page' => 10, 'total' => 0, 'pages' => 1, 'from' => 0, 'to' => 0],
            AppRepository::paginationMeta(0, 5, 10)
        );
    }
}
