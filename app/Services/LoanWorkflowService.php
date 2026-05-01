<?php

namespace App\Services;

use App\Models\AuditLogModel;
use App\Models\CollectionMethodModel;
use App\Models\CustomerModel;
use App\Models\InstallmentModel;
use App\Models\LoanApplicationModel;
use App\Models\LoanModel;
use App\Models\NotificationModel;
use App\Models\PaymentModel;
use App\Support\AppRepository;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;

class LoanWorkflowService
{
    public function submitForEvaluation($application): void
    {
        // TODO: Validate application, change status
    }

    public function approve(string $applicationGuid, float $approvedAmount): array
    {
        $db = Database::connect();
        $applicationModel = new LoanApplicationModel();
        $loanModel = new LoanModel();
        $installmentModel = new InstallmentModel();
        $auditModel = new AuditLogModel();

        $application = $applicationModel->find($applicationGuid);
        if ($application === null) {
            throw new DatabaseException('La solicitud no existe.');
        }

        if (in_array($application->status, ['rejected'], true)) {
            throw new DatabaseException('La solicitud rechazada no puede aprobarse.');
        }

        $existingLoan = $loanModel->where('application_guid', $applicationGuid)->first();
        if ($existingLoan !== null) {
            return [
                'application_guid' => $applicationGuid,
                'loan_guid' => $existingLoan->guid,
                'created' => false,
            ];
        }

        $approvedAmount = round($approvedAmount, 2);
        if ($approvedAmount <= 0) {
            throw new DatabaseException('El monto aprobado debe ser mayor a cero.');
        }

        $schedule = (new AmortizationService())->generateSchedule([
            'principal_amount' => $approvedAmount,
            'interest_rate' => (float) $application->interest_rate,
            'term_months' => (int) $application->term_months,
            'amortization_type' => (string) $application->amortization_type,
            'disbursed_at' => date('Y-m-d H:i:s'),
        ]);

        if ($schedule === []) {
            throw new DatabaseException('No se pudo generar el cronograma de cuotas.');
        }

        $totalPayable = round(array_sum(array_column($schedule, 'total_amount')), 2);
        $totalInterest = round(array_sum(array_column($schedule, 'interest_amount')), 2);

        $db->transStart();

        $applicationModel->update($applicationGuid, [
            'status' => 'approved',
            'approved_amount' => $approvedAmount,
            'approved_by' => auth()->id() ?: null,
            'evaluated_by' => auth()->id() ?: null,
            'disbursed_at' => date('Y-m-d H:i:s'),
        ]);

        $loanModel->insert([
            'application_guid' => $applicationGuid,
            'customer_guid' => $application->customer_guid,
            'currency' => $application->currency,
            'principal_amount' => $approvedAmount,
            'interest_rate' => $application->interest_rate,
            'term_months' => $application->term_months,
            'amortization_type' => $application->amortization_type,
            'total_interest' => $totalInterest,
            'total_payable' => $totalPayable,
            'outstanding_balance' => $totalPayable,
            'status' => 'active',
            'next_due_date' => $schedule[0]['due_date'],
            'disbursed_at' => date('Y-m-d H:i:s'),
            'closed_at' => null,
        ]);

        $loan = $loanModel->where('application_guid', $applicationGuid)->first();
        if ($loan === null) {
            throw new DatabaseException('No se pudo crear el prestamo aprobado.');
        }

        foreach ($schedule as $item) {
            $installmentModel->insert([
                'loan_guid' => $loan->guid,
                'installment_number' => $item['installment_number'],
                'due_date' => $item['due_date'],
                'principal_amount' => $item['principal_amount'],
                'interest_amount' => $item['interest_amount'],
                'total_amount' => $item['total_amount'],
                'paid_amount' => 0,
                'remaining_balance' => $item['remaining_balance'],
                'status' => strtotime($item['due_date']) < strtotime('today') ? 'overdue' : 'pending',
                'paid_at' => null,
                'late_fee' => 0,
            ]);
        }

        $auditModel->insert([
            'user_guid' => auth()->id() ?: null,
            'action' => 'loan.approved',
            'entity_type' => 'loan_application',
            'entity_guid' => $applicationGuid,
            'old_values' => json_encode(['status' => $application->status, 'approved_amount' => $application->approved_amount]),
            'new_values' => json_encode(['status' => 'approved', 'approved_amount' => $approvedAmount, 'loan_guid' => $loan->guid]),
            'ip_address' => service('request')->getIPAddress(),
            'user_agent' => substr((string) service('request')->getUserAgent(), 0, 255),
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('No se pudo aprobar la solicitud.');
        }

        try {
            $repository = new AppRepository();
            $customer = (new CustomerModel())->asArray()->find($application->customer_guid);
            $applicationData = method_exists($application, 'toRawArray') ? $application->toRawArray() : (array) $application;
            $loanData = $repository->getLoan($loan->guid) ?? (method_exists($loan, 'toRawArray') ? $loan->toRawArray() : (array) $loan);

            $collections = [];
            try {
                $collections = array_values(array_filter(
                    (new CollectionMethodModel())->asArray()->findAll(),
                    static fn(array $item): bool => ($item['status'] ?? 'active') === 'active'
                ));
            } catch (\Throwable) {
                $collections = [];
            }

            $pdf = (new PdfDocumentService())->render('pdf/contract', [
                'title' => 'Contrato de prestamo',
                'loan' => $loanData,
                'application' => $applicationData,
                'customer' => $customer,
                'installments' => $schedule,
                'collectionMethods' => $collections,
            ]);

            (new PdfDocumentService())->saveContract($loan->guid, $pdf);
        } catch (\Throwable) {
            // No interrumpe la aprobacion si el PDF del contrato falla.
        }

        return [
            'application_guid' => $applicationGuid,
            'loan_guid' => $loan->guid,
            'created' => true,
        ];
    }

    public function reject($application, string $reason, string $evaluatorGuid): void
    {
        // TODO: Change status, save reason, trigger notification
    }

    public function disburse($application): void
    {
        // El flujo de este proyecto genera el prestamo y sus cuotas al aprobar.
    }

    public function deleteLoan(string $loanGuid, string $adminPassword): void
    {
        $user = auth()->user();
        if ($user === null || ! $user->inGroup('admin')) {
            throw new DatabaseException('Solo un administrador puede eliminar un prestamo.');
        }

        if (! password_verify($adminPassword, (string) $user->getPasswordHash())) {
            throw new DatabaseException('La clave del administrador es incorrecta.');
        }

        $db = Database::connect();
        $loanModel = new LoanModel();
        $installmentModel = new InstallmentModel();
        $paymentModel = new PaymentModel();
        $notificationModel = new NotificationModel();
        $auditModel = new AuditLogModel();
        $applicationModel = new LoanApplicationModel();

        $loan = $loanModel->find($loanGuid);
        if ($loan === null) {
            throw new DatabaseException('El prestamo no existe.');
        }

        $installments = $installmentModel->where('loan_guid', $loanGuid)->findAll();
        $isPaidOff = in_array((string) $loan->status, ['paid', 'paid_off'], true)
            || round((float) $loan->outstanding_balance, 2) <= 0;

        if (! $isPaidOff && $installments !== []) {
            $isPaidOff = array_reduce($installments, static function (bool $carry, object $installment): bool {
                $totalAmount = round((float) $installment->total_amount + (float) $installment->late_fee, 2);
                $paidAmount = round((float) $installment->paid_amount, 2);

                return $carry && $paidAmount >= $totalAmount;
            }, true);
        }

        if ($isPaidOff) {
            throw new DatabaseException('Los prestamos cancelados en su totalidad no pueden eliminarse del sistema.');
        }

        $db->transStart();

        $payments = $paymentModel->where('loan_guid', $loanGuid)->findAll();
        foreach ($payments as $payment) {
            $auditModel->where('entity_type', 'payment')->where('entity_guid', $payment->guid)->delete();
        }
        $paymentModel->where('loan_guid', $loanGuid)->delete(null, true);

        foreach ($installments as $installment) {
            $auditModel->where('entity_type', 'installment')->where('entity_guid', $installment->guid)->delete();
        }
        $installmentModel->where('loan_guid', $loanGuid)->delete(null, true);

        $notificationModel->where('loan_guid', $loanGuid)->delete();
        $auditModel->where('entity_type', 'loan')->where('entity_guid', $loanGuid)->delete();

        if (! empty($loan->application_guid)) {
            $applicationModel->update($loan->application_guid, [
                'status' => 'evaluation',
                'approved_amount' => null,
                'approved_by' => null,
                'disbursed_at' => null,
            ]);

            $auditModel->where('entity_type', 'loan_application')
                ->where('entity_guid', $loan->application_guid)
                ->where('action', 'loan.approved')
                ->delete();
        }

        $loanModel->delete($loanGuid, true);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('No se pudo eliminar el prestamo.');
        }
    }

