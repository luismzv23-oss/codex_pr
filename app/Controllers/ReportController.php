<?php

namespace App\Controllers;

class ReportController extends BaseController
{
    public function dashboard()
    {
        return view('reports/dashboard', [
            'title' => 'Metricas globales',
            'dashboard' => $this->repository->getDashboardData(),
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
