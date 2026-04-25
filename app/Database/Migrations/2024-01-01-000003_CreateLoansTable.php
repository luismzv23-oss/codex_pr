<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoansTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'guid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'application_guid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'customer_guid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'currency' => [
                'type'       => 'CHAR',
                'constraint' => 3,
                'default'    => 'ARS',
            ],
            'principal_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'interest_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,4',
            ],
            'term_months' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'amortization_type' => [
                'type'       => 'ENUM',
                'constraint' => ['french', 'german', 'american'],
            ],
            'total_interest' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'total_payable' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'outstanding_balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'paid_off', 'defaulted', 'restructured'],
                'default'    => 'active',
            ],
            'next_due_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'disbursed_at' => [
                'type' => 'DATETIME',
            ],
            'closed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('guid', true);
        $this->forge->createTable('loans');
    }

    public function down()
    {
        $this->forge->dropTable('loans');
    }
}
