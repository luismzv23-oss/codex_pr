<?php

use App\Services\LoanWorkflowService;
use App\Services\PdfDocumentService;
use App\Support\AppRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Shield\Auth;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use Config\Services;

/** Writes only to connection-local temporary copies of the application tables. */
final class FirstCollectionApprovalTest extends CIUnitTestCase
{
    private ?BaseConnection $connection = null;
    private array $previousTestsConfig;
    private array $tables = ['customers', 'loan_applications', 'loans', 'installments', 'payments', 'notifications', 'audit_logs', 'collection_methods'];
    private LoanWorkflowService $workflow;
    private string $contractHtml = '';

    protected function setUp(): void
    {
        parent::setUp();
        helper(['app', 'auth']);
        $database = config('Database');
        $this->previousTestsConfig = $database->tests;
        $config = $database->default;
        if ($config['DBDriver'] !== 'MySQLi') $this->markTestSkipped('Requires MySQLi.');
        $sourcePrefix = $config['DBPrefix'];
        $config['DBPrefix'] = 'collection_approval_test_' . bin2hex(random_bytes(6)) . '_';
        $database->tests = $config;
        $this->connection = Database::connect('tests', false);
        $this->connection->initialize();
        foreach ($this->tables as $table) {
            $target = $this->connection->prefixTable($table);
            $source = $sourcePrefix . $table;
            $this->connection->query("CREATE TEMPORARY TABLE `{$target}` LIKE `{$source}`");
        }
        $user = $this->createMock(User::class);
        $user->method('can')->willReturn(true);
        $auth = $this->createMock(Auth::class);
        $auth->method('setAuthenticator')->willReturnSelf();
        $auth->method('user')->willReturn($user);
        $auth->method('id')->willReturn(1);
        Services::injectMock('auth', $auth);
        $documents = $this->createMock(PdfDocumentService::class);
        $documents->method('render')->willReturnCallback(function ($view, $data) {
            $html = view($view, $data);
            $this->contractHtml = $html;
            return $html;
        });
        $documents->method('saveContract')->willReturnCallback(static function () {});
        $this->workflow = new LoanWorkflowService($documents);
        $this->connection->table('customers')->insert([
            'guid' => 'customer-test', 'first_name' => 'Cliente', 'last_name' => 'Prueba',
            'dni' => '12345678', 'email' => 'fixture@example.com', 'phone' => '12345678',
            'created_at' => '2026-01-01 00:00:00',
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->connection !== null) {
            foreach (array_reverse($this->tables) as $table) {
                $name = $this->connection->prefixTable($table);
                $this->connection->query("DROP TEMPORARY TABLE IF EXISTS `{$name}`");
            }
            $this->connection->close();
        }
        if (isset($this->previousTestsConfig)) {
            config('Database')->tests = $this->previousTestsConfig;
            Database::connect('tests', false);
        }
        parent::tearDown();
    }

    private function application(?string $date): void
    {
        $this->connection->table('loan_applications')->insert([
            'guid' => 'application-test', 'customer_guid' => 'customer-test', 'requested_amount' => 100000,
            'currency' => 'ARS', 'interest_rate' => 0.10, 'term_months' => 3, 'amortization_type' => 'french',
            'status' => 'draft', 'first_collection_date' => $date, 'created_at' => '2026-01-01 00:00:00',
        ]);
    }

    public function testApprovalPersistsDateInstallmentsNextDueDateAndContract(): void
    {
        $this->application('2096-01-31');
        $result = $this->workflow->approve('application-test', 100000);
        $loan = $this->connection->table('loans')->where('guid', $result['loan_guid'])->get()->getRowArray();
        $this->assertSame('2096-01-31', $loan['first_collection_date']);
        $this->assertSame('2096-01-31', $loan['next_due_date']);
        $installments = $this->connection->table('installments')->orderBy('installment_number')->get()->getResultArray();
        $this->assertSame(['2096-01-31', '2096-02-29', '2096-03-31'], array_column($installments, 'due_date'));
        $this->assertStringContainsString('31/01/2096', $this->contractHtml);
        $this->assertStringContainsString('29/02/2096', $this->contractHtml);
        $this->assertSame(1, $this->connection->table('audit_logs')->countAllResults());
        // Force synchronization and confirm the persisted anchor is honored.
        $this->connection->table('installments')->where('installment_number', 1)->update(['due_date' => '2096-01-05']);
        (new AppRepository())->getLoan($loan['guid']);
        $this->assertSame('2096-01-31', $this->connection->table('installments')->where('installment_number', 1)->get()->getRowArray()['due_date']);
        $repeat = $this->workflow->approve('application-test', 100000, '2096-02-01');
        $this->assertFalse($repeat['created']);
        $this->assertSame(1, $this->connection->table('loans')->countAllResults());
    }

    public function testExpiredDateBlocksApprovalUntilAdminCorrectsIt(): void
    {
        $this->application('2000-01-31');
        try {
            $this->workflow->approve('application-test', 100000);
            $this->fail('Expired date approved.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('pasado', $exception->getMessage());
        }
        $this->assertSame(0, $this->connection->table('loans')->countAllResults());
        $this->assertSame('draft', $this->connection->table('loan_applications')->get()->getRowArray()['status']);
        $result = $this->workflow->approve('application-test', 100000, '2096-11-20');
        $this->assertTrue($result['created']);
        $this->assertSame('2096-11-20', $this->connection->table('loan_applications')->get()->getRowArray()['first_collection_date']);
        $this->assertSame('2096-11-20', $this->connection->table('loans')->get()->getRowArray()['next_due_date']);
        $this->assertStringContainsString('20/11/2096', $this->contractHtml);
    }

    public function testAutomaticApprovalKeepsDayFive(): void
    {
        $this->application(null);
        $result = $this->workflow->approve('application-test', 100000);
        $loan = $this->connection->table('loans')->where('guid', $result['loan_guid'])->get()->getRowArray();
        $this->assertNull($loan['first_collection_date']);
        $this->assertSame('05', substr($loan['next_due_date'], -2));
        $this->assertSame(3, $this->connection->table('installments')->countAllResults());
        $this->assertStringContainsString(date('d/m/Y', strtotime($loan['next_due_date'])), $this->contractHtml);
    }
}
