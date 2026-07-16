<?php

namespace App\Controllers;

use App\Services\PaymentService;
use Throwable;

class PaymentController extends BaseController
{
    public function index()
    {
        $buscar = trim((string) $this->request->getGet('buscar'));
        $metodo = trim((string) $this->request->getGet('metodo'));
        $moneda = trim((string) $this->request->getGet('moneda'));

        $payments = $this->repository->getPayments();

        if ($buscar !== '') {
            $payments = array_filter($payments, static function (array $payment) use ($buscar): bool {
                return stripos($payment['customer_name'] ?? '', $buscar) !== false
                    || stripos($payment['reference_number'] ?? '', $buscar) !== false
                    || stripos($payment['loan_guid'] ?? '', $buscar) !== false;
            });
        }

        if ($metodo !== '') {
            $payments = array_filter($payments, static function (array $payment) use ($metodo): bool {
                return ($payment['payment_method'] ?? '') === $metodo;
            });
        }

        if ($moneda !== '') {
            $payments = array_filter($payments, static function (array $payment) use ($moneda): bool {
                return ($payment['currency'] ?? '') === $moneda;
            });
        }

        return view('payments/index', [
            'title' => 'Pagos',
            'payments' => array_values($payments),
            'buscar' => $buscar,
            'metodo' => $metodo,
            'moneda' => $moneda,
        ]);
    }

    public function create($installment_guid = null)
    {
        $installment = $installment_guid ? $this->repository->getInstallment($installment_guid) : null;
        $loan = $installment ? $this->repository->getLoan($installment['loan_guid']) : null;
        $customer = $loan ? $this->repository->getCustomer($loan['customer_guid']) : null;

        return view('payments/create', [
            'title' => 'Registrar pago',
            'installment_guid' => $installment_guid,
            'installment' => $installment,
            'loan' => $loan,
            'customer' => $customer,
            'return_url' => $this->safeReturnUrl($this->request->getGet('return')),
            'loans' => $this->repository->getLoans(),
            'customers' => $this->repository->getCustomers(),
        ]);
    }

    public function store()
    {
        $payload = $this->request->getPost([
            'loan_guid',
            'installment_guid',
            'customer_guid',
            'amount',
            'currency',
            'payment_method',
            'reference_number',
            'notes',
            'return_url',
        ]);

        $rules = [
            'loan_guid' => 'required',
            'installment_guid' => 'required',
            'customer_guid' => 'required',
            'amount' => 'required|decimal|greater_than[0]',
            'currency' => 'required|exact_length[3]',
            'payment_method' => 'required|in_list[cash,transfer,card,check]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $redirectTarget = $this->safeReturnUrl($payload['return_url'] ?? null);
        unset($payload['return_url']);

        try {
            (new PaymentService())->processPayment($payload);

            return redirect()->to($redirectTarget)->with('message', 'Pago procesado y deuda actualizada.');
        } catch (Throwable $exception) {
            return redirect()->back()->withInput()->with('errors', [$exception->getMessage()]);
        }
    }

    private function safeReturnUrl($value): string
    {
        $value = trim((string) $value);

        if ($value === '' || $value[0] !== '/' || str_starts_with($value, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $value)) {
            return '/pagos';
        }

        return $value;
    }
}
