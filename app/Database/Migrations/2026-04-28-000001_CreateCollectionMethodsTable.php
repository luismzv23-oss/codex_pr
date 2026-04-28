<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCollectionMethodsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'guid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'cuit' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'cbu' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
            ],
            'account_alias' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'entity' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'disabled'],
                'default'    => 'active',
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
        $this->forge->createTable('collection_methods');
    }

    public function down()
    {
        $this->forge->dropTable('collection_methods', true);
    }
}
