<?php

namespace App\Controllers;

use App\Services\LoanWorkflowService;
use Throwable;

class LoanController extends BaseController
{
    public function index()
    {
        $statusFilter = $this->request->getGet('estado') ?: 'active';

        return view('loans/index', [
            'title' => 'Prestamos',
            'loans' => $this->repository->getLoansByStatus($statusFilter),
            'approvalQueue' => $this->repository->getApprovalQueue(),
            'statusFilter' => $statusFilter,
        ]);
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

        return view('loans/amortization', [
            'title' => 'Amortizacion',
            'loan' => $loan,
            'installments' => $this->repository->getCustomerInstallments($loan['customer_guid'], $statusFilter),
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
