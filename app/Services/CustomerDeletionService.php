<?php

namespace App\Services;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Shield\Entities\User;
use Config\Database;
use DomainException;
use InvalidArgumentException;
use OutOfBoundsException;
use RuntimeException;
use Throwable;

class CustomerDeletionService
{
    private ?BaseConnection $db;
    private PdfDocumentService $documents;

    public function __construct(?BaseConnection $db = null, ?PdfDocumentService $documents = null)
    {
        $this->db = $db;
        $this->documents = $documents ?? new PdfDocumentService();
    }

    public function preview(string $customerGuid, ?User $actor): array
    {
        $this->authorize($actor);
        return $this->summarize($this->collect($customerGuid));
    }

    public function delete(string $customerGuid, ?User $actor, string $password): array
    {
        $this->authorize($actor);
        if ($password === '' || ! password_verify($password, (string) $actor->getPasswordHash())) {
            throw new InvalidArgumentException('La contraseña del administrador es incorrecta.');
        }

        $db = $this->connection();
        if (! $db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar la eliminación.');
        }

        try {
            // Lock the customer and its dependents and include previous soft deletes.
            $records = $this->collect($customerGuid, true);
            $contracts = [];
            foreach ($records['loans'] as $loan) {
                // Stored identifiers must never escape the contract directory.
                if (! preg_match('/^[a-zA-Z0-9_-]{1,36}$/D', $loan['guid'])) {
                    throw new RuntimeException('Identificador de contrato inválido.');
                }
                $contracts[] = $this->documents->contractPath($loan['guid']);
            }

            foreach ([
                'customer' => [$customerGuid],
                'loan_application' => array_column($records['loan_applications'], 'guid'),
                'loan' => array_column($records['loans'], 'guid'),
                'installment' => array_column($records['installments'], 'guid'),
                'payment' => array_column($records['payments'], 'guid'),
                'notification' => array_column($records['notifications'], 'guid'),
            ] as $type => $guids) {
                if ($guids !== []) {
                    $this->executeDelete($db->table('audit_logs')->where('entity_type', $type)->whereIn('entity_guid', $guids));
                }
            }

            // Children first; Query Builder DELETE is physical even for soft-delete models.
            foreach (['payments', 'notifications', 'installments', 'loans', 'loan_applications'] as $table) {
                $guids = array_column($records[$table], 'guid');
                if ($guids !== []) {
                    $this->executeDelete($db->table($table)->whereIn('guid', $guids));
                }
            }
            $this->executeDelete($db->table('customers')->where('guid', $customerGuid));
            if (! $db->transStatus() || ! $db->transCommit()) {
                throw new RuntimeException('No se pudo completar la eliminación.');
            }
        } catch (Throwable $exception) {
            $db->transRollback();
            throw $exception;
        }

        // Files cannot join an SQL transaction. Clean them only after a successful commit.
        $pending = 0;
        foreach ($contracts as $path) {
            if ((file_exists($path) || is_link($path)) && ! @unlink($path)) {
                $pending++;
                log_message('error', 'Contrato pendiente de eliminar: {path}', ['path' => $path]);
            }
        }

        return array_merge($this->summarize($records), ['pending_contracts' => $pending]);
    }

    private function authorize(?User $actor): void
    {
        if ($actor === null || ! $actor->can('customers.delete')) {
            throw new DomainException('No tenés permiso para eliminar clientes.');
        }
    }

    private function connection(): BaseConnection
    {
        return $this->db ?? ($this->db = Database::connect());
    }

    private function collect(string $customerGuid, bool $lock = false): array
    {
        $db = $this->connection();
        $customers = $this->rows($db->table('customers')->where('guid', $customerGuid), $lock);
        if ($customers === []) {
            throw new OutOfBoundsException('El cliente no existe o ya fue eliminado.');
        }
        $applications = $this->rows($db->table('loan_applications')->where('customer_guid', $customerGuid), $lock);
        $loansQuery = $db->table('loans')->groupStart()->where('customer_guid', $customerGuid);
        $this->orIds($loansQuery, 'application_guid', array_column($applications, 'guid'));
        $loans = $this->rows($loansQuery->groupEnd(), $lock);
        $loanGuids = array_column($loans, 'guid');
        $installments = $loanGuids === [] ? [] : $this->rows($db->table('installments')->whereIn('loan_guid', $loanGuids), $lock);

        $paymentsQuery = $db->table('payments')->groupStart()->where('customer_guid', $customerGuid);
        $this->orIds($paymentsQuery, 'loan_guid', $loanGuids);
        $this->orIds($paymentsQuery, 'installment_guid', array_column($installments, 'guid'));
        $payments = $this->rows($paymentsQuery->groupEnd(), $lock);
        $notificationsQuery = $db->table('notifications')->groupStart()->where('customer_guid', $customerGuid);
        $this->orIds($notificationsQuery, 'loan_guid', $loanGuids);

        return [
            'customer' => $customers[0], 'loan_applications' => $applications,
            'loans' => $loans, 'installments' => $installments, 'payments' => $payments,
            'notifications' => $this->rows($notificationsQuery->groupEnd(), $lock),
        ];
    }

    private function orIds(BaseBuilder $query, string $column, array $guids): void
    {
        if ($guids !== []) {
            $query->orWhereIn($column, $guids);
        }
    }

    private function rows(BaseBuilder $query, bool $lock): array
    {
        $sql = $query->getCompiledSelect();
        if ($lock && $this->connection()->DBDriver === 'MySQLi') {
            $sql .= ' FOR UPDATE';
        }
        $result = $this->connection()->query($sql);
        if ($result === false) {
            throw new RuntimeException('No se pudieron consultar los registros asociados.');
        }
        return $result->getResultArray();
    }

    private function executeDelete(BaseBuilder $query): void
    {
        if (! $query->delete()) {
            throw new RuntimeException('No se pudieron eliminar los registros asociados.');
        }
    }

    private function summarize(array $records): array
    {
        return [
            'customer_name' => trim($records['customer']['first_name'] . ' ' . $records['customer']['last_name']),
            'applications' => count($records['loan_applications']), 'loans' => count($records['loans']),
            'installments' => count($records['installments']), 'payments' => count($records['payments']),
            'notifications' => count($records['notifications']),
        ];
    }
}
