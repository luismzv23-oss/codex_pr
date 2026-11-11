<?php

namespace App\Controllers;

use App\Support\AppRepository;

class ReportController extends BaseController
{
    public function dashboard()
    {
        [$from, $to] = AppRepository::normalizeDateRange(
            $this->request->getGet('desde'),
            $this->request->getGet('hasta')
        );

        return view('reports/dashboard', [
            'title' => 'Metricas globales',
            'dashboard' => $this->repository->getDashboardData(),
            'approved' => $this->repository->getApprovedLoansSummary($from, $to),
            'presets' => AppRepository::approvedLoansPresets(),
        ]);
    }

    public function overdue()
    {
        $overdue = array_values(array_filter(
            $this->repository->getInstallments(),
            static fn(array $item): bool => $item['status'] === 'overdue'
        ));

        return view('reports/overdue', [
            'title' => 'Analisis de mora',
            'installments' => $overdue,
        ]);
    }
}
