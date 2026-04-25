<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Customer extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'kyc_verified_at'];
    protected $casts   = [
        'kyc_status' => 'string',
        'risk_score' => 'float',
        'estimated_income' => '?float',
        'credit_limit' => 'float',
        'credit_limit_mode' => 'string',
        'credit_status' => 'string',
    ];

    public function isVerified(): bool
    {
        return $this->kyc_status === 'verified';
    }
}
