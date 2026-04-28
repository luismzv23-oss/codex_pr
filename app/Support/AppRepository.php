<?php

namespace App\Support;

use App\Models\AmortizationSystemModel;
use App\Models\AuditLogModel;
use App\Models\CustomerModel;
use App\Models\InstallmentModel;
use App\Models\LoanApplicationModel;
use App\Models\LoanModel;
use App\Models\PaymentModel;
use App\Services\AmortizationService;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use Throwable;

class AppRepository
{
    public function getDashboardData(): array
    {
        $customers = $this->getCustomers();
        $applications = $this->getApplications();
        $loans = $this->getLoans();
        $installments = $this->getInstallments();
        $payments = $this->getPayments();
        $auditLogs = $this->getAuditLogs();

        $activeLoans = array_values(array_filter($loans, static fn(array $loan): bool => $loan['status'] === 'active'));
        $overdueInstallments = array_values(array_filter($installments, static fn(array $item): bool => $item['status'] === 'overdue'));
        $pendingApplications = array_values(array_filter(
            $applications,
            static fn(array $application): bool => in_array($application['status'], ['draft', 'evaluation', 'approved'], true)
        ));

        $chartBuckets = $this->buildDashboardChartBuckets();

        foreach ($payments as $payment) {
            $bucket = $this->resolveDashboardBucket($payment['created_at'] ?? null);
            if ($bucket === null) {
                continue;
            }

            $chartBuckets[$bucket]['payments'] += round((float) ($payment['amount'] ?? 0), 2);
        }

        foreach ($installments as $installment) {
            $bucket = $this->resolveDashboardBucket($installment['due_date'] ?? null);
            if ($bucket === null || $installment['status'] !== 'overdue') {
                continue;
            }

            $chartBuckets[$bucket]['overdue'] += round((float) ($installment['amount_due'] ?? 0), 2);
        }

        foreach ($loans as $loan) {
            $bucket = $this->resolveDashboardBucket($loan['disbursed_at'] ?? $loan['created_at'] ?? null);
            if ($bucket === null) {
                continue;
            }

            $chartBuckets[$bucket]['loans'] += round((float) ($loan['principal_amount'] ?? 0), 2);
        }

        $upcomingInstallments = array_values(array_filter($installments, static function (array $item): bool {
            if ($item['status'] === 'paid') {
                return false;
            }

            $dueDate = strtotime($item['due_date']);

            return $dueDate >= strtotime('today') && $dueDate <= strtotime('+7 days');
        }));

        usort($auditLogs, static fn(array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));

        return [
            'stats' => [
                'customers' => count($customers),
                'pending_applications' => count($pendingApplications),
                'active_loans' => count($activeLoans),
                'monthly_income' => array_sum(array_map(static fn(array $payment): float => (float) $payment['amount'], $payments)),
                'overdue_amount' => array_sum(array_map(static fn(array $item): float => (float) ($item['amount_due'] ?? 0), $overdueInstallments)),
                'upcoming_installments' => count($upcomingInstallments),
            ],
            'chart' => [
                'labels' => array_map(
                    static fn(array $item): string => $item['label'],
                    array_values($chartBuckets)
                ),
                'series' => [
                    [
                        'name' => 'Pagos',
                        'data' => array_map(static fn(array $item): float => round($item['payments'], 2), array_values($chartBuckets)),
                    ],
                    [
                        'name' => 'Mora',
                        'data' => array_map(static fn(array $item): float => round($item['overdue'], 2), array_values($chartBuckets)),
                    ],
                    [
                        'name' => 'Prestamos',
                        'data' => array_map(static fn(array $item): float => round($item['loans'], 2), array_values($chartBuckets)),
                    ],
                ],
            ],
            'recent_activity' => array_slice($auditLogs, 0, 5),
        ];
    }

    public function getCustomers(): array
    {
        $customers = $this->safeFindAll(new CustomerModel(), 'customers');

        usort($customers, static fn(array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));

        return $customers;
    }

    public function getCustomer(string $guid): ?array
    {
        foreach ($this->getCustomers() as $customer) {
            if ($customer['guid'] === $guid) {
                return $customer;
            }
        }

        return null;
    }

    public function saveCustomer(array $data): bool
    {
        try {
            return (new CustomerModel())->save($data);
        } catch (Throwable) {
            return false;
        }
    }

    public function getApplications(): array
    {
        $applications = $this->safeFindAll(new LoanApplicationModel(), 'applications');
        $customers = $this->getCustomersIndexed();

        foreach ($applications as &$application) {
            $application['customer_name'] = $customers[$application['customer_guid']]['full_name'] ?? 'Cliente no disponible';
        }
        unset($application);

        usort($applications, static fn(array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));

        return $applications;
    }

