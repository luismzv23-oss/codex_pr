<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Loan extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'disbursed_at', 'closed_at', 'next_due_date'];
    protected $casts   = [
        'principal_amount'    => 'float',
        'interest_rate'       => 'float',
        'term_months'         => 'integer',
        'total_interest'      => 'float',
        'total_payable'       => 'float',
        'outstanding_balance' => 'float',
        'status'              => 'string',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOverdue(): bool
    {
        // Simple check if default date passed
        if (!$this->next_due_date) {
            return false;
        }
        return strtotime($this->next_due_date) < time();
    }
}
