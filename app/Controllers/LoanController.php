<?php

namespace App\Controllers;

use App\Services\LoanWorkflowService;
use Throwable;

class LoanController extends BaseController
{
    public function index()
    {
        $statusFilter = $this->request->getGet('estado') ?: 'active';
        $buscar = trim((string) $this->request->getGet('buscar'));
        $estado_detalle = trim((string) $this->request->getGet('estado_detalle'));
        $moneda = trim((string) $this->request->getGet('moneda'));

        $loans = $this->repository->getLoansByStatus($statusFilter);

        if ($buscar !== '') {
            $loans = array_filter($loans, static function (array $loan) use ($buscar): bool {
                return stripos($loan['customer_name'] ?? '', $buscar) !== false
                    || stripos($loan['guid'] ?? '', $buscar) !== false
                    || stripos($loan['alias'] ?? '', $buscar) !== false;
            });
        }

        if ($estado_detalle !== '') {
            $loans = array_filter($loans, static function (array $loan) use ($estado_detalle): bool {
                return ($loan['status'] ?? '') === $estado_detalle;
            });
        }

        if ($moneda !== '') {
            $loans = array_filter($loans, static function (array $loan) use ($moneda): bool {
                return ($loan['currency'] ?? '') === $moneda;
            });
        }

        return view('loans/index', [
            'title' => 'Prestamos',
            'loans' => array_values($loans),
            'approvalQueue' => $this->repository->getApprovalQueue(),
            'statusFilter' => $statusFilter,
            'buscar' => $buscar,
            'estado_detalle' => $estado_detalle,
            'moneda' => $moneda,
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
