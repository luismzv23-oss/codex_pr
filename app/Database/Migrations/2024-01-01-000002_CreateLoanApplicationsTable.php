<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoanApplicationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'guid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'customer_guid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'requested_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'approved_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'null'       => true,
            ],
            'currency' => [
                'type'       => 'CHAR',
                'constraint' => 3,
                'default'    => 'ARS',
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
                'default'    => 'french',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'evaluation', 'approved', 'rejected', 'disbursed', 'closed', 'defaulted'],
                'default'    => 'draft',
            ],
            'evaluated_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'approved_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'disbursed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'rejection_reason' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
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
        // $this->forge->addForeignKey('customer_guid', 'customers', 'guid', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('loan_applications');
    }

    public function down()
    {
        $this->forge->dropTable('loan_applications');
    }
}
