<?php

use CodeIgniter\Shield\Auth;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class PaymentGroupingPresentationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['app', 'auth']);
        $user = $this->createMock(User::class);
        $user->username = 'test-admin';
        $user->method('can')->willReturn(true);
        $auth = $this->createMock(Auth::class);
        $auth->method('setAuthenticator')->willReturnSelf();
        $auth->method('user')->willReturn($user);
        Services::injectMock('auth', $auth);
    }

    private function data(): array
    {
        $groups = [];
        for ($i = 1; $i <= 6; $i++) $groups[] = [
            'guid' => 'c' . $i, 'name' => '<script>Cliente</script>', 'loan_count' => 1, 'payment_count' => 1,
            'loans' => [[
                'guid' => 'loan' . $i, 'label' => 'PS-00' . $i . '-CP',
                'payments' => [[
                    'guid' => 'pay' . $i, 'amount' => 1000, 'currency' => 'ARS', 'payment_method' => 'transfer',
                    'reference_number' => '<script>Referencia</script>', 'created_at' => '2026-01-01 10:30:00',
                ]],
            ]],
        ];
        return ['title' => 'Pagos', 'buscar' => '', 'metodo' => '', 'moneda' => '', 'groups' => $groups,
            'pagination' => ['page' => 1, 'pages' => 2, 'total' => 8, 'from' => 1, 'to' => 6]];
    }

    public function testTwoCollapsibleLevelsStartClosedAndRenderPaymentDetails(): void
    {
        $html = view('payments/index', $this->data());
        $this->assertSame(6, substr_count($html, 'data-customer-group'));
        $this->assertSame(6, substr_count($html, 'data-loan-group'));
        $this->assertSame(6, substr_count($html, 'data-payment-row'));
        $this->assertSame(0, preg_match('/<details[^>]*\sopen(?:\s|>)/', $html));
        $this->assertStringContainsString('PS-001-CP', $html);
        $this->assertStringContainsString('Transferencia', $html);
        $this->assertStringContainsString('01/01/2026 10:30', $html);
        $this->assertStringContainsString('Mostrando 1–6 de 8 clientes', $html);
        $this->assertStringContainsString('assets/js/payments.js', html_entity_decode($html));
    }

    public function testValuesAreEscapedAndGroupKeysUseIdentifiers(): void
    {
        $html = view('payments/_listing', $this->data());
        $this->assertStringNotContainsString('<script>Cliente</script>', $html);
        $this->assertStringNotContainsString('<script>Referencia</script>', $html);
        $this->assertStringContainsString('customer:c1', html_entity_decode($html));
        $this->assertStringContainsString('loan:c1:loan1', html_entity_decode($html));
    }

    public function testEmptyResultsShowMessageAndDisabledNavigation(): void
    {
        $data = $this->data();
        $data['groups'] = [];
        $data['pagination'] = ['page' => 1, 'pages' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
        $html = view('payments/_listing', $data);
        $this->assertStringContainsString('No se encontraron registros de pago', $html);
        $this->assertStringNotContainsString('data-payment-row', $html);
        $this->assertSame(2, substr_count($html, 'aria-disabled="true"'));
    }
}
