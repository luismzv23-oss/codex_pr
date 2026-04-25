<?php

namespace App\Services;

class AuditService
{
    public function log(string $action, string $entityType, ?string $entityGuid, ?array $oldValues, ?array $newValues): void
    {
        // TODO: Insert into audit_logs table
    }

    public function getEntityHistory(string $entityType, string $entityGuid): array
    {
        // TODO: Query audit_logs
        return [];
    }

    public function getUserActivity(string $userGuid, string $dateRange): array
    {
        // TODO: Query audit_logs
        return [];
    }
}
