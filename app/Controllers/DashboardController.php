<?php

namespace App\Controllers;

use App\Support\AppRepository;

class DashboardController extends BaseController
{
    public function index()
    {
        $user = auth()->user();
        if ($user !== null && ! $user->can('dashboard.view') && $user->can('payments.collect')) {
            return redirect()->to('/cuotas-pendientes');
        }

        [$from, $to] = AppRepository::normalizeDateRange(
            $this->request->getGet('desde'),
            $this->request->getGet('hasta')
        );

        return view('dashboard/index', [
            'title' => 'Dashboard',
            'dashboard' => $this->repository->getDashboardData(),
            'approvedMonth' => $this->repository->getApprovedLoansSummary($from, $to),
            'collection' => $this->repository->getCollectionSummary($from, $to),
            'presets' => AppRepository::approvedLoansPresets(),
        ]);
    }
}
