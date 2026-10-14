<?php

namespace App\Controllers;

use App\Services\LoanWorkflowService;
use Throwable;

class LoanController extends BaseController
{
    public function index()
    {
        return view('loans/index', array_merge([
            'title' => 'Prestamos', 'approvalQueue' => $this->repository->getApprovalQueue(),
        ], $this->loanListData()));
    }

    public function listing()
    {
        $data = $this->loanListData();
        return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
            'html' => view('loans/_listing', $data), 'pagination' => $data['pagination'],
        ]);
    }

    private function loanListData(): array
    {
        $filters = ['estado' => $this->request->getGet('estado') === 'cancelled' ? 'cancelled' : 'active'];
        foreach (['buscar', 'estado_detalle', 'moneda'] as $name) {
            $filters[$name] = trim((string) $this->request->getGet($name));
        }
        $page = max(1, (int) $this->request->getGet('page'));
        return array_merge($filters, ['statusFilter' => $filters['estado']], $this->repository->getLoanPage($filters, $page));
    }

    public function show($id)
    {
        $loan = $this->repository->getLoan($id);

        if ($loan === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Prestamo no encontrado');
        }

        return view('loans/show', [
            'title' => 'Prestamo ' . $loan['guid'],
            'loan' => $loan,
            'installments' => $this->repository->getLoanInstallments($id),
            'customerTotalDebt' => $this->repository->getCustomerTotalDebt($loan['customer_guid']),
        ]);
    }

    public function amortization($id)
    {
        $loan = $this->repository->getLoan($id);

        if ($loan === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Prestamo no encontrado');
        }

        $statusFilter = $this->request->getGet('estado') ?: 'all';
        $selectedLoanGuid = $this->request->getGet('prestamo') ?: $loan['guid'];
        $customerLoans = $this->repository->getCustomerLoans($loan['customer_guid']);
        $installments = $this->repository->getCustomerInstallments($loan['customer_guid'], $statusFilter, $selectedLoanGuid);

        $summaryInstallments = $this->repository->getCustomerInstallments($loan['customer_guid'], 'all', $selectedLoanGuid);

        return view('loans/amortization', [
            'title' => 'Amortizacion',
            'loan' => $loan,
            'installments' => $installments,
            'customerLoans' => $customerLoans,
            'selectedLoanGuid' => $selectedLoanGuid,
            'summary' => [
                'total' => count($summaryInstallments),
                'paid' => count(array_filter($summaryInstallments, static fn(array $item): bool => $item['status'] === 'paid')),
                'pending_partial' => count(array_filter($summaryInstallments, static fn(array $item): bool => in_array($item['status'], ['pending', 'partial'], true))),
                'overdue' => count(array_filter($summaryInstallments, static fn(array $item): bool => $item['status'] === 'overdue')),
            ],
            'statusFilter' => $statusFilter,
        ]);
    }

    public function statement($id)
    {
        $statement = $this->repository->getCustomerStatementByLoan($id);

        if ($statement === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Estado de cuenta no encontrado');
        }

        return view('loans/statement', [
            'title' => 'Estado de cuenta',
            'statement' => $statement,
        ]);
    }

    public function delete($id)
    {
        $adminPassword = (string) $this->request->getPost('admin_password');

        try {
            (new LoanWorkflowService())->deleteLoan($id, $adminPassword);

            return redirect()->to('/prestamos')->with('message', 'Prestamo y registros asociados eliminados. La solicitud vuelve a evaluacion.');
        } catch (Throwable $exception) {
            return redirect()->back()->withInput()->with('errors', [$exception->getMessage()]);
        }
    }
}
