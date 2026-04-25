<?php

namespace App\Services;

class NotificationService
{
    public function sendPaymentReminder($customer, $installment): void
    {
        // TODO: Enqueue notification
    }

    public function sendOverdueNotice($customer, $loan): void
    {
        // TODO: Enqueue notification
    }

    public function sendApprovalNotice($customer, $application): void
    {
        // TODO: Enqueue notification
    }

    public function processQueue(): void
    {
        // TODO: Process pending notifications based on type (email/SMS/WSP)
    }
}
