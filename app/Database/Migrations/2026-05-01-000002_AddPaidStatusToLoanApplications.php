<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaidStatusToLoanApplications extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE loan_applications MODIFY status ENUM('draft','evaluation','approved','rejected','disbursed','closed','defaulted','paid') NOT NULL DEFAULT 'draft'");
        $this->db->query("
            UPDATE loan_applications application
            INNER JOIN loans loan ON loan.application_guid = application.guid
            SET application.status = 'paid'
            WHERE loan.status = 'paid' OR loan.outstanding_balance <= 0
        ");
    }

    public function down()
    {
        $this->db->table('loan_applications')->where('status', 'paid')->update(['status' => 'closed']);
        $this->db->query("ALTER TABLE loan_applications MODIFY status ENUM('draft','evaluation','approved','rejected','disbursed','closed','defaulted') NOT NULL DEFAULT 'draft'");
    }
}
