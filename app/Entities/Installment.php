<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Installment extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'due_date', 'paid_at'];
    protected $casts   = [
        'installment_number' => 'integer',
        'principal_amount'   => 'float',
        'interest_amount'    => 'float',
        'total_amount'       => 'float',
        'paid_amount'        => 'float',
        'remaining_balance'  => 'float',
        'late_fee'           => 'float',
        'status'             => 'string',
    ];

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid']);
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || (empty($this->paid_at) && strtotime($this->due_date) < time());
    }
}
