<?php

namespace App\Models;

use App\Entities\Installment;

class InstallmentModel extends BaseUuidModel
{
    protected $table            = 'installments';
    protected $primaryKey       = 'guid';
    protected $useAutoIncrement = false;
    protected $returnType       = Installment::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'loan_guid', 'installment_number', 'due_date', 'principal_amount',
        'interest_amount', 'total_amount', 'paid_amount', 'remaining_balance',
        'status', 'paid_at', 'late_fee'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

}
