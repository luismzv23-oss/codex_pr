<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCurrenciesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'guid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'code' => [
                'type'       => 'CHAR',
                'constraint' => 3,
                'unique'     => true, // E.g., USD, ARS
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'symbol' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'exchange_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,6',
                'default'    => 1.000000,
            ],
            'is_default' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('guid', true);
        $this->forge->createTable('currencies');
    }

    public function down()
    {
        $this->forge->dropTable('currencies');
    }
}
