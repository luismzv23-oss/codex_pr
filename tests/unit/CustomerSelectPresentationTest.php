<?php

use CodeIgniter\Shield\Auth;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class CustomerSelectPresentationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['app', 'auth']);
        unset($_SESSION['_ci_old_input']);
        $user = $this->createMock(User::class);
        $user->username = 'test-admin';
        $user->method('can')->willReturn(true);
        $auth = $this->createMock(Auth::class);
        $auth->method('setAuthenticator')->willReturnSelf();
        $auth->method('user')->willReturn($user);
        Services::injectMock('auth', $auth);
    }

    protected function tearDown(): void
    {
        unset($_SESSION['_ci_old_input']);
        parent::tearDown();
    }

    private function data(): array
    {
        return ['title' => 'Nueva solicitud', 'selectedCustomer' => null,
            'customers' => [
                ['guid' => 'c1', 'full_name' => 'María José López', 'dni' => '20-33.095.790-6'],
                ['guid' => 'c2', 'full_name' => 'María José López', 'dni' => '42081542'],
                ['guid' => 'c3', 'full_name' => '<script>Cliente</script>', 'dni' => ''],
            ],
            'systems' => [['code' => 'french']],
        ];
    }

    public function testNamesAndDocumentsArePresentWithNativeSelectFallback(): void
    {
        $html = view('loan_applications/create', $this->data());
        $this->assertStringContainsString('data-customer-search', $html);
        $this->assertStringContainsString('María José López — 20-33.095.790-6', $html);
        $this->assertStringContainsString('María José López — 42081542', $html);
        $this->assertStringContainsString('Sin documento', $html);
        $this->assertStringContainsString('name="customer_guid"', $html);
        $this->assertStringContainsString('assets/js/customer-select.js', html_entity_decode($html));
        $this->assertStringNotContainsString('<script>Cliente</script>', $html);
    }

    public function testPreviousSelectionIsRestoredAfterValidationErrors(): void
    {
        $_SESSION['_ci_old_input'] = ['post' => ['customer_guid' => 'c2']];
        $html = view('loan_applications/create', $this->data());
        $this->assertMatchesRegularExpression('/<option value="c2"[^>]*selected>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="c1"[^>]*selected>/', $html);
    }

    public function testOriginatingCustomerStaysLockedWithTheSamePostedIdentifier(): void
    {
        $data = $this->data();
        $data['selectedCustomer'] = $data['customers'][0];
        $html = view('loan_applications/create', $data);
        $this->assertStringContainsString('type="hidden" name="customer_guid" value="c1"', $html);
        $this->assertStringContainsString('type="hidden" name="source_customer_guid" value="c1"', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*name="customer_guid_locked"[^>]*disabled/', $html);
        $this->assertMatchesRegularExpression('/<option value="c1"[^>]*selected>/', $html);
    }
}
