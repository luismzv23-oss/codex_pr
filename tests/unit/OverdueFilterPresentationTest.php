<?php

use CodeIgniter\Test\CIUnitTestCase;

final class OverdueFilterPresentationTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('app');
    }

    private function customers(): array
    {
        return [['guid' => 'c1', 'full_name' => 'Luis Zorrilla', 'dni' => '30111222'], ['guid' => 'c2', 'full_name' => 'Ana Bel', 'dni' => '']];
    }

    public function testCustomerSelectDefaultsAreUnchanged(): void
    {
        $html = view('components/customer_select', ['customers' => $this->customers(), 'lockedCustomerGuid' => '']);

        $this->assertStringContainsString('name="customer_guid"', $html);
        $this->assertStringContainsString(' required', $html);
        $this->assertStringContainsString('>Seleccionar cliente<', $html);
    }

    public function testCustomerSelectAsOptionalFilter(): void
    {
        $html = view('components/customer_select', [
            'customers' => $this->customers(), 'lockedCustomerGuid' => 'c1', 'customerSelectId' => 'overdue-customer',
            'customerSelectName' => 'cliente', 'customerSelectRequired' => false, 'customerSelectPlaceholder' => 'Todos los clientes',
        ]);

        $this->assertStringContainsString('name="cliente"', $html);
        $this->assertStringNotContainsString(' required', $html);
        $this->assertStringContainsString('>Todos los clientes<', $html);
        $this->assertMatchesRegularExpression('/value="c1"[^>]*selected/', $html);
        $this->assertStringContainsString('Ana Bel — Sin documento', $html);
    }

    public function testPagerKeepsCustomerFilterOnPageLinks(): void
    {
        $pagination = ['page' => 2, 'per_page' => 10, 'total' => 25, 'pages' => 3, 'from' => 11, 'to' => 20];
        $html = html_entity_decode(view('components/pager', ['pagination' => $pagination, 'baseUrl' => '/reportes/mora', 'query' => ['cliente' => 'c1'], 'noun' => 'clientes en mora', 'label' => 'x']));

        $this->assertStringContainsString('href="/reportes/mora?cliente=c1&page=1"', $html);
        $this->assertStringContainsString('href="/reportes/mora?cliente=c1&page=3"', $html);
    }

    public function testPagerOmitsEmptyFilters(): void
    {
        $pagination = ['page' => 1, 'per_page' => 10, 'total' => 25, 'pages' => 3, 'from' => 1, 'to' => 10];
        $html = html_entity_decode(view('components/pager', ['pagination' => $pagination, 'baseUrl' => '/reportes/mora', 'query' => ['cliente' => ''], 'noun' => 'clientes en mora', 'label' => 'x']));

        $this->assertStringContainsString('href="/reportes/mora?page=2"', $html);
    }
}
