<?php

use CodeIgniter\Test\CIUnitTestCase;

final class PendingInstallmentsPaginationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('app');
    }

    private function render(array $filters, array $pagination): string
    {
        $item = [
            'guid' => 'ins1', 'loan_guid' => 'loan1', 'loan_label' => 'PS-001-AB', 'customer_name' => 'Ana Bel',
            'installment_number' => 2, 'generation_date' => '2026-11-01', 'due_date' => '2026-11-05',
            'total_amount' => 100, 'paid_amount' => 0, 'amount_due' => 100, 'currency' => 'USD', 'status' => 'pending',
        ];

        return html_entity_decode(view('pending_installments/_listing', ['installments' => [$item], 'pagination' => $pagination] + $filters));
    }

    public function testPagerAndReturnUrlKeepFiltersAndPage(): void
    {
        $html = $this->render(
            ['buscar' => 'ana', 'estado' => 'pending', 'periodo' => 'all'],
            ['page' => 2, 'per_page' => 10, 'total' => 25, 'pages' => 3, 'from' => 11, 'to' => 20]
        );

        $this->assertStringContainsString('Mostrando 11–20 de 25 cuotas', $html);
        $this->assertStringContainsString('data-page="1" href="/cuotas-pendientes?buscar=ana&estado=pending&periodo=all&page=1"', $html);
        $this->assertStringContainsString('data-page="3" href="/cuotas-pendientes?buscar=ana&estado=pending&periodo=all&page=3"', $html);
        $this->assertStringContainsString('/pagos/crear/ins1?return=' . rawurlencode('/cuotas-pendientes?buscar=ana&estado=pending&periodo=all&page=2'), $html);
        $this->assertStringContainsString('USD 100,00', $html);
    }

    public function testPageSizeIsEight(): void
    {
        $this->assertSame(8, \App\Controllers\PendingInstallmentController::PER_PAGE);
    }

    public function testDefaultPeriodIsOmittedFromLinks(): void
    {
        $html = $this->render(
            ['buscar' => '', 'estado' => '', 'periodo' => 'current_month'],
            ['page' => 1, 'per_page' => 10, 'total' => 12, 'pages' => 2, 'from' => 1, 'to' => 10]
        );

        $this->assertStringContainsString('href="/cuotas-pendientes?page=2"', $html);
        $this->assertStringContainsString('?return=' . rawurlencode('/cuotas-pendientes?page=1'), $html);
    }
}
