<?php

namespace App\Models;

class AuditLogModel extends BaseUuidModel
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'guid';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_guid', 'action', 'entity_type', 'entity_guid', 'old_values',
        'new_values', 'ip_address', 'user_agent'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

}
