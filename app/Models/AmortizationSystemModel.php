<?php

namespace App\Models;

class AmortizationSystemModel extends BaseUuidModel
{
    protected $table = 'amortization_systems';
    protected $primaryKey = 'guid';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'code',
        'name',
        'description',
        'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
