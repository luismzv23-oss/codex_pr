<?php

namespace App\Controllers;

use App\Services\PdfDocumentService;
use CodeIgniter\Exceptions\PageNotFoundException;

class LoanDocumentController extends BaseController
{
    public function loan($id)
    {
        $loan = $this->repository->getLoan((string) $id);
        if ($loan === null) {
            throw PageNotFoundException::forPageNotFound('Prestamo no encontrado');
        }

        $customer = $this->repository->getCustomer($loan['customer_guid']);
        $installments = $this->repository->getLoanInstallments($loan['guid']);
        $payments = $this->repository->getLoanPayments($loan['guid']);

        return $this->downloadPdf(
            'pdf/loan',
            [
                'title' => 'Resumen del prestamo',
                'loan' => $loan,
                'customer' => $customer,
                'installments' => $installments,
                'payments' => $payments,
                'collectionMethods' => $this->repository->getCollectionMethods(true),
            ],
            'prestamo-' . $loan['alias'] . '.pdf'
        );
    }

    public function amortization($id)
    {
        $loan = $this->repository->getLoan((string) $id);
        if ($loan === null) {
            throw PageNotFoundException::forPageNotFound('Prestamo no encontrado');
        }

        return $this->downloadPdf(
            'pdf/amortization',
            [
                'title' => 'Cronograma de pago',
                'loan' => $loan,
                'customer' => $this->repository->getCustomer($loan['customer_guid']),
                'installments' => $this->repository->getLoanInstallments($loan['guid']),
            ],
            'cronograma-' . $loan['alias'] . '.pdf'
        );
    }

    public function statement($id)
    {
        $statement = $this->repository->getCustomerStatementByLoan((string) $id);
        if ($statement === null) {
            throw PageNotFoundException::forPageNotFound('Estado de cuenta no encontrado');
        }

        return $this->downloadPdf(
            'pdf/statement',
            [
                'title' => 'Estado de cuenta',
                'statement' => $statement,
            ],
            'estado-cuenta-' . ($statement['loan']['alias'] ?? $statement['loan']['guid']) . '.pdf'
        );
    }

    public function installment($loanId, $installmentId)
    {
        $loan = $this->repository->getLoan((string) $loanId);
        $installment = $this->repository->getInstallmentForLoan((string) $loanId, (string) $installmentId);

        if ($loan === null || $installment === null) {
            throw PageNotFoundException::forPageNotFound('Cuota no encontrada');
        }

        return $this->downloadPdf(
            'pdf/installment',
            [
                'title' => 'Cuota del prestamo',
                'loan' => $loan,
                'customer' => $this->repository->getCustomer($loan['customer_guid']),
                'installment' => $installment,
                'collectionMethods' => $this->repository->getCollectionMethods(true),
                'barcodeValue' => ($loan['alias'] ?? $loan['guid']) . '-C' . str_pad((string) $installment['installment_number'], 3, '0', STR_PAD_LEFT),
            ],
            'cuota-' . ($loan['alias'] ?? $loan['guid']) . '-' . $installment['installment_number'] . '.pdf'
        );
    }

    public function contract($id)
    {
        $loan = $this->repository->getLoan((string) $id);
        if ($loan === null) {
            throw PageNotFoundException::forPageNotFound('Prestamo no encontrado');
        }

        $application = ! empty($loan['application_guid']) ? $this->repository->getApplication((string) $loan['application_guid']) : null;
        $service = new PdfDocumentService();
        $contractPath = $service->contractPath($loan['guid']);

        if (is_file($contractPath)) {
            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="contrato-' . ($loan['alias'] ?? $loan['guid']) . '.pdf"')
                ->setBody((string) file_get_contents($contractPath));
        }

        return $this->downloadPdf(
            'pdf/contract',
            [
                'title' => 'Contrato de prestamo',
                'loan' => $loan,
                'application' => $application,
                'customer' => $this->repository->getCustomer($loan['customer_guid']),
                'installments' => $this->repository->getLoanInstallments($loan['guid']),
                'collectionMethods' => $this->repository->getCollectionMethods(true),
            ],
            'contrato-' . ($loan['alias'] ?? $loan['guid']) . '.pdf'
        );
    }

    public function clearance($id)
    {
        $loan = $this->repository->getLoan((string) $id);
        if ($loan === null) {
            throw PageNotFoundException::forPageNotFound('Prestamo no encontrado');
        }

        if (! in_array(($loan['status'] ?? ''), ['paid', 'paid_off'], true) && round((float) ($loan['outstanding_balance'] ?? 0), 2) > 0) {
            return redirect()->back()->with('errors', ['El PDF de libre deuda solo puede generarse cuando el credito esta totalmente pagado.']);
        }

        return $this->downloadPdf(
            'pdf/clearance',
            [
                'title' => 'Certificado de libre deuda',
                'loan' => $loan,
                'customer' => $this->repository->getCustomer($loan['customer_guid']),
                'payments' => $this->repository->getLoanPayments($loan['guid']),
            ],
            'libre-deuda-' . ($loan['alias'] ?? $loan['guid']) . '.pdf'
        );
    }

    private function downloadPdf(string $view, array $data, string $filename)
    {
        $pdf = (new PdfDocumentService())->render($view, $data);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($pdf);
    }
}
