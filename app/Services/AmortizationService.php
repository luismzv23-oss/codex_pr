<?php

namespace App\Services;

class AmortizationService
{
    public function calculateFrench(float $amount, float $rate, int $terms): array
    {
        $periodRate = $this->normalizeMonthlyRate($rate);
        if ($terms <= 0) {
            return [];
        }

        if ($periodRate <= 0) {
            $principalPerInstallment = $amount / $terms;
            $balance = $amount;
            $schedule = [];
            for ($i = 1; $i <= $terms; $i++) {
                $principal = round(min($principalPerInstallment, $balance), 2);
                $balance = round(max(0, $balance - $principal), 2);
                $schedule[] = [
                    'installment_number' => $i,
                    'principal_amount' => $principal,
                    'interest_amount' => 0.0,
                    'total_amount' => $principal,
                    'remaining_balance' => $balance,
                ];
            }

            return $schedule;
        }

        $payment = $amount * ($periodRate / (1 - pow(1 + $periodRate, -$terms)));
        $balance = $amount;
        $schedule = [];

        for ($i = 1; $i <= $terms; $i++) {
            $interest = round($balance * $periodRate, 2);
            $principal = round($payment - $interest, 2);

            if ($i === $terms) {
                $principal = round($balance, 2);
            }

            $total = round($principal + $interest, 2);
            $balance = round(max(0, $balance - $principal), 2);

            $schedule[] = [
                'installment_number' => $i,
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'total_amount' => $total,
                'remaining_balance' => $balance,
            ];
        }

        return $schedule;
    }

    public function calculateGerman(float $amount, float $rate, int $terms): array
    {
        $periodRate = $this->normalizeMonthlyRate($rate);
        if ($terms <= 0) {
            return [];
        }

        $principal = round($amount / $terms, 2);
        $balance = $amount;
        $schedule = [];

        for ($i = 1; $i <= $terms; $i++) {
            $principalAmount = $i === $terms ? round($balance, 2) : $principal;
            $interest = round($balance * $periodRate, 2);
            $total = round($principalAmount + $interest, 2);
            $balance = round(max(0, $balance - $principalAmount), 2);

            $schedule[] = [
                'installment_number' => $i,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interest,
                'total_amount' => $total,
                'remaining_balance' => $balance,
            ];
        }

        return $schedule;
    }

    public function calculateAmerican(float $amount, float $rate, int $terms): array
    {
        $periodRate = $this->normalizeMonthlyRate($rate);
        if ($terms <= 0) {
            return [];
        }

        $interestOnly = round($amount * $periodRate, 2);
        $balance = $amount;
        $schedule = [];

        for ($i = 1; $i <= $terms; $i++) {
            $principal = $i === $terms ? round($balance, 2) : 0.0;
            $interest = $interestOnly;
            $total = round($principal + $interest, 2);
            $balance = round(max(0, $balance - $principal), 2);

            $schedule[] = [
                'installment_number' => $i,
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'total_amount' => $total,
                'remaining_balance' => $balance,
            ];
        }

        return $schedule;
    }

    public function generateSchedule(array $loan): array
    {
        $amount = (float) ($loan['principal_amount'] ?? 0);
        $rate = (float) ($loan['interest_rate'] ?? 0);
        $terms = (int) ($loan['term_months'] ?? 0);

        $schedule = match ($loan['amortization_type'] ?? 'french') {
            'german' => $this->calculateGerman($amount, $rate, $terms),
            'american' => $this->calculateAmerican($amount, $rate, $terms),
            default => $this->calculateFrench($amount, $rate, $terms),
        };

        $firstDueDate = $this->resolveFirstDueDate($loan['disbursed_at'] ?? null);

        foreach ($schedule as $index => &$item) {
            $item['due_date'] = date('Y-m-d', strtotime('+' . $index . ' month', strtotime($firstDueDate)));
        }
        unset($item);

        return $schedule;
    }

    public function recalculate(array $loan): array
    {
        return $this->generateSchedule($loan);
    }

    private function normalizeMonthlyRate(float $rate): float
    {
        return $rate > 1 ? ($rate / 100) : $rate;
    }

    private function resolveFirstDueDate($disbursedAt): string
    {
        $timestamp = ! empty($disbursedAt) ? strtotime((string) $disbursedAt) : strtotime('today');
        if ($timestamp === false) {
            $timestamp = strtotime('today');
        }

        $day = (int) date('j', $timestamp);
        $baseMonth = new \DateTimeImmutable(date('Y-m-01', $timestamp));
        $monthOffset = $day <= 19 ? '+1 month' : '+2 months';

        return $baseMonth->modify($monthOffset)->format('Y-m-d');
    }
}