    public function getApplication(string $guid): ?array
    {
        foreach ($this->getApplications() as $application) {
            if ($application['guid'] === $guid) {
                $application['linked_loan_guid'] = $this->getLoanByApplication($guid)['guid'] ?? null;

                return $application;
            }
        }

        return null;
    }

    public function saveApplication(array $data): bool
    {
        try {
            return (new LoanApplicationModel())->save($data);
        } catch (Throwable) {
            return false;
        }
    }

    public function updateApplicationStatus(string $guid, array $data): bool
    {
        try {
            return (new LoanApplicationModel())->update($guid, $data);
        } catch (Throwable) {
            return false;
        }
    }

    public function getLoans(): array
    {
        $loans = $this->safeFindAll(new LoanModel(), 'loans');
        $customers = $this->getCustomersIndexed();

        foreach ($loans as &$loan) {
            $loan['customer_name'] = $customers[$loan['customer_guid']]['full_name'] ?? 'Cliente no disponible';
        }
        unset($loan);

        usort($loans, static fn(array $a, array $b): int => strcmp($b['disbursed_at'], $a['disbursed_at']));

        return $loans;
    }

    public function getLoansByStatus(string $statusFilter = 'active'): array
    {
        $statusMap = [
            'active' => ['active', 'defaulted', 'restructured'],
            'cancelled' => ['paid_off'],
        ];

        $allowedStatuses = $statusMap[$statusFilter] ?? $statusMap['active'];

        return array_values(array_filter(
            $this->getLoans(),
            static fn(array $loan): bool => in_array($loan['status'], $allowedStatuses, true)
        ));
    }

    public function getLoan(string $guid): ?array
    {
        foreach ($this->getLoans() as $loan) {
            if ($loan['guid'] === $guid) {
                return $this->synchronizeUntouchedLoanSchedule($loan);
            }
        }

        return null;
    }

    public function getLoanByApplication(string $applicationGuid): ?array
    {
        foreach ($this->getLoans() as $loan) {
            if (($loan['application_guid'] ?? null) === $applicationGuid) {
                return $loan;
            }
        }

        return null;
    }

    public function getApprovalQueue(): array
    {
        return array_values(array_filter(
            $this->getApplications(),
            fn(array $application): bool => in_array($application['status'], ['draft', 'evaluation'], true) && $this->getLoanByApplication($application['guid']) === null
        ));
    }

    public function getLoanInstallments(string $loanGuid): array
    {
        $loan = $this->getLoan($loanGuid);
        if ($loan !== null) {
            $this->ensureLoanSchedule($loan);
        }

        $installments = array_values(array_filter(
            $this->getInstallments(),
            static fn(array $item): bool => $item['loan_guid'] === $loanGuid
        ));

        usort($installments, static fn(array $a, array $b): int => ($a['installment_number'] <=> $b['installment_number']));

        $nextPayableGuid = null;
        foreach ($installments as $item) {
            if (($item['amount_due'] ?? 0) > 0) {
                $nextPayableGuid = $item['guid'];
                break;
            }
        }

        foreach ($installments as &$installment) {
            $installment['can_generate_payment'] = $installment['guid'] === $nextPayableGuid;
        }
        unset($installment);

        return $installments;
    }

    public function getCustomerInstallments(string $customerGuid, ?string $status = null): array
    {
        $loanGuids = array_column(array_filter(
            $this->getLoans(),
            static fn(array $loan): bool => $loan['customer_guid'] === $customerGuid
        ), 'guid');

        $installments = array_values(array_filter($this->getInstallments(), static function (array $item) use ($loanGuids, $status): bool {
            if (! in_array($item['loan_guid'], $loanGuids, true)) {
                return false;
            }

            if ($status !== null && $status !== 'all' && $item['status'] !== $status) {
                return false;
            }

            return true;
        }));

        $loans = $this->getLoansIndexed();
        foreach ($installments as &$installment) {
            $loan = $loans[$installment['loan_guid']] ?? null;
            $installment['currency'] = $loan['currency'] ?? 'ARS';
            $installment['loan_label'] = $loan['guid'] ?? $installment['loan_guid'];
        }
        unset($installment);

        usort($installments, static fn(array $a, array $b): int => strcmp($a['due_date'], $b['due_date']));

        return $installments;
    }

    public function getInstallment(string $guid): ?array
    {
        foreach ($this->getInstallments() as $installment) {
            if ($installment['guid'] === $guid) {
                return $installment;
            }
        }

        return null;
    }

    public function getInstallments(): array
    {
        $installments = $this->safeFindAll(new InstallmentModel(), 'installments');

        foreach ($installments as &$installment) {
            $installment['status'] = $this->normalizeInstallmentStatus($installment);
            $installment['amount_due'] = max(
                0,
                round(((float) ($installment['total_amount'] ?? 0) + (float) ($installment['late_fee'] ?? 0)) - (float) ($installment['paid_amount'] ?? 0), 2)
            );
        }
        unset($installment);

        usort($installments, static fn(array $a, array $b): int => strcmp($a['due_date'], $b['due_date']));

        return $installments;
    }

