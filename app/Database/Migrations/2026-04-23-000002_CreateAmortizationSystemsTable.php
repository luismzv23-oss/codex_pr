<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAmortizationSystemsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'guid' => [
                'type' => 'CHAR',
                'constraint' => 36,
            ],
            'code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'disabled'],
                'default' => 'active',
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
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('amortization_systems');
    }

    public function down()
    {
        $this->forge->dropTable('amortization_systems', true);
    }
}
