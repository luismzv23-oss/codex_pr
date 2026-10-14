<?php

use CodeIgniter\Shield\Auth;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class FirstCollectionPresentationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['app', 'auth']);
        unset($_SESSION['_ci_old_input']);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['_ci_old_input']);
        parent::tearDown();
    }

    private function actor(bool $schedule): void
    {
        $user = $this->createMock(User::class);
        $user->username = 'test-user';
        $user->method('can')->willReturnCallback(static fn(string $permission): bool => $permission !== 'applications.schedule' || $schedule);
        $auth = $this->createMock(Auth::class);
        $auth->method('setAuthenticator')->willReturnSelf();
        $auth->method('user')->willReturn($user);
        Services::injectMock('auth', $auth);
    }

    private function createData(): array
    {
        return ['title' => 'Nueva solicitud', 'customers' => [], 'selectedCustomer' => null, 'systems' => [['code' => 'french']]];
    }

    public function testCheckboxRequiresPermission(): void
    {
        $this->actor(false);
        $this->assertStringNotContainsString('name="custom_collection_date"', view('loan_applications/create', $this->createData()));
        $this->actor(true);
        $html = view('loan_applications/create', $this->createData());
        $this->assertStringContainsString('name="custom_collection_date"', $html);
        $this->assertMatchesRegularExpression('/id="first-collection-date"[^>]*disabled/', $html);
    }

    public function testValidationFailureRestoresCheckboxAndChosenDate(): void
    {
        $this->actor(true);
        $_SESSION['_ci_old_input'] = ['post' => ['custom_collection_date' => '1', 'first_collection_date' => '2096-01-31']];
        $html = view('loan_applications/create', $this->createData());
        $this->assertMatchesRegularExpression('/name="custom_collection_date"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/id="first-collection-date"[^>]*value="2096-01-31"[^>]*required/', $html);
    }

    public function testPendingCustomDateCanBeCorrectedBeforeApprovalOnly(): void
    {
        $this->actor(true);
        $application = ['guid' => 'app', 'customer_name' => 'Cliente', 'status' => 'draft', 'requested_amount' => 1000,
            'approved_amount' => null, 'currency' => 'ARS', 'amortization_type' => 'french', 'interest_rate' => 0.10,
            'term_months' => 3, 'created_at' => '2026-01-01', 'disbursed_at' => null, 'first_collection_date' => '2000-01-31'];
        $html = view('loan_applications/show', ['title' => 'Solicitud', 'application' => $application]);
        $this->assertStringContainsString('type="date" name="first_collection_date"', $html);
        $this->assertStringContainsString('31/01/2000', $html);
        $application['linked_loan_guid'] = 'loan';
        $html = view('loan_applications/show', ['title' => 'Solicitud', 'application' => $application]);
        $this->assertStringNotContainsString('type="date" name="first_collection_date"', $html);
    }
}
