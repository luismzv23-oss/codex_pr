<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterAmortizationTypeColumns extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE loan_applications MODIFY amortization_type VARCHAR(50) NOT NULL DEFAULT 'french'");
        $this->db->query("ALTER TABLE loans MODIFY amortization_type VARCHAR(50) NOT NULL");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE loan_applications MODIFY amortization_type ENUM('french','german','american') NOT NULL DEFAULT 'french'");
        $this->db->query("ALTER TABLE loans MODIFY amortization_type ENUM('french','german','american') NOT NULL");
    }
}
