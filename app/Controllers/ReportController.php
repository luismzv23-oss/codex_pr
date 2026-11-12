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
            'snapshot' => $this->repository->getPortfolioSnapshot($to),
            'approved' => $this->repository->getApprovedLoansSummary($from, $to),
            'collection' => $this->repository->getCollectionSummary($from, $to),
            'presets' => AppRepository::approvedLoansPresets(),
            'tabs' => AppRepository::REPORT_TABS,
            'tab' => AppRepository::normalizeReportTab($this->request->getGet('tab')),
        ]);
    }

    public function overdue()
    {
        $data = $this->repository->getOverduePage(max(1, (int) $this->request->getGet('page')));

        if ($this->request->isAJAX()) {
            return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
                'html' => view('reports/_overdue_listing', $data), 'pagination' => $data['pagination'],
            ]);
        }

        return view('reports/overdue', ['title' => 'Analisis de mora'] + $data);
    }
}
