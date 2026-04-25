<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInstallmentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'guid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'loan_guid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'installment_number' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'due_date' => [
                'type' => 'DATE',
            ],
            'principal_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'interest_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'total_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'paid_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => 0,
            ],
            'remaining_balance' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'paid', 'partial', 'overdue'],
                'default'    => 'pending',
            ],
            'paid_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'late_fee' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0,
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
        $this->forge->createTable('installments');
    }

    public function down()
    {
        $this->forge->dropTable('installments');
    }
}
