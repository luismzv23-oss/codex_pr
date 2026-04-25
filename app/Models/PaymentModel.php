<?php

namespace App\Models;

use App\Entities\Payment;

class PaymentModel extends BaseUuidModel
{
    protected $table            = 'payments';
    protected $primaryKey       = 'guid';
    protected $useAutoIncrement = false;
    protected $returnType       = Payment::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'loan_guid', 'installment_guid', 'customer_guid', 'amount', 'currency',
        'payment_method', 'reference_number', 'notes', 'received_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

}
