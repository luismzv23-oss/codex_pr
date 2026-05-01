<?php

namespace App\Services;

use App\Models\InstallmentModel;
use App\Models\LoanApplicationModel;
use App\Models\LoanModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Services\AmortizationService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;

class PaymentService
{
    public function processPayment(array $payload): array
    {
        $db = Database::connect();
        $installmentModel = new InstallmentModel();
        $applicationModel = new LoanApplicationModel();
        $loanModel = new LoanModel();
        $paymentModel = new PaymentModel();

        $loan = $loanModel->find($payload['loan_guid']);
        $installment = $installmentModel->find($payload['installment_guid']);

        if ($loan === null || $installment === null) {
            throw new DatabaseException('Prestamo o cuota no encontrados.');
        }

        if ($installment->loan_guid !== $loan->guid) {
            throw new DatabaseException('La cuota no corresponde al prestamo seleccionado.');
        }

        $amount = round((float) $payload['amount'], 2);
        if ($amount <= 0) {
            throw new DatabaseException('El monto debe ser mayor a cero.');
        }

        $currentPaid = round((float) $installment->paid_amount, 2);
        $installmentTotal = round((float) $installment->total_amount + (float) $installment->late_fee, 2);
        $amountDue = max(0, round($installmentTotal - $currentPaid, 2));

        if ($amountDue <= 0) {
            throw new DatabaseException('La cuota seleccionada ya se encuentra pagada.');
        }

        $loanOutstanding = round((float) $loan->outstanding_balance, 2);
        if ($amount > $loanOutstanding + 0.009) {
            throw new DatabaseException('El monto no puede ser mayor al saldo total pendiente del prestamo.');
        }

        $db->transStart();

        $paymentModel->insert([
            'loan_guid' => $loan->guid,
            'installment_guid' => $installment->guid,
            'customer_guid' => $payload['customer_guid'],
            'amount' => $amount,
            'currency' => $payload['currency'],
            'payment_method' => $payload['payment_method'],
            'reference_number' => $payload['reference_number'] ?: null,
            'notes' => $payload['notes'] ?: null,
            'received_by' => auth()->id() ?: null,
        ]);

        $updatedInstallment = ['status' => 'paid'];
        $differenceDetected = abs($amount - $amountDue) > 0.009;

        if ($differenceDetected) {
            $loanInstallments = $installmentModel
                ->where('loan_guid', $loan->guid)
                ->orderBy('installment_number', 'ASC')
                ->findAll();

            $updatedInstallment = $this->recalculateRemainingInstallments(
                $loan,
                $installment,
                $loanInstallments,
                $installmentModel,
                $amount
            );
        } else {
            $newPaidAmount = round($currentPaid + $amount, 2);
            $installmentStatus = $this->resolveInstallmentStatus(
                $newPaidAmount,
                $installmentTotal,
                (string) $installment->due_date
            );

            $installmentModel->update($installment->guid, [
                'paid_amount' => $newPaidAmount,
                'status' => $installmentStatus,
                'paid_at' => $installmentStatus === 'paid' ? date('Y-m-d H:i:s') : null,
                'remaining_balance' => (float) $installment->remaining_balance,
            ]);

            $updatedInstallment = ['status' => $installmentStatus];

            if ($installmentStatus === 'paid') {
                $loanInstallments = $installmentModel
                    ->where('loan_guid', $loan->guid)
                    ->orderBy('installment_number', 'ASC')
                    ->findAll();

                $this->recalculateScheduledInstallments(
                    $loan,
                    (int) $installment->installment_number,
                    (float) $installment->remaining_balance,
                    $loanInstallments,
                    $installmentModel
                );
            }
        }

        $loanInstallments = $installmentModel
            ->where('loan_guid', $loan->guid)
            ->orderBy('installment_number', 'ASC')
            ->findAll();

        $outstandingBalance = 0.0;
        $nextDueDate = null;
        $hasOpenInstallments = false;

        foreach ($loanInstallments as $item) {
            $itemTotal = round((float) $item->total_amount + (float) $item->late_fee, 2);
            $itemPaid = round((float) $item->paid_amount, 2);
            $itemOutstanding = max(0, round($itemTotal - $itemPaid, 2));
            $outstandingBalance += $itemOutstanding;

            if ($itemOutstanding > 0) {
                $hasOpenInstallments = true;
                $nextDueDate ??= (string) $item->due_date;
            }
        }

        $loanModel->update($loan->guid, [
            'outstanding_balance' => round($outstandingBalance, 2),
            'next_due_date' => $nextDueDate,
            'status' => $hasOpenInstallments ? 'active' : 'paid',
            'closed_at' => $hasOpenInstallments ? null : date('Y-m-d H:i:s'),
        ]);

        if (! $hasOpenInstallments && ! empty($loan->application_guid)) {
            $applicationModel->update($loan->application_guid, [
                'status' => 'paid',
            ]);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('No se pudo procesar el pago.');
        }

        return [
            'loan_guid' => $loan->guid,
            'installment_guid' => $installment->guid,
            'amount_applied' => $amount,
            'outstanding_balance' => round($outstandingBalance, 2),
            'installment_status' => $updatedInstallment['status'],
        ];
    }

