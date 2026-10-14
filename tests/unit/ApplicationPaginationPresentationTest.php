<?php

use CodeIgniter\Shield\Auth;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class ApplicationPaginationPresentationTest extends CIUnitTestCase
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
        $applications = [];
        for ($i = 1; $i <= 6; $i++) {
            $applications[] = [
                'guid' => 'app' . $i, 'customer_name' => '<script>Prueba</script>', 'requested_amount' => 1000,
                'currency' => 'ARS', 'amortization_type' => 'french', 'term_months' => 3,
                'interest_rate' => 0.25, 'status' => 'approved', 'loan_count' => $i === 1 ? 0 : 2,
            ];
        }
        return ['title' => 'Solicitudes', 'buscar' => '', 'estado' => '', 'moneda' => '', 'sistema' => '',
            'applications' => $applications, 'summary' => ['draft' => 2, 'evaluation' => 3, 'approved' => 18],
            'pagination' => ['page' => 1, 'pages' => 4, 'total' => 23, 'from' => 1, 'to' => 6],
        ];
    }

    public function testPageRendersCardsCountsAndScriptWithEscapedNames(): void
    {
        $html = view('loan_applications/index', $this->data());
        $this->assertSame(6, substr_count($html, 'data-application-card'));
        $this->assertSame(6, substr_count($html, 'Total de préstamos del cliente:'));
        $this->assertStringContainsString('<strong>0</strong>', $html);
        $this->assertStringContainsString('assets/js/applications.js', html_entity_decode($html));
        $this->assertStringNotContainsString('<script>Prueba</script>', $html);
        $this->assertStringContainsString('Página 1 de 4', $html);
    }

    public function testSummaryRendersAllFilteredResultsRatherThanOnlyVisibleCards(): void
    {
        $html = view('loan_applications/_summary', $this->data());
        $this->assertMatchesRegularExpression('/>\s*18\s*<\/p>/', $html);
        $this->assertMatchesRegularExpression('/>\s*2\s*<\/p>/', $html);
        $this->assertMatchesRegularExpression('/>\s*3\s*<\/p>/', $html);
    }

    public function testEmptyResultsRenderMessageAndDisabledNavigation(): void
    {
        $data = $this->data();
        $data['applications'] = [];
        $data['pagination'] = ['page' => 1, 'pages' => 1, 'total' => 0, 'from' => 0, 'to' => 0];
        $html = view('loan_applications/_listing', $data);
        $this->assertStringContainsString('No se encontraron solicitudes', $html);
        $this->assertStringNotContainsString('data-application-card', $html);
        $this->assertSame(2, substr_count($html, 'aria-disabled="true"'));
    }
}
