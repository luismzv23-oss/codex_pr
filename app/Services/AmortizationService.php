<?php

namespace App\Services;

class AmortizationService
{
    public function calculateFrench(float $amount, float $rate, int $terms): array
    {
        $periodRate = $this->ratePerPeriod($rate);
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
        $periodRate = $this->ratePerPeriod($rate);
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
        $periodRate = $this->ratePerPeriod($rate);
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

        $anchorDate = ! empty($loan['disbursed_at']) ? strtotime((string) $loan['disbursed_at']) : strtotime('today');

        foreach ($schedule as $index => &$item) {
            $item['due_date'] = date('Y-m-d', strtotime('+' . ($index + 1) . ' month', $anchorDate));
        }
        unset($item);

        return $schedule;
    }

    public function recalculate(array $loan): array
    {
        return $this->generateSchedule($loan);
    }

    private function ratePerPeriod(float $rate): float
    {
        return $rate > 1 ? ($rate / 100 / 12) : ($rate / 12);
    }
}
