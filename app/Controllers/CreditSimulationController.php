<?php

namespace App\Controllers;

use App\Services\AmortizationService;

class CreditSimulationController extends BaseController
{
    public function index()
    {
        $simulation = null;
        $input = [
            'amount' => '',
            'interest_rate' => '',
            'terms' => '',
            'currency' => 'ARS',
            'amortization_type' => 'french',
        ];
        $systems = $this->repository->getAmortizationSystems(true);

        if ($this->request->getMethod() === 'post') {
            $input = [
                'amount' => (string) $this->request->getPost('amount'),
                'interest_rate' => (string) $this->request->getPost('interest_rate'),
                'terms' => (string) $this->request->getPost('terms'),
                'currency' => strtoupper(trim((string) $this->request->getPost('currency')) ?: 'ARS'),
                'amortization_type' => strtolower(trim((string) $this->request->getPost('amortization_type')) ?: 'french'),
            ];

            $rules = [
                'amount' => 'required|numeric|greater_than[0]',
                'interest_rate' => 'required|numeric|greater_than_equal_to[0]',
                'terms' => 'required|integer|greater_than[0]',
                'currency' => 'required|min_length[2]|max_length[5]',
                'amortization_type' => 'required',
            ];

            if (! $this->validate($rules)) {
                return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
            }

            $availableSystemCodes = array_column($systems, 'code');
            if (! in_array($input['amortization_type'], $availableSystemCodes, true)) {
                return redirect()->back()->withInput()->with('errors', ['Sistema de amortizacion no disponible.']);
            }

            $schedule = (new AmortizationService())->generateSchedule([
                'principal_amount' => (float) $input['amount'],
                'interest_rate' => (float) $input['interest_rate'],
                'term_months' => (int) $input['terms'],
                'amortization_type' => $input['amortization_type'],
            ]);

            $simulation = [
                'schedule' => $schedule,
                'total_interest' => round(array_sum(array_column($schedule, 'interest_amount')), 2),
                'total_payable' => round(array_sum(array_column($schedule, 'total_amount')), 2),
                'currency' => $input['currency'],
                'amortization_type' => $input['amortization_type'],
                'amortization_label' => amortization_system_label($input['amortization_type']),
            ];
        }

        return view('credit_simulation/index', [
            'title' => 'Simulacion del credito',
            'input' => $input,
            'systems' => $systems,
            'simulation' => $simulation,
        ]);
    }
}
