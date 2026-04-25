<?php

namespace App\Models;

class NotificationModel extends BaseUuidModel
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'guid';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'customer_guid', 'loan_guid', 'channel', 'type', 'subject',
        'message', 'status', 'scheduled_at', 'sent_at'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

}
