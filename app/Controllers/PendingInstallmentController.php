<?php

namespace App\Controllers;

class PendingInstallmentController extends BaseController
{
    public function index()
    {
        $loans = [];
        foreach ($this->repository->getLoans() as $loan) {
            $loans[$loan['guid']] = $loan;
        }

        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $installments = array_values(array_filter(
            $this->repository->getInstallments(),
            static fn(array $item): bool => in_array($item['status'], ['pending', 'partial'], true)
                && (float) ($item['amount_due'] ?? 0) > 0
                && (string) ($item['due_date'] ?? '') >= $monthStart
                && (string) ($item['due_date'] ?? '') <= $monthEnd
        ));

        usort($installments, static fn(array $a, array $b): int => strcmp($a['due_date'], $b['due_date']));

        $nextInstallments = [];
        foreach ($installments as $installment) {
            $loanGuid = (string) ($installment['loan_guid'] ?? '');
            if ($loanGuid !== '' && ! isset($nextInstallments[$loanGuid])) {
                $nextInstallments[$loanGuid] = $installment;
            }
        }

        $installments = array_values($nextInstallments);

        foreach ($installments as &$installment) {
            $loan = $loans[$installment['loan_guid']] ?? null;
            $installment['currency'] = $loan['currency'] ?? 'ARS';
            $installment['loan_label'] = $loan['alias'] ?? ($loan['guid'] ?? $installment['loan_guid']);
            $installment['customer_name'] = $loan['customer_name'] ?? 'Cliente no disponible';
        }
        unset($installment);

        usort($installments, static fn(array $a, array $b): int => strcmp($a['due_date'], $b['due_date']));

        return view('pending_installments/index', [
            'title' => 'Cuotas pendientes',
            'installments' => $installments,
            'summary' => [
                'total' => count($installments),
                'overdue' => 0,
                'amount_due' => round(array_sum(array_map(static fn(array $item): float => (float) ($item['amount_due'] ?? 0), $installments)), 2),
            ],
        ]);
    }
}
