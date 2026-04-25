<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTwoFactorToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'two_factor_auth_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
            ],
        ];
        
        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'two_factor_auth_enabled');
    }
}
