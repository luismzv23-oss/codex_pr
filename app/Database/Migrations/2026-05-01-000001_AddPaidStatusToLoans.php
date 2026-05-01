<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaidStatusToLoans extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE loans MODIFY status ENUM('active','paid','paid_off','defaulted','restructured') NOT NULL DEFAULT 'active'");
        $this->db->table('loans')->where('status', 'paid_off')->update(['status' => 'paid']);
    }

    public function down()
    {
        $this->db->table('loans')->where('status', 'paid')->update(['status' => 'paid_off']);
        $this->db->query("ALTER TABLE loans MODIFY status ENUM('active','paid_off','defaulted','restructured') NOT NULL DEFAULT 'active'");
    }
}
