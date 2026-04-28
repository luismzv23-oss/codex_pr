<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToCollectionMethodsTable extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('collection_methods')) {
            return;
        }

        $fields = $this->db->getFieldData('collection_methods');
        $hasStatus = false;

        foreach ($fields as $field) {
            if (($field->name ?? null) === 'status') {
                $hasStatus = true;
                break;
            }
        }

        if (! $hasStatus) {
            $this->forge->addColumn('collection_methods', [
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['active', 'disabled'],
                    'default'    => 'active',
                    'after'      => 'entity',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('collection_methods')) {
            return;
        }

        $fields = $this->db->getFieldData('collection_methods');
        foreach ($fields as $field) {
            if (($field->name ?? null) === 'status') {
                $this->forge->dropColumn('collection_methods', 'status');
                break;
            }
        }
    }
}
