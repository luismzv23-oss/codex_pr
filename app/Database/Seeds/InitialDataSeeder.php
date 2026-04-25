<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

class InitialDataSeeder extends Seeder
{
    public function run()
    {
        $this->seedAdminUser();
        $this->seedCurrencies();
        $this->seedDomainData();
    }

    private function seedAdminUser(): void
    {
        $users = model(UserModel::class);
        $existing = $users->where('username', 'admin')->first();

        if ($existing !== null) {
            return;
        }

        $user = new User([
            'username' => 'admin',
            'email' => 'admin@fintech.local',
            'password' => 'Admin12345',
            'active' => 1,
        ]);

        $users->save($user);
        $user = $users->where('username', 'admin')->first();
        $user->activate();
        $user->addGroup('admin');
    }

    private function seedCurrencies(): void
    {
        if ($this->db->table('currencies')->countAllResults() > 0) {
            return;
        }

        $rows = [
            [
                'guid' => 'cur-ars-0001-0001-0001-000000000001',
                'code' => 'ARS',
                'name' => 'Peso Argentino',
                'symbol' => '$',
                'exchange_rate' => 1.000000,
                'is_default' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'guid' => 'cur-usd-0001-0001-0001-000000000002',
                'code' => 'USD',
                'name' => 'Dolar Estadounidense',
                'symbol' => 'US$',
                'exchange_rate' => 0.001100,
                'is_default' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->insertRows('currencies', $rows);
    }

    private function seedDomainData(): void
    {
        if ($this->db->table('amortization_systems')->countAllResults() === 0) {
            $timestamp = date('Y-m-d H:i:s');
            $this->insertRows('amortization_systems', [
                [
                    'guid' => '00000000-0000-0000-0000-000000000011',
                    'code' => 'french',
                    'name' => 'Frances',
                    'description' => 'Cuota fija con interes decreciente.',
                    'status' => 'active',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                [
                    'guid' => '00000000-0000-0000-0000-000000000012',
                    'code' => 'german',
                    'name' => 'Aleman',
                    'description' => 'Capital fijo y cuota decreciente.',
                    'status' => 'active',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
                [
                    'guid' => '00000000-0000-0000-0000-000000000013',
                    'code' => 'american',
                    'name' => 'Americano',
                    'description' => 'Interes periodico y capital al vencimiento.',
                    'status' => 'active',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            ]);
        }

        if ($this->db->table('customers')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->insertRows('customers', [
            [
                'guid' => '11111111-1111-1111-1111-111111111111',
                'first_name' => 'Sofia',
                'last_name' => 'Ramirez',
                'dni' => '28111222',
                'email' => 'sofia.ramirez@example.test',
                'phone' => '+54 11 5555 0101',
                'dni_encrypted' => '',
                'address' => 'Palermo, Buenos Aires',
                'estimated_income' => 1850000.00,
                'credit_limit' => 650000.00,
                'credit_limit_mode' => 'manual',
                'credit_status' => 'active',
                'kyc_status' => 'verified',
                'kyc_verified_at' => $now,
                'risk_score' => 14.80,
                'notes' => 'Perfil con pagos consistentes.',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'guid' => '22222222-2222-2222-2222-222222222222',
                'first_name' => 'Martin',
                'last_name' => 'Lopez',
                'dni' => '30123456',
                'email' => 'martin.lopez@example.test',
                'phone' => '+54 11 5555 0102',
                'dni_encrypted' => '',
                'address' => 'Cordoba Capital',
                'estimated_income' => 960000.00,
                'credit_limit' => 336000.00,
                'credit_limit_mode' => 'automatic',
                'credit_status' => 'restricted',
                'kyc_status' => 'pending',
                'risk_score' => 38.20,
                'notes' => 'Solicitud en evaluacion manual.',
                'created_by' => 1,
                'kyc_verified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'guid' => '33333333-3333-3333-3333-333333333333',
                'first_name' => 'Valentina',
                'last_name' => 'Suarez',
                'dni' => '27555111',
                'email' => 'valentina.suarez@example.test',
                'phone' => '+54 351 555 0199',
                'dni_encrypted' => '',
                'address' => 'Rosario, Santa Fe',
                'estimated_income' => 1220000.00,
                'credit_limit' => 427000.00,
                'credit_limit_mode' => 'automatic',
                'credit_status' => 'active',
                'kyc_status' => 'verified',
                'kyc_verified_at' => $now,
                'risk_score' => 21.40,
                'notes' => 'Cliente apto para ticket medio.',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->insertRows('loan_applications', [
            [
                'guid' => 'aaaaaaaa-1111-1111-1111-111111111111',
                'customer_guid' => '11111111-1111-1111-1111-111111111111',
                'requested_amount' => 250000.00,
                'approved_amount' => 250000.00,
                'currency' => 'ARS',
                'interest_rate' => 0.0850,
                'term_months' => 12,
                'amortization_type' => 'french',
                'status' => 'approved',
                'evaluated_by' => null,
                'approved_by' => 1,
                'disbursed_at' => null,
                'rejection_reason' => null,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'guid' => 'bbbbbbbb-2222-2222-2222-222222222222',
                'customer_guid' => '22222222-2222-2222-2222-222222222222',
                'requested_amount' => 400000.00,
                'currency' => 'ARS',
                'interest_rate' => 0.0975,
                'term_months' => 18,
                'amortization_type' => 'german',
                'status' => 'evaluation',
                'approved_amount' => null,
                'evaluated_by' => null,
                'approved_by' => null,
                'disbursed_at' => null,
                'rejection_reason' => null,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'guid' => 'cccccccc-3333-3333-3333-333333333333',
                'customer_guid' => '33333333-3333-3333-3333-333333333333',
                'requested_amount' => 150000.00,
                'approved_amount' => 150000.00,
                'currency' => 'ARS',
                'interest_rate' => 0.0790,
                'term_months' => 10,
                'amortization_type' => 'american',
                'status' => 'disbursed',
                'evaluated_by' => null,
                'approved_by' => 1,
                'disbursed_at' => $now,
                'rejection_reason' => null,
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->insertRows('loans', [
            [
                'guid' => 'dddddddd-1111-1111-1111-111111111111',
                'application_guid' => 'cccccccc-3333-3333-3333-333333333333',
                'customer_guid' => '33333333-3333-3333-3333-333333333333',
                'currency' => 'ARS',
                'principal_amount' => 150000.00,
                'interest_rate' => 0.0790,
                'term_months' => 10,
                'amortization_type' => 'american',
                'total_interest' => 11850.00,
                'total_payable' => 161850.00,
                'outstanding_balance' => 129480.00,
                'status' => 'active',
                'next_due_date' => date('Y-m-d', strtotime('+3 days')),
                'disbursed_at' => $now,
                'closed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'guid' => 'eeeeeeee-2222-2222-2222-222222222222',
                'application_guid' => 'aaaaaaaa-1111-1111-1111-111111111111',
                'customer_guid' => '11111111-1111-1111-1111-111111111111',
                'currency' => 'ARS',
                'principal_amount' => 250000.00,
                'interest_rate' => 0.0850,
                'term_months' => 12,
                'amortization_type' => 'french',
                'total_interest' => 26500.00,
                'total_payable' => 276500.00,
                'outstanding_balance' => 184300.00,
                'status' => 'active',
                'next_due_date' => date('Y-m-d', strtotime('-2 days')),
                'disbursed_at' => $now,
                'closed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->insertRows('installments', [
            [
                'guid' => 'f1111111-1111-1111-1111-111111111111',
                'loan_guid' => 'dddddddd-1111-1111-1111-111111111111',
                'installment_number' => 1,
                'due_date' => date('Y-m-d', strtotime('-12 days')),
                'principal_amount' => 0.00,
                'interest_amount' => 1185.00,
                'total_amount' => 1185.00,
                'paid_amount' => 1185.00,
                'remaining_balance' => 150000.00,
                'status' => 'paid',
                'paid_at' => $now,
                'late_fee' => 0.00,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'guid' => 'f2222222-2222-2222-2222-222222222222',
                'loan_guid' => 'dddddddd-1111-1111-1111-111111111111',
                'installment_number' => 2,
                'due_date' => date('Y-m-d', strtotime('+3 days')),
                'principal_amount' => 0.00,
                'interest_amount' => 1185.00,
                'total_amount' => 1185.00,
                'paid_amount' => 0.00,
                'remaining_balance' => 150000.00,
                'status' => 'pending',
                'late_fee' => 0.00,
                'paid_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'guid' => 'f3333333-3333-3333-3333-333333333333',
                'loan_guid' => 'eeeeeeee-2222-2222-2222-222222222222',
                'installment_number' => 1,
                'due_date' => date('Y-m-d', strtotime('-16 days')),
                'principal_amount' => 19000.00,
                'interest_amount' => 1770.00,
                'total_amount' => 20770.00,
                'paid_amount' => 20770.00,
                'remaining_balance' => 231000.00,
                'status' => 'paid',
                'paid_at' => $now,
                'late_fee' => 0.00,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'guid' => 'f4444444-4444-4444-4444-444444444444',
                'loan_guid' => 'eeeeeeee-2222-2222-2222-222222222222',
                'installment_number' => 2,
                'due_date' => date('Y-m-d', strtotime('-2 days')),
                'principal_amount' => 19340.00,
                'interest_amount' => 1430.00,
                'total_amount' => 20770.00,
                'paid_amount' => 0.00,
                'remaining_balance' => 211660.00,
                'status' => 'overdue',
                'late_fee' => 420.00,
                'paid_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->insertRows('payments', [
            [
                'guid' => 'p1111111-1111-1111-1111-111111111111',
                'loan_guid' => 'dddddddd-1111-1111-1111-111111111111',
                'installment_guid' => 'f1111111-1111-1111-1111-111111111111',
                'customer_guid' => '33333333-3333-3333-3333-333333333333',
                'amount' => 1185.00,
                'currency' => 'ARS',
                'payment_method' => 'transfer',
                'reference_number' => 'TRX-1001',
                'notes' => 'Pago puntual.',
                'received_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'guid' => 'p2222222-2222-2222-2222-222222222222',
                'loan_guid' => 'eeeeeeee-2222-2222-2222-222222222222',
                'installment_guid' => 'f3333333-3333-3333-3333-333333333333',
                'customer_guid' => '11111111-1111-1111-1111-111111111111',
                'amount' => 20770.00,
                'currency' => 'ARS',
                'payment_method' => 'cash',
                'reference_number' => 'REC-2026-04-15',
                'notes' => 'Caja sucursal centro.',
                'received_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->insertRows('audit_logs', [
            [
                'guid' => 'a1111111-1111-1111-1111-111111111111',
                'user_guid' => 1,
                'action' => 'loan.approved',
                'entity_type' => 'loan_application',
                'entity_guid' => 'aaaaaaaa-1111-1111-1111-111111111111',
                'old_values' => null,
                'new_values' => json_encode(['status' => 'approved']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
                'created_at' => $now,
            ],
            [
                'guid' => 'a2222222-2222-2222-2222-222222222222',
                'user_guid' => 1,
                'action' => 'loan.disbursed',
                'entity_type' => 'loan',
                'entity_guid' => 'dddddddd-1111-1111-1111-111111111111',
                'old_values' => null,
                'new_values' => json_encode(['status' => 'active']),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
                'created_at' => $now,
            ],
        ]);

        $this->db->table('notifications')->insert([
            'guid' => 'n1111111-1111-1111-1111-111111111111',
            'customer_guid' => '11111111-1111-1111-1111-111111111111',
            'loan_guid' => 'eeeeeeee-2222-2222-2222-222222222222',
            'channel' => 'system',
            'type' => 'payment_reminder',
            'subject' => 'Recordatorio de cuota',
            'message' => 'Tu cuota vence en las proximas 48 horas.',
            'status' => 'pending',
            'scheduled_at' => $now,
            'created_at' => $now,
        ]);
    }

    private function insertRows(string $table, array $rows): void
    {
        $columns = [];
        foreach ($rows as $row) {
            $columns = array_unique(array_merge($columns, array_keys($row)));
        }

        $normalized = [];
        foreach ($rows as $row) {
            $normalized[] = array_replace(array_fill_keys($columns, null), $row);
        }

        $this->db->table($table)->insertBatch($normalized);
    }
}
