<?php

namespace App\Models;

use App\Entities\Customer;

class CustomerModel extends BaseUuidModel
{
    protected $table            = 'customers';
    protected $primaryKey       = 'guid';
    protected $useAutoIncrement = false;
    protected $returnType       = Customer::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'first_name', 'last_name', 'dni', 'email', 'phone', 'dni_encrypted',
        'address', 'estimated_income', 'credit_limit', 'credit_limit_mode',
        'credit_status', 'kyc_status', 'kyc_verified_at', 'risk_score',
        'notes', 'created_by'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

}
