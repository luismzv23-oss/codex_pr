<?php

namespace App\Models;

use App\Entities\Loan;

class LoanModel extends BaseUuidModel
{
    protected $table            = 'loans';
    protected $primaryKey       = 'guid';
    protected $useAutoIncrement = false;
    protected $returnType       = Loan::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'application_guid', 'customer_guid', 'currency', 'principal_amount', 'interest_rate',
        'term_months', 'amortization_type', 'total_interest', 'total_payable',
        'outstanding_balance', 'status', 'next_due_date', 'disbursed_at', 'closed_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

}
