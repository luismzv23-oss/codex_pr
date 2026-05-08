<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGenerationDateToInstallments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('installments', [
            'generation_date' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'installment_number',
            ],
        ]);

        $rows = $this->db->table('installments')
            ->select('guid, due_date')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            if (empty($row['due_date']) || strtotime((string) $row['due_date']) === false) {
                continue;
            }

            $this->db->table('installments')
                ->where('guid', $row['guid'])
                ->update([
                    'generation_date' => date('Y-m-01', strtotime((string) $row['due_date'])),
                    'due_date' => date('Y-m-05', strtotime((string) $row['due_date'])),
                ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('installments', 'generation_date');
    }
}
