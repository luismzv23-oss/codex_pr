<?php

namespace App\Models;

use App\Entities\LoanApplication;

class LoanApplicationModel extends BaseUuidModel
{
    protected $table            = 'loan_applications';
    protected $primaryKey       = 'guid';
    protected $useAutoIncrement = false;
    protected $returnType       = LoanApplication::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'customer_guid', 'requested_amount', 'approved_amount', 'currency', 'interest_rate',
        'term_months', 'amortization_type', 'status', 'evaluated_by',
        'approved_by', 'disbursed_at', 'rejection_reason', 'created_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

}
