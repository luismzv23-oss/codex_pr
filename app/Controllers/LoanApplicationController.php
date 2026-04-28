<?php

namespace App\Controllers;

use App\Services\LoanWorkflowService;
use Throwable;

class LoanApplicationController extends BaseController
{
    public function index()
    {
        return view('loan_applications/index', [
            'title' => 'Solicitudes',
            'applications' => $this->repository->getApplications(),
            'customers' => $this->repository->getCustomers(),
        ]);
    }

    public function show($id)
    {
        $application = $this->repository->getApplication($id);

        if ($application === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Solicitud no encontrada');
        }

        return view('loan_applications/show', [
            'title' => 'Solicitud ' . $application['guid'],
            'application' => $application,
        ]);
    }

    public function create()
    {
        $selectedCustomerGuid = trim((string) $this->request->getGet('customer_guid'));
        $selectedCustomer = $selectedCustomerGuid !== ''
            ? $this->repository->getCustomer($selectedCustomerGuid)
            : null;

        return view('loan_applications/create', [
            'title' => 'Nueva solicitud',
            'customers' => $this->repository->getCustomers(),
            'systems' => $this->repository->getAmortizationSystems(true),
            'selectedCustomer' => $selectedCustomer,
        ]);
    }

    public function store()
    {
        $payload = $this->request->getPost([
            'customer_guid',
            'requested_amount',
            'currency',
            'interest_rate',
            'term_months',
            'amortization_type',
            'source_customer_guid',
        ]);
        $payload['status'] = 'draft';

        $rules = [
            'customer_guid' => 'required',
            'requested_amount' => 'required|decimal',
            'currency' => 'required|exact_length[3]',
            'interest_rate' => 'required|decimal',
            'term_months' => 'required|integer|greater_than[0]',
            'amortization_type' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $sourceCustomerGuid = trim((string) ($payload['source_customer_guid'] ?? ''));
        if ($sourceCustomerGuid !== '' && $sourceCustomerGuid !== (string) $payload['customer_guid']) {
            return redirect()->back()->withInput()->with('errors', ['La solicitud debe mantenerse asociada al cliente de origen.']);
        }

        $selectedSystem = $this->repository->findAmortizationSystemByCode((string) $payload['amortization_type']);
        if ($selectedSystem === null || ($selectedSystem['status'] ?? 'disabled') !== 'active') {
            return redirect()->back()->withInput()->with('errors', ['Debes seleccionar un sistema de amortizacion activo y valido.']);
        }

        $payload['interest_rate'] = round(((float) $payload['interest_rate']) / 100, 4);
        $payload['amortization_type'] = strtolower(trim((string) $payload['amortization_type']));
        unset($payload['source_customer_guid']);

        $saved = $this->repository->saveApplication($payload);

        return redirect()->to('/solicitudes')->with(
            'message',
            $saved ? 'Solicitud creada.' : 'Sin base operativa, se mantiene la data demo.'
        );
    }

    public function evaluate($id)
    {
        $updated = $this->repository->updateApplicationStatus($id, ['status' => 'evaluation']);

        return redirect()->to('/solicitudes/' . $id)->with(
            'message',
            $updated ? 'Solicitud marcada en evaluacion.' : 'No se pudo persistir. La vista sigue en modo demo.'
        );
    }

    public function approve($id)
    {
        $application = $this->repository->getApplication($id);
        $amount = $this->request->getPost('approved_amount') ?: ($application['requested_amount'] ?? null);

        try {
            $result = (new LoanWorkflowService())->approve($id, (float) $amount);

            return redirect()->to('/prestamos/' . $result['loan_guid'])->with(
                'message',
                $result['created']
                    ? 'Solicitud aprobada. Se genero el prestamo con sus cuotas.'
                    : 'La solicitud ya tenia un prestamo generado.'
            );
        } catch (Throwable $exception) {
            return redirect()->back()->withInput()->with('errors', [$exception->getMessage()]);
        }
    }

    public function reject($id)
    {
        $updated = $this->repository->updateApplicationStatus($id, [
            'status' => 'rejected',
            'rejection_reason' => $this->request->getPost('rejection_reason') ?: 'Revision interna',
        ]);

        return redirect()->to('/solicitudes/' . $id)->with(
            'message',
            $updated ? 'Solicitud rechazada.' : 'No se pudo persistir el rechazo. Sigue el modo demo.'
        );
    }

    public function disburse($id)
    {
        return redirect()->to('/solicitudes/' . $id)->with('message', 'El prestamo y sus cuotas se generan al aprobar la solicitud.');
    }

    public function delete($id)
    {
        $adminPassword = (string) $this->request->getPost('admin_password');

        try {
            (new LoanWorkflowService())->deleteRejectedApplication($id, $adminPassword);

            return redirect()->to('/solicitudes')->with('message', 'Solicitud rechazada eliminada.');
        } catch (Throwable $exception) {
            return redirect()->back()->withInput()->with('errors', [$exception->getMessage()]);
        }
    }
}
