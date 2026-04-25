<?php

namespace App\Controllers;

use App\Services\PaymentService;
use Throwable;

class PaymentController extends BaseController
{
    public function index()
    {
        return view('payments/index', [
            'title' => 'Pagos',
            'payments' => $this->repository->getPayments(),
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
            'return_url' => $this->request->getGet('return') ?: '/pagos',
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
            'amount' => 'required|decimal',
            'currency' => 'required|exact_length[3]',
            'payment_method' => 'required|in_list[cash,transfer,card,check]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $redirectTarget = $payload['return_url'] ?: '/pagos';
        unset($payload['return_url']);

        try {
            (new PaymentService())->processPayment($payload);

            return redirect()->to($redirectTarget)->with('message', 'Pago procesado y deuda actualizada.');
        } catch (Throwable $exception) {
            return redirect()->back()->withInput()->with('errors', [$exception->getMessage()]);
        }
    }
}
