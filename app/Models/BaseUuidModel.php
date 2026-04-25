<?php

namespace App\Models;

use CodeIgniter\Model;
use Ramsey\Uuid\Uuid;

abstract class BaseUuidModel extends Model
{
    protected $beforeInsert = ['ensureGuid'];
    protected $beforeInsertBatch = ['ensureBatchGuids'];

    protected function ensureGuid(array $data): array
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            return $data;
        }

        if (empty($data['data'][$this->primaryKey])) {
            $data['data'][$this->primaryKey] = Uuid::uuid4()->toString();
        }

        return $data;
    }

    protected function ensureBatchGuids(array $data): array
    {
        if (! isset($data['data']) || ! is_array($data['data'])) {
            return $data;
        }

        foreach ($data['data'] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (empty($row[$this->primaryKey])) {
                $data['data'][$index][$this->primaryKey] = Uuid::uuid4()->toString();
            }
        }

        return $data;
    }
}
