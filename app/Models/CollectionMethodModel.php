<?php

namespace App\Models;

class CollectionMethodModel extends BaseUuidModel
{
    protected $table            = 'collection_methods';
    protected $primaryKey       = 'guid';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'cuit',
        'cbu',
        'account_alias',
        'entity',
        'status',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