    public function getPayments(): array
    {
        $payments = $this->safeFindAll(new PaymentModel(), 'payments');
        $loans = $this->getLoansIndexed();
        $customers = $this->getCustomersIndexed();

        foreach ($payments as &$payment) {
            $payment['loan_label'] = $loans[$payment['loan_guid']]['guid'] ?? 'Prestamo';
            $payment['customer_name'] = $customers[$payment['customer_guid']]['full_name'] ?? 'Cliente no disponible';
        }
        unset($payment);

        usort($payments, static fn(array $a, array $b): int => strcmp($b['created_at'], $a['created_at']));

        return $payments;
    }

    public function savePayment(array $data): bool
    {
        try {
            return (new PaymentModel())->save($data);
        } catch (Throwable) {
            return false;
        }
    }

    public function getAuditLogs(): array
    {
        return $this->safeFindAll(new AuditLogModel(), 'audit_logs');
    }

    public function getCustomerStatementByLoan(string $loanGuid): ?array
    {
        $loan = $this->getLoan($loanGuid);
        if ($loan === null) {
            return null;
        }

        $customer = $this->getCustomer($loan['customer_guid']);
        if ($customer === null) {
            return null;
        }

        $installments = $this->getCustomerInstallments($customer['guid']);
        $payments = array_values(array_filter(
            $this->getPayments(),
            static fn(array $payment): bool => $payment['customer_guid'] === $customer['guid']
        ));

        $installmentsGrouped = [];
        foreach ($installments as $item) {
            $loanKey = $item['loan_guid'];
            if (! isset($installmentsGrouped[$loanKey])) {
                $loanData = $this->getLoan($loanKey);
                $installmentsGrouped[$loanKey] = [
                    'loan' => $loanData,
                    'items' => [],
                ];
            }

            $installmentsGrouped[$loanKey]['items'][] = $item;
        }

        $paymentsGrouped = [];
        foreach ($payments as $payment) {
            $loanKey = $payment['loan_guid'];
            if (! isset($paymentsGrouped[$loanKey])) {
                $loanData = $this->getLoan($loanKey);
                $paymentsGrouped[$loanKey] = [
                    'loan' => $loanData,
                    'items' => [],
                ];
            }

            $paymentsGrouped[$loanKey]['items'][] = $payment;
        }

        return [
            'customer' => $customer,
            'loan' => $loan,
            'installments' => $installments,
            'payments' => $payments,
            'installments_grouped' => array_values($installmentsGrouped),
            'payments_grouped' => array_values($paymentsGrouped),
            'summary' => [
                'total_installments' => count($installments),
                'paid_installments' => count(array_filter($installments, static fn(array $item): bool => $item['status'] === 'paid')),
                'pending_installments' => count(array_filter($installments, static fn(array $item): bool => in_array($item['status'], ['pending', 'partial', 'overdue'], true))),
                'total_paid' => array_sum(array_map(static fn(array $item): float => (float) $item['paid_amount'], $installments)),
                'total_pending' => array_sum(array_map(static fn(array $item): float => max(0, (float) $item['total_amount'] - (float) $item['paid_amount']), $installments)),
                'customer_total_debt' => $this->getCustomerTotalDebt($customer['guid']),
            ],
        ];
    }

    public function getCustomerTotalDebt(string $customerGuid): float
    {
        $loans = array_filter(
            $this->getLoans(),
            static fn(array $loan): bool => $loan['customer_guid'] === $customerGuid && in_array($loan['status'], ['active', 'defaulted', 'restructured'], true)
        );

        return round(array_sum(array_map(static fn(array $loan): float => (float) ($loan['outstanding_balance'] ?? 0), $loans)), 2);
    }

