<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function index()
    {
        $user = auth()->user();
        if ($user !== null && ! $user->can('dashboard.view') && $user->can('payments.collect')) {
            return redirect()->to('/cuotas-pendientes');
        }

        return view('dashboard/index', [
            'title' => 'Dashboard',
            'dashboard' => $this->repository->getDashboardData(),
            'approvedMonth' => $this->repository->getApprovedLoansSummary(date('Y-m-01'), date('Y-m-d')),
        ]);
    }
}
