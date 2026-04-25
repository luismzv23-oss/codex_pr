<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class LoanApplication extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'disbursed_at'];
    protected $casts   = [
        'requested_amount' => 'float',
        'approved_amount'  => 'float',
        'interest_rate'    => 'float',
        'term_months'      => 'integer',
        'status'           => 'string',
    ];

    public function isApprovable(): bool
    {
        return $this->status === 'evaluation';
    }
}