    public function getAmortizationSystems(bool $onlyActive = false): array
    {
        try {
            $systems = array_map([$this, 'normalize'], (new AmortizationSystemModel())->asArray()->findAll());
        } catch (Throwable) {
            $systems = $this->fallbackData()['amortization_systems'];
        }

        if ($onlyActive) {
            $systems = array_values(array_filter($systems, static fn(array $item): bool => ($item['status'] ?? 'active') === 'active'));
        }

        usort($systems, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $systems;
    }

    public function getAmortizationSystem(string $guid): ?array
    {
        foreach ($this->getAmortizationSystems() as $system) {
            if ($system['guid'] === $guid) {
                return $system;
            }
        }

        return null;
    }

    public function findAmortizationSystemByCode(string $code, bool $withDeleted = false): ?array
    {
        $normalizedCode = strtolower(trim($code));

        try {
            $model = new AmortizationSystemModel();
            if ($withDeleted) {
                $model->withDeleted();
            }

            $system = $model
                ->asArray()
                ->where('LOWER(code)', $normalizedCode)
                ->first();

            return $system ? $this->normalize($system) : null;
        } catch (Throwable) {
            foreach ($this->fallbackData()['amortization_systems'] as $system) {
                if (strcasecmp((string) ($system['code'] ?? ''), $normalizedCode) === 0) {
                    return $system;
                }
            }

            return null;
        }
    }

    public function createAmortizationSystem(array $data): bool
    {
        try {
            $model = new AmortizationSystemModel();
            $existing = $this->findAmortizationSystemByCode((string) ($data['code'] ?? ''), true);

            if ($existing !== null) {
                if (empty($existing['deleted_at'])) {
                    return false;
                }

                return (bool) db_connect()
                    ->table('amortization_systems')
                    ->where('guid', $existing['guid'])
                    ->update([
                        'code' => $data['code'],
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'status' => $data['status'],
                        'deleted_at' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            return (bool) $model->insert($data);
        } catch (Throwable) {
            return false;
        }
    }

    public function updateAmortizationSystem(string $guid, array $data): bool
    {
        try {
            $model = new AmortizationSystemModel();
            $system = $model->find($guid);
            if ($system === null) {
                return false;
            }

            $oldCode = strtolower((string) ($system['code'] ?? ''));
            $newCode = strtolower((string) ($data['code'] ?? $oldCode));
            $db = db_connect();
            $db->transStart();

            $saved = $model->update($guid, $data);

            if ($saved && $oldCode !== $newCode) {
                $db->table('loan_applications')->where('LOWER(amortization_type)', $oldCode)->update(['amortization_type' => $newCode]);
                $db->table('loans')->where('LOWER(amortization_type)', $oldCode)->update(['amortization_type' => $newCode]);
            }

            $db->transComplete();

            return $saved && $db->transStatus() !== false;
        } catch (Throwable) {
            return false;
        }
    }

    public function deleteAmortizationSystem(string $guid): bool
    {
        try {
            return (bool) (new AmortizationSystemModel())->delete($guid);
        } catch (Throwable) {
            return false;
        }
    }

    public function deleteApplication(string $guid): bool
    {
        try {
            return (bool) (new LoanApplicationModel())->delete($guid, true);
        } catch (Throwable) {
            return false;
        }
    }

    public function getUsers(): array
    {
        try {
            $users = model(UserModel::class)->withIdentities()->findAll();

            return array_map(static function (User $user): array {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->getEmail(),
                    'active' => (bool) $user->active,
                    'created_at' => (string) $user->created_at,
                ];
            }, $users);
        } catch (Throwable) {
            return $this->fallbackData()['users'];
        }
    }

    public function getUser(int $id): ?array
    {
        foreach ($this->getUsers() as $user) {
            if ((int) $user['id'] === $id) {
                return $user;
            }
        }

        return null;
    }

    public function saveUser(array $data): bool
    {
        try {
            $model = model(UserModel::class);
            if (! empty($data['id'])) {
                $user = $model->findById((int) $data['id']);
                if ($user === null) {
                    return false;
                }
                $user->username = $data['username'];
                $user->email = $data['email'];
                if (array_key_exists('active', $data)) {
                    $user->active = ! empty($data['active']) ? 1 : 0;
                }
                if (! empty($data['password'])) {
                    $user->password = $data['password'];
                }

                return $model->save($user);
            }

            $user = new User([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'] ?? 'ChangeMe123!',
                'active' => ! empty($data['active']) ? 1 : 0,
            ]);

            return $model->save($user);
        } catch (Throwable) {
            return false;
        }
    }

    public function deleteUser(int $id): bool
    {
        try {
            return (bool) model(UserModel::class)->delete($id, true);
        } catch (Throwable) {
            return false;
        }
    }

    public function toggleUserStatus(int $id): bool
    {
        try {
            $model = model(UserModel::class);
            $user = $model->findById($id);
            if ($user === null) {
                return false;
            }
            $user->active = $user->active ? 0 : 1;

            return $model->save($user);
        } catch (Throwable) {
            return false;
        }
    }

    public function toggleAmortizationSystem(string $guid): bool
    {
        try {
            $model = new AmortizationSystemModel();
            $system = $model->find($guid);
            if ($system === null) {
                return false;
            }

            $system['status'] = ($system['status'] ?? 'active') === 'active' ? 'disabled' : 'active';

            return $model->save($system);
        } catch (Throwable) {
            foreach ($this->fallbackData()['amortization_systems'] as $system) {
                if ($system['guid'] === $guid) {
                    return true;
                }
            }

            return false;
        }
    }

    public function amortizationSystemInUse(string $code): bool
    {
        $normalizedCode = strtolower(trim($code));

        try {
            $applications = (new LoanApplicationModel())
                ->withDeleted()
                ->asArray()
                ->select('guid')
                ->where('LOWER(amortization_type)', $normalizedCode)
                ->first();

            if ($applications !== null) {
                return true;
            }

            $loans = (new LoanModel())
                ->withDeleted()
                ->asArray()
                ->select('guid')
                ->where('LOWER(amortization_type)', $normalizedCode)
                ->first();

            return $loans !== null;
        } catch (Throwable) {
            foreach (['applications', 'loans'] as $bucket) {
                foreach ($this->fallbackData()[$bucket] as $row) {
                    if (strcasecmp((string) ($row['amortization_type'] ?? ''), $normalizedCode) === 0) {
                        return true;
                    }
                }
            }

            return false;
        }
    }

    public function countActiveAmortizationSystems(): int
    {
        return count($this->getAmortizationSystems(true));
    }

    private function getCustomersIndexed(): array
    {
        $indexed = [];

        foreach ($this->getCustomers() as $customer) {
            $indexed[$customer['guid']] = $customer;
        }

        return $indexed;
    }

    private function getLoansIndexed(): array
    {
        $indexed = [];

        foreach ($this->getLoans() as $loan) {
            $indexed[$loan['guid']] = $loan;
        }

        return $indexed;
    }

    private function safeFindAll(object $model, string $fallback): array
    {
        try {
            return array_map([$this, 'normalize'], $model->asArray()->findAll());
        } catch (Throwable) {
            return $this->fallbackData()[$fallback];
        }
    }

    private function normalize(array $row): array
    {
        if (isset($row['first_name'], $row['last_name'])) {
            $row['full_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
        }

        return $row;
    }

    private function normalizeInstallmentStatus(array $installment): string
    {
        $paidAmount = (float) ($installment['paid_amount'] ?? 0);
        $totalAmount = (float) ($installment['total_amount'] ?? 0) + (float) ($installment['late_fee'] ?? 0);

        if (($installment['status'] ?? null) === 'paid' && $totalAmount <= 0) {
            return 'paid';
        }

        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            return 'paid';
        }

        $isOverdue = ! empty($installment['due_date']) && strtotime((string) $installment['due_date']) < strtotime('today');

        if ($paidAmount > 0) {
            return $isOverdue ? 'overdue' : 'partial';
        }

        return $isOverdue ? 'overdue' : (string) ($installment['status'] ?? 'pending');
    }

    private function ensureLoanSchedule(array $loan): void
    {
        try {
            $model = new InstallmentModel();
            $existing = $model->asArray()
                ->where('loan_guid', $loan['guid'])
                ->orderBy('installment_number', 'ASC')
                ->findAll();

            if (count($existing) >= (int) ($loan['term_months'] ?? 0)) {
                return;
            }

            $existingByNumber = [];
            foreach ($existing as $item) {
                $existingByNumber[(int) $item['installment_number']] = $item;
            }

            $schedule = (new AmortizationService())->generateSchedule($loan);

            foreach ($schedule as $item) {
                $number = (int) $item['installment_number'];
                if (isset($existingByNumber[$number])) {
                    continue;
                }

                $model->insert([
                    'loan_guid' => $loan['guid'],
                    'installment_number' => $number,
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
        } catch (Throwable) {
            // Fallback/demo mode does not persist generated schedules.
        }
    }

    private function synchronizeUntouchedLoanSchedule(array $loan): array
    {
        try {
            $installmentModel = new InstallmentModel();
            $paymentModel = new PaymentModel();
            $loanModel = new LoanModel();

            $existing = $installmentModel->asArray()
                ->where('loan_guid', $loan['guid'])
                ->orderBy('installment_number', 'ASC')
                ->findAll();

            if ($existing === []) {
                return $loan;
            }

            if ($paymentModel->where('loan_guid', $loan['guid'])->countAllResults() > 0) {
                return $loan;
            }

            foreach ($existing as $item) {
                if (
                    round((float) ($item['paid_amount'] ?? 0), 2) > 0
                    || round((float) ($item['late_fee'] ?? 0), 2) > 0
                    || in_array((string) ($item['status'] ?? 'pending'), ['paid', 'partial'], true)
                ) {
                    return $loan;
                }
            }

            $expected = (new AmortizationService())->generateSchedule($loan);
            if ($expected === []) {
                return $loan;
            }

            if (! $this->loanScheduleNeedsSync($loan, $existing, $expected)) {
                return $loan;
            }

            $existingByNumber = [];
            foreach ($existing as $item) {
                $existingByNumber[(int) $item['installment_number']] = $item;
            }

            $expectedNumbers = array_map(
                static fn(array $item): int => (int) $item['installment_number'],
                $expected
            );

            foreach ($existing as $item) {
                if (! in_array((int) $item['installment_number'], $expectedNumbers, true)) {
                    $installmentModel->delete($item['guid'], true);
                }
            }

            foreach ($expected as $item) {
                $number = (int) $item['installment_number'];
                $payload = [
                    'due_date' => $item['due_date'],
                    'principal_amount' => $item['principal_amount'],
                    'interest_amount' => $item['interest_amount'],
                    'total_amount' => $item['total_amount'],
                    'paid_amount' => 0,
                    'remaining_balance' => $item['remaining_balance'],
                    'status' => strtotime($item['due_date']) < strtotime('today') ? 'overdue' : 'pending',
                    'paid_at' => null,
                    'late_fee' => 0,
                ];

                if (isset($existingByNumber[$number])) {
                    $installmentModel->update($existingByNumber[$number]['guid'], $payload);
                    continue;
                }

                $installmentModel->insert($payload + [
                    'loan_guid' => $loan['guid'],
                    'installment_number' => $number,
                ]);
            }

            $totalPayable = round(array_sum(array_column($expected, 'total_amount')), 2);
            $totalInterest = round(array_sum(array_column($expected, 'interest_amount')), 2);

            $loanModel->update($loan['guid'], [
                'total_interest' => $totalInterest,
                'total_payable' => $totalPayable,
                'outstanding_balance' => $totalPayable,
                'next_due_date' => $expected[0]['due_date'] ?? null,
                'status' => 'active',
                'closed_at' => null,
            ]);

            $loan['total_interest'] = $totalInterest;
            $loan['total_payable'] = $totalPayable;
            $loan['outstanding_balance'] = $totalPayable;
            $loan['next_due_date'] = $expected[0]['due_date'] ?? ($loan['next_due_date'] ?? null);
            $loan['status'] = 'active';

            return $loan;
        } catch (Throwable) {
            return $loan;
        }
    }

    private function loanScheduleNeedsSync(array $loan, array $existing, array $expected): bool
    {
        if (count($existing) !== count($expected)) {
            return true;
        }

        $expectedTotalPayable = round(array_sum(array_column($expected, 'total_amount')), 2);
        if ($this->moneyDiffers((float) ($loan['total_payable'] ?? 0), $expectedTotalPayable)) {
            return true;
        }

        foreach ($expected as $index => $item) {
            $current = $existing[$index] ?? null;
            if ($current === null) {
                return true;
            }

            if (
                $this->moneyDiffers((float) ($current['principal_amount'] ?? 0), (float) $item['principal_amount'])
                || $this->moneyDiffers((float) ($current['interest_amount'] ?? 0), (float) $item['interest_amount'])
                || $this->moneyDiffers((float) ($current['total_amount'] ?? 0), (float) $item['total_amount'])
                || $this->moneyDiffers((float) ($current['remaining_balance'] ?? 0), (float) $item['remaining_balance'])
            ) {
                return true;
            }
        }

        return false;
    }

    private function moneyDiffers(float $left, float $right): bool
    {
        return abs(round($left, 2) - round($right, 2)) > 0.01;
    }

    private function buildDashboardChartBuckets(): array
    {
        $buckets = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $timestamp = strtotime('-' . $offset . ' month');
            $key = date('Y-m', $timestamp);

            $buckets[$key] = [
                'label' => date('M Y', $timestamp),
                'payments' => 0.0,
                'overdue' => 0.0,
                'loans' => 0.0,
            ];
        }

        return $buckets;
    }

    private function resolveDashboardBucket(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return null;
        }

        $bucket = date('Y-m', $timestamp);
        $allowedBuckets = array_keys($this->buildDashboardChartBuckets());

        return in_array($bucket, $allowedBuckets, true) ? $bucket : null;
    }

    private function fallbackData(): array
    {
        return [
            'customers' => [
                [
                    'guid' => 'cst-demo-001',
                    'first_name' => 'Sofia',
                    'last_name' => 'Ramirez',
                    'full_name' => 'Sofia Ramirez',
                    'dni' => '28111222',
                    'email' => 'sofia.ramirez@example.test',
                    'phone' => '+54 11 5555 0101',
                    'dni_encrypted' => '',
                    'address' => 'Palermo, Buenos Aires',
                    'estimated_income' => 1850000,
                    'credit_limit' => 650000,
                    'credit_limit_mode' => 'manual',
                    'credit_status' => 'active',
                    'kyc_status' => 'verified',
                    'kyc_verified_at' => '2026-04-10 09:00:00',
                    'risk_score' => 14.8,
                    'notes' => 'Perfil con pagos consistentes.',
                    'created_at' => '2026-04-01 10:00:00',
                ],
                [
                    'guid' => 'cst-demo-002',
                    'first_name' => 'Martin',
                    'last_name' => 'Lopez',
                    'full_name' => 'Martin Lopez',
                    'dni' => '30123456',
                    'email' => 'martin.lopez@example.test',
                    'phone' => '+54 11 5555 0102',
                    'dni_encrypted' => '',
                    'address' => 'Cordoba Capital',
                    'estimated_income' => 960000,
                    'credit_limit' => 336000,
                    'credit_limit_mode' => 'automatic',
                    'credit_status' => 'restricted',
                    'kyc_status' => 'pending',
                    'kyc_verified_at' => null,
                    'risk_score' => 38.2,
                    'notes' => 'Solicitud en evaluacion manual.',
                    'created_at' => '2026-04-12 15:30:00',
                ],
                [
                    'guid' => 'cst-demo-003',
                    'first_name' => 'Valentina',
                    'last_name' => 'Suarez',
                    'full_name' => 'Valentina Suarez',
                    'dni' => '27555111',
                    'email' => 'valentina.suarez@example.test',
                    'phone' => '+54 351 555 0199',
                    'dni_encrypted' => '',
                    'address' => 'Rosario, Santa Fe',
                    'estimated_income' => 1220000,
                    'credit_limit' => 427000,
                    'credit_limit_mode' => 'automatic',
                    'credit_status' => 'active',
                    'kyc_status' => 'verified',
                    'kyc_verified_at' => '2026-04-14 11:40:00',
                    'risk_score' => 21.4,
                    'notes' => 'Cliente apto para ticket medio.',
                    'created_at' => '2026-04-14 11:00:00',
                ],
            ],
            'applications' => [
                [
                    'guid' => 'app-demo-001',
                    'customer_guid' => 'cst-demo-001',
                    'requested_amount' => 250000,
                    'approved_amount' => 250000,
                    'currency' => 'ARS',
                    'interest_rate' => 0.085,
                    'term_months' => 12,
                    'amortization_type' => 'french',
                    'status' => 'approved',
                    'rejection_reason' => null,
                    'disbursed_at' => null,
                    'created_at' => '2026-04-15 09:30:00',
                ],
                [
                    'guid' => 'app-demo-002',
                    'customer_guid' => 'cst-demo-002',
                    'requested_amount' => 400000,
                    'approved_amount' => null,
                    'currency' => 'ARS',
                    'interest_rate' => 0.0975,
                    'term_months' => 18,
                    'amortization_type' => 'german',
                    'status' => 'evaluation',
                    'rejection_reason' => null,
                    'disbursed_at' => null,
                    'created_at' => '2026-04-18 16:00:00',
                ],
                [
                    'guid' => 'app-demo-003',
                    'customer_guid' => 'cst-demo-003',
                    'requested_amount' => 150000,
                    'approved_amount' => 150000,
                    'currency' => 'ARS',
                    'interest_rate' => 0.079,
                    'term_months' => 10,
                    'amortization_type' => 'american',
                    'status' => 'disbursed',
                    'rejection_reason' => null,
                    'disbursed_at' => '2026-04-20 14:20:00',
                    'created_at' => '2026-04-16 13:10:00',
                ],
            ],
            'loans' => [
                [
                    'guid' => 'loan-demo-001',
                    'application_guid' => 'app-demo-003',
                    'customer_guid' => 'cst-demo-003',
                    'currency' => 'ARS',
                    'principal_amount' => 150000,
                    'interest_rate' => 0.079,
                    'term_months' => 10,
                    'amortization_type' => 'american',
                    'total_interest' => 11850,
                    'total_payable' => 161850,
                    'outstanding_balance' => 129480,
                    'status' => 'active',
                    'next_due_date' => date('Y-m-d', strtotime('+3 days')),
                    'disbursed_at' => '2026-04-20 14:20:00',
                    'closed_at' => null,
                    'created_at' => '2026-04-20 14:20:00',
                ],
                [
                    'guid' => 'loan-demo-002',
                    'application_guid' => 'app-demo-001',
                    'customer_guid' => 'cst-demo-001',
                    'currency' => 'ARS',
                    'principal_amount' => 250000,
                    'interest_rate' => 0.085,
                    'term_months' => 12,
                    'amortization_type' => 'french',
                    'total_interest' => 26500,
                    'total_payable' => 276500,
                    'outstanding_balance' => 184300,
                    'status' => 'active',
                    'next_due_date' => date('Y-m-d', strtotime('-2 days')),
                    'disbursed_at' => '2026-04-11 12:00:00',
                    'closed_at' => null,
                    'created_at' => '2026-04-11 12:00:00',
                ],
            ],
            'installments' => [
                [
                    'guid' => 'ins-demo-001',
                    'loan_guid' => 'loan-demo-001',
                    'installment_number' => 1,
                    'due_date' => date('Y-m-d', strtotime('-12 days')),
                    'principal_amount' => 0,
                    'interest_amount' => 1185,
                    'total_amount' => 1185,
                    'paid_amount' => 1185,
                    'remaining_balance' => 150000,
                    'status' => 'paid',
                    'late_fee' => 0,
                    'created_at' => '2026-04-01 09:00:00',
                ],
                [
                    'guid' => 'ins-demo-002',
                    'loan_guid' => 'loan-demo-001',
                    'installment_number' => 2,
                    'due_date' => date('Y-m-d', strtotime('+3 days')),
                    'principal_amount' => 0,
                    'interest_amount' => 1185,
                    'total_amount' => 1185,
                    'paid_amount' => 0,
                    'remaining_balance' => 150000,
                    'status' => 'pending',
                    'late_fee' => 0,
                    'created_at' => '2026-04-21 09:00:00',
                ],
                [
                    'guid' => 'ins-demo-003',
                    'loan_guid' => 'loan-demo-002',
                    'installment_number' => 1,
                    'due_date' => date('Y-m-d', strtotime('-16 days')),
                    'principal_amount' => 19000,
                    'interest_amount' => 1770,
                    'total_amount' => 20770,
                    'paid_amount' => 20770,
                    'remaining_balance' => 231000,
                    'status' => 'paid',
                    'late_fee' => 0,
                    'created_at' => '2026-04-05 10:00:00',
                ],
                [
                    'guid' => 'ins-demo-004',
                    'loan_guid' => 'loan-demo-002',
                    'installment_number' => 2,
                    'due_date' => date('Y-m-d', strtotime('-2 days')),
                    'principal_amount' => 19340,
                    'interest_amount' => 1430,
                    'total_amount' => 20770,
                    'paid_amount' => 0,
                    'remaining_balance' => 211660,
                    'status' => 'overdue',
                    'late_fee' => 420,
                    'created_at' => '2026-04-18 10:00:00',
                ],
            ],
            'payments' => [
                [
                    'guid' => 'pay-demo-001',
                    'loan_guid' => 'loan-demo-001',
                    'installment_guid' => 'ins-demo-001',
                    'customer_guid' => 'cst-demo-003',
                    'amount' => 1185,
                    'currency' => 'ARS',
                    'payment_method' => 'transfer',
                    'reference_number' => 'TRX-1001',
                    'notes' => 'Pago puntual.',
                    'created_at' => '2026-04-10 12:35:00',
                ],
                [
                    'guid' => 'pay-demo-002',
                    'loan_guid' => 'loan-demo-002',
                    'installment_guid' => 'ins-demo-003',
                    'customer_guid' => 'cst-demo-001',
                    'amount' => 20770,
                    'currency' => 'ARS',
                    'payment_method' => 'cash',
                    'reference_number' => 'REC-2026-04-15',
                    'notes' => 'Caja sucursal centro.',
                    'created_at' => '2026-04-15 17:10:00',
                ],
            ],
            'audit_logs' => [
                [
                    'guid' => 'aud-demo-001',
                    'action' => 'loan.approved',
                    'entity_type' => 'loan_application',
                    'entity_guid' => 'app-demo-001',
                    'new_values' => json_encode(['status' => 'approved']),
                    'created_at' => '2026-04-15 10:00:00',
                ],
                [
                    'guid' => 'aud-demo-002',
                    'action' => 'loan.disbursed',
                    'entity_type' => 'loan',
                    'entity_guid' => 'loan-demo-001',
                    'new_values' => json_encode(['status' => 'active']),
                    'created_at' => '2026-04-20 14:20:00',
                ],
                [
                    'guid' => 'aud-demo-003',
                    'action' => 'payment.received',
                    'entity_type' => 'payment',
                    'entity_guid' => 'pay-demo-002',
                    'new_values' => json_encode(['amount' => 20770]),
                    'created_at' => '2026-04-15 17:10:00',
                ],
            ],
            'amortization_systems' => [
                [
                    'guid' => 'sys-demo-001',
                    'code' => 'french',
                    'name' => 'Frances',
                    'description' => 'Cuota fija con interes decreciente.',
                    'status' => 'active',
                ],
                [
                    'guid' => 'sys-demo-002',
                    'code' => 'german',
                    'name' => 'Aleman',
                    'description' => 'Capital fijo y cuota decreciente.',
                    'status' => 'active',
                ],
                [
                    'guid' => 'sys-demo-003',
                    'code' => 'american',
                    'name' => 'Americano',
                    'description' => 'Interes periodico y capital al vencimiento.',
                    'status' => 'active',
                ],
            ],
            'users' => [
                [
                    'id' => 1,
                    'username' => 'admin',
                    'email' => 'admin@fintech.local',
                    'active' => true,
                    'created_at' => '2026-04-22 00:00:00',
                ],
            ],
        ];
    }
}
