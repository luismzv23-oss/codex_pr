<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCommercialFieldsToCustomers extends Migration
{
    public function up()
    {
        $fields = [
            'dni' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'last_name',
            ],
            'estimated_income' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'null' => true,
                'after' => 'address',
            ],
            'credit_limit' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0,
                'after' => 'estimated_income',
            ],
            'credit_limit_mode' => [
                'type' => 'ENUM',
                'constraint' => ['manual', 'automatic'],
                'default' => 'manual',
                'after' => 'credit_limit',
            ],
            'credit_status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'restricted'],
                'default' => 'active',
                'after' => 'credit_limit_mode',
            ],
        ];

        $this->forge->addColumn('customers', $fields);
        $this->db->table('customers')->where('email', 'sofia.ramirez@example.test')->update([
            'dni' => '28111222',
            'estimated_income' => 1850000.00,
            'credit_limit' => 650000.00,
            'credit_limit_mode' => 'manual',
            'credit_status' => 'active',
        ]);
        $this->db->table('customers')->where('email', 'martin.lopez@example.test')->update([
            'dni' => '30123456',
            'estimated_income' => 960000.00,
            'credit_limit' => 336000.00,
            'credit_limit_mode' => 'automatic',
            'credit_status' => 'restricted',
        ]);
        $this->db->table('customers')->where('email', 'valentina.suarez@example.test')->update([
            'dni' => '27555111',
            'estimated_income' => 1220000.00,
            'credit_limit' => 427000.00,
            'credit_limit_mode' => 'automatic',
            'credit_status' => 'active',
        ]);
        $this->db->query('CREATE UNIQUE INDEX customers_dni_unique ON customers (dni)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX customers_dni_unique ON customers');
        $this->forge->dropColumn('customers', [
            'dni',
            'estimated_income',
            'credit_limit',
            'credit_limit_mode',
            'credit_status',
        ]);
    }
}
