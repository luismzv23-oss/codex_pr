<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContextColumnToSettings extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('context', 'settings')) {
            $this->forge->addColumn('settings', [
                'context' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'type',
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('settings', 'context');
    }
}