    public function deleteRejectedApplication(string $applicationGuid, string $adminPassword): void
    {
        $user = auth()->user();
        if ($user === null || ! $user->inGroup('admin')) {
            throw new DatabaseException('Solo un administrador puede eliminar una solicitud.');
        }

        if (! password_verify($adminPassword, (string) $user->getPasswordHash())) {
            throw new DatabaseException('La clave del administrador es incorrecta.');
        }

        $db = Database::connect();
        $applicationModel = new LoanApplicationModel();
        $auditModel = new AuditLogModel();

        $application = $applicationModel->find($applicationGuid);
        if ($application === null) {
            throw new DatabaseException('La solicitud no existe.');
        }

        if ((string) $application->status !== 'rejected') {
            throw new DatabaseException('Solo pueden eliminarse solicitudes rechazadas.');
        }

        $db->transStart();

        $auditModel->where('entity_type', 'loan_application')
            ->where('entity_guid', $applicationGuid)
            ->delete();

        $applicationModel->delete($applicationGuid, true);

        $db->transComplete();

        if ($db->transStatus() === false) {
            throw new DatabaseException('No se pudo eliminar la solicitud rechazada.');
        }
    }

    public function close($loan): void
    {
        // TODO: Ensure all paid off, change status to closed
    }
}
