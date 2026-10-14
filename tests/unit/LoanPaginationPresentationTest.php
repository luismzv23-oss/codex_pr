<?php

use CodeIgniter\Shield\Auth;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class LoanPaginationPresentationTest extends CIUnitTestCase
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
        $loans = [];
        for ($i = 1; $i <= 6; $i++) $loans[] = [
            'guid' => 'loan' . $i, 'customer_name' => '<script>Cliente Prueba</script>',
            'principal_amount' => 1000, 'outstanding_balance' => 1500, 'currency' => 'ARS',
            'next_due_date' => null, 'status' => 'active',
        ];
        return ['title' => 'Prestamos', 'loans' => $loans, 'approvalQueue' => [],
            'statusFilter' => 'active', 'buscar' => '', 'estado_detalle' => '', 'moneda' => '',
            'pagination' => ['page' => 1, 'pages' => 3, 'total' => 14, 'from' => 1, 'to' => 6],
        ];
    }

    public function testPageRendersSixLoansWithoutRemovingApprovalQueue(): void
    {
        $data = $this->data();
        $data['approvalQueue'] = [[
            'guid' => 'pending', 'customer_name' => 'Solicitud de prueba', 'requested_amount' => 2000,
            'currency' => 'ARS', 'amortization_type' => 'french', 'term_months' => 3,
            'interest_rate' => 0.25, 'status' => 'evaluation',
        ]];
        $html = view('loans/index', $data);
        $this->assertSame(6, substr_count($html, 'data-loan-card'));
        $this->assertStringContainsString('/solicitudes/pending', $html);
        $this->assertStringContainsString('1 pendientes', $html);
        $this->assertStringContainsString('Página 1 de 3', $html);
        $this->assertStringContainsString('assets/js/loans.js', html_entity_decode($html));
        $this->assertStringNotContainsString('<script>Cliente Prueba</script>', $html);
    }

    public function testListFragmentDoesNotIncludeApprovalSectionOrTemplate(): void
    {
        $html = view('loans/_listing', $this->data());
        $this->assertSame(6, substr_count($html, 'data-loan-card'));
        $this->assertStringNotContainsString('Solicitudes pendientes', $html);
        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringContainsString('Mostrando 1–6 de 14 préstamos', $html);
    }

    public function testEmptyPageRendersMessageAndDisabledNavigation(): void
    {
        $data = $this->data();
        $data['loans'] = [];
        $data['pagination'] = ['page' => 1, 'pages' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
        $html = view('loans/_listing', $data);
        $this->assertStringContainsString('No hay prestamos para el filtro seleccionado.', $html);
        $this->assertSame(2, substr_count($html, 'aria-disabled="true"'));
        $this->assertStringNotContainsString('data-loan-card', $html);
    }
}
