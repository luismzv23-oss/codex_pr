<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFirstCollectionDate extends Migration
{
    public function up()
    {
        foreach (['loan_applications', 'loans'] as $table) {
            if (!$this->db->fieldExists('first_collection_date', $table)) {
                $this->forge->addColumn($table, ['first_collection_date' => ['type' => 'DATE', 'null' => true, 'default' => null]]);
            }
        }
    }

    public function down()
    {
        foreach (['loans', 'loan_applications'] as $table) {
            if ($this->db->fieldExists('first_collection_date', $table)) $this->forge->dropColumn($table, 'first_collection_date');
        }
    }
}
