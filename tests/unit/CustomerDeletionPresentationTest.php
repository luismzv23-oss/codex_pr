<?php

use CodeIgniter\Shield\Auth;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class CustomerDeletionPresentationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['app', 'auth']);
    }

    private function actor(bool $canDelete): void
    {
        $user = $this->createMock(User::class);
        $user->username = 'admin-test';
        $user->method('can')->willReturn($canDelete);
        $auth = $this->createMock(Auth::class);
        $auth->method('setAuthenticator')->willReturnSelf();
        $auth->method('user')->willReturn($user);
        Services::injectMock('auth', $auth);
    }

    private function listData(): array
    {
        return [
            'title' => 'Clientes', 'buscar' => '', 'estado_crediticio' => '', 'estado_kyc' => '',
            'customers' => [[
                'guid' => 'test-customer', 'full_name' => 'Cliente Prueba', 'email' => 'test@example.com',
                'dni' => '12345678', 'credit_status' => 'active', 'credit_limit' => 0,
                'loan_count' => 2, 'created_at' => '2026-01-01 00:00:00',
            ]],
            'pagination' => ['page' => 1, 'pages' => 1, 'from' => 1, 'to' => 1, 'total' => 1],
        ];
    }

    public function testAdminHasBothDeletionPermissions(): void
    {
        $permissions = config('AuthGroups')->matrix['admin'];
        $this->assertContains('users.delete', $permissions);
        $this->assertContains('customers.delete', $permissions);
    }

    public function testAuthorizedListRendersOneDeleteButtonPerDesktopAndMobileRowAndDialog(): void
    {
        $this->actor(true);
        $html = view('customers/index', $this->listData());
        $this->assertSame(2, substr_count($html, 'data-delete-customer '));
        $this->assertSame(1, substr_count($html, 'id="customer-delete-dialog"'));
        $this->assertStringContainsString('assets/js/customer-deletion.js', html_entity_decode($html));
        $this->assertStringContainsString('Tu contraseña de administrador', $html);
    }

    public function testUnauthorizedListDoesNotRenderDeletionControls(): void
    {
        $this->actor(false);
        $html = view('customers/index', $this->listData());
        $this->assertStringNotContainsString('data-delete-customer ', $html);
        $this->assertStringNotContainsString('id="customer-delete-dialog"', $html);
    }

    public function testUserDeletionHasConfirmationAndButtonInBothLayouts(): void
    {
        $this->actor(true);
        $html = view('settings/users/index', ['title' => 'Usuarios', 'users' => [[
            'id' => 100, 'username' => 'Usuario Prueba', 'email' => 'test@example.com',
            'role_label' => 'Operador', 'active' => true, 'created_at' => '2026-01-01 00:00:00',
        ]]]);
        $this->assertSame(2, substr_count($html, 'title="Eliminar usuario"'));
        $this->assertSame(2, substr_count($html, 'onsubmit="return confirm('));
        $this->assertSame(2, substr_count($html, 'name="_method" value="DELETE"'));
    }
}
