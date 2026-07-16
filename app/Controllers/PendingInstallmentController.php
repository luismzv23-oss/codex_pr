<?php

namespace App\Controllers;

class PendingInstallmentController extends BaseController
{
    public function index()
    {
        $buscar = trim((string) $this->request->getGet('buscar'));
        $estado = trim((string) $this->request->getGet('estado'));
        $periodo = $this->request->getGet('periodo') ?: 'current_month';

        $loans = [];
        foreach ($this->repository->getLoans() as $loan) {
            $loans[$loan['guid']] = $loan;
        }

        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $today = date('Y-m-d');

        $installments = $this->repository->getInstallments();

        // Base filter for pending / partial / overdue installments with due amounts
        $installments = array_filter($installments, static function (array $item) use ($periodo, $monthStart, $monthEnd, $today): bool {
            if ((float) ($item['amount_due'] ?? 0) <= 0) {
                return false;
            }

            if (! in_array($item['status'], ['pending', 'partial', 'overdue'], true)) {
                return false;
            }

            if ($periodo === 'current_month') {
                return (string) ($item['due_date'] ?? '') >= $monthStart
                    && (string) ($item['due_date'] ?? '') <= $monthEnd;
            }

            if ($periodo === 'overdue') {
                return (string) ($item['due_date'] ?? '') < $today;
            }

            return true; // 'all' period
        });

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

        if ($buscar !== '') {
            $installments = array_filter($installments, static function (array $item) use ($buscar): bool {
                return stripos($item['customer_name'] ?? '', $buscar) !== false
                    || stripos($item['loan_label'] ?? '', $buscar) !== false;
            });
        }

        if ($estado !== '') {
            $installments = array_filter($installments, static function (array $item) use ($estado): bool {
                return ($item['status'] ?? '') === $estado;
            });
        }

        usort($installments, static fn(array $a, array $b): int => strcmp($a['due_date'], $b['due_date']));

        return view('pending_installments/index', [
            'title' => 'Cuotas pendientes',
            'installments' => array_values($installments),
            'summary' => [
                'total' => count($installments),
                'overdue' => count(array_filter($installments, static fn(array $item): bool => $item['status'] === 'overdue')),
                'amount_due' => round(array_sum(array_map(static fn(array $item): float => (float) ($item['amount_due'] ?? 0), $installments)), 2),
            ],
            'buscar' => $buscar,
            'estado' => $estado,
            'periodo' => $periodo,
        ]);
    }
}