    protected function recalculateScheduledInstallments(object $loan, int $paidInstallmentNumber, float $remainingBalance, array $loanInstallments, InstallmentModel $installmentModel): void
    {
        $futureInstallments = array_values(array_filter(
            $loanInstallments,
            static fn(object $item): bool => (int) $item->installment_number > $paidInstallmentNumber && $item->status !== 'paid'
        ));

        if ($futureInstallments === []) {
            return;
        }

        $schedule = $this->buildScheduleProjection(
            $remainingBalance,
            (float) $loan->interest_rate,
            count($futureInstallments),
            (string) $loan->amortization_type
        );

        foreach ($futureInstallments as $index => $item) {
            $projection = $schedule[$index] ?? null;
            if ($projection === null) {
                continue;
            }

            $installmentModel->update($item->guid, [
                'principal_amount' => $projection['principal_amount'],
                'interest_amount' => $projection['interest_amount'],
                'total_amount' => $projection['total_amount'],
                'remaining_balance' => $projection['remaining_balance'],
                'status' => strtotime((string) $item->due_date) < strtotime('today') ? 'overdue' : 'pending',
                'paid_amount' => 0,
                'paid_at' => null,
            ]);
        }
    }

    protected function recalculateRemainingInstallments(object $loan, object $targetInstallment, array $loanInstallments, InstallmentModel $installmentModel, float $paymentAmount): array
    {
        $pendingInstallments = array_values(array_filter(
            $loanInstallments,
            static fn(object $item): bool => (int) $item->installment_number >= (int) $targetInstallment->installment_number && $item->status !== 'paid'
        ));

        if ($pendingInstallments === []) {
            return [
                'status' => 'paid',
            ];
        }

        $currentPaid = round((float) $targetInstallment->paid_amount, 2);
        $lateFee = round((float) $targetInstallment->late_fee, 2);
        $interestAmount = round((float) $targetInstallment->interest_amount, 2);
        $currentPrincipalPaid = $this->principalPaidFromAmount(
            $currentPaid,
            $lateFee,
            $interestAmount
        );

        $principalOutstandingBefore = max(
            0,
            round(((float) $targetInstallment->principal_amount - $currentPrincipalPaid), 2)
        );

        foreach ($pendingInstallments as $item) {
            if ($item->guid === $targetInstallment->guid) {
                continue;
            }

            $principalOutstandingBefore = round($principalOutstandingBefore + (float) $item->principal_amount, 2);
        }

        $newPaidAmount = round($currentPaid + $paymentAmount, 2);
        $newPrincipalPaid = $this->principalPaidFromAmount(
            $newPaidAmount,
            $lateFee,
            $interestAmount
        );
        $principalReduction = min(
            $principalOutstandingBefore,
            max(0, round($newPrincipalPaid, 2))
        );
        $principalOutstandingAfter = max(0, round($principalOutstandingBefore - $principalReduction, 2));

        $schedule = $this->buildScheduleProjection(
            $principalOutstandingAfter,
            (float) $loan->interest_rate,
            count($pendingInstallments),
            (string) $loan->amortization_type
        );

        $updatedCurrent = [
            'status' => 'paid',
        ];

        foreach ($pendingInstallments as $index => $item) {
            $projection = $schedule[$index] ?? null;
            if ($projection === null) {
                continue;
            }

            if ($item->guid === $targetInstallment->guid) {
                $recalculatedTotal = round($projection['total_amount'] + $lateFee, 2);
                $status = $this->resolveInstallmentStatus(
                    $newPaidAmount,
                    $recalculatedTotal,
                    (string) $item->due_date
                );

                $updatedCurrent = [
                    'status' => $status,
                ];

                $installmentModel->update($item->guid, [
                    'principal_amount' => $projection['principal_amount'],
                    'interest_amount' => $projection['interest_amount'],
                    'total_amount' => $projection['total_amount'],
                    'remaining_balance' => $projection['remaining_balance'],
                    'status' => $status,
                    'paid_amount' => $newPaidAmount,
                    'paid_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null,
                ]);

                continue;
            }

            $installmentModel->update($item->guid, [
                'principal_amount' => $projection['principal_amount'],
                'interest_amount' => $projection['interest_amount'],
                'total_amount' => $projection['total_amount'],
                'remaining_balance' => $projection['remaining_balance'],
                'status' => strtotime((string) $item->due_date) < strtotime('today') ? 'overdue' : 'pending',
                'paid_amount' => 0,
                'paid_at' => null,
            ]);
        }

        return $updatedCurrent;
    }

    protected function resolveInstallmentStatus(float $paidAmount, float $installmentTotal, string $dueDate): string
    {
        if ($paidAmount >= $installmentTotal) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return strtotime($dueDate) < strtotime('today') ? 'overdue' : 'partial';
        }

        return strtotime($dueDate) < strtotime('today') ? 'overdue' : 'pending';
    }

    protected function principalPaidFromAmount(float $paidAmount, float $lateFee, float $interestAmount): float
    {
        return max(0, round($paidAmount - $lateFee - $interestAmount, 2));
    }

    protected function buildScheduleProjection(float $amount, float $rate, int $terms, string $amortizationType): array
    {
        if ($terms <= 0) {
            return [];
        }

        $service = new AmortizationService();

        return match ($amortizationType) {
            'german' => $service->calculateGerman($amount, $rate, $terms),
            'american' => $service->calculateAmerican($amount, $rate, $terms),
            default => $service->calculateFrench($amount, $rate, $terms),
        };
    }
}
