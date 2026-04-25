<?php

namespace App\Controllers;

class CustomerController extends BaseController
{
    public function index()
    {
        return view('customers/index', [
            'title' => 'Clientes',
            'customers' => $this->repository->getCustomers(),
        ]);
    }

    public function show($id)
    {
        $customer = $this->repository->getCustomer($id);

        if ($customer === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Cliente no encontrado');
        }

        $applications = array_values(array_filter(
            $this->repository->getApplications(),
            static fn(array $application): bool => $application['customer_guid'] === $id
        ));
        $loans = array_values(array_filter(
            $this->repository->getLoans(),
            static fn(array $loan): bool => $loan['customer_guid'] === $id
        ));

        return view('customers/show', [
            'title' => $customer['full_name'],
            'customer' => $customer,
            'applications' => $applications,
            'loans' => $loans,
        ]);
    }

    public function create()
    {
        return view('customers/create', ['title' => 'Nuevo cliente']);
    }

    public function store()
    {
        $payload = $this->buildCustomerPayload();
        $rules = $this->customerRules();

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $saved = $this->repository->saveCustomer($payload);

        return redirect()->to('/clientes')->with(
            'message',
            $saved ? 'Cliente guardado correctamente.' : 'Sin conexion a la base. Se mantuvo el modo demo.'
        );
    }

    public function edit($id)
    {
        $customer = $this->repository->getCustomer($id);

        if ($customer === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Cliente no encontrado');
        }

        return view('customers/edit', [
            'title' => 'Editar cliente',
            'customer' => $customer,
        ]);
    }

    public function update($id)
    {
        $payload = $this->buildCustomerPayload();
        $payload['guid'] = $id;
        $rules = $this->customerRules($id);

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $saved = $this->repository->saveCustomer($payload);

        return redirect()->to('/clientes/' . $id)->with(
            'message',
            $saved ? 'Cliente actualizado.' : 'No se pudo persistir el cambio. El modo demo sigue activo.'
        );
    }

    public function delete($id)
    {
        return redirect()->to('/clientes')->with('message', 'La eliminacion queda pendiente para la siguiente iteracion.');
    }

    private function buildCustomerPayload(): array
    {
        $payload = $this->request->getPost([
            'first_name',
            'last_name',
            'dni',
            'email',
            'phone',
            'address',
            'estimated_income',
            'credit_limit',
            'credit_limit_mode',
            'credit_status',
            'kyc_status',
            'risk_score',
            'notes',
        ]);

        $payload['dni'] = trim((string) ($payload['dni'] ?? ''));
        $payload['estimated_income'] = $payload['estimated_income'] !== '' ? $payload['estimated_income'] : null;
        $payload['credit_limit'] = $payload['credit_limit'] !== '' ? $payload['credit_limit'] : 0;

        if (($payload['credit_limit_mode'] ?? 'manual') === 'automatic') {
            $income = (float) ($payload['estimated_income'] ?? 0);
            $payload['credit_limit'] = round($income * 0.35, 2);
        }

        return $payload;
    }

    private function customerRules(?string $guid = null): array
    {
        $emailRule = 'required|valid_email';
        $dniRule = 'required|min_length[7]|max_length[20]';

        if ($guid === null) {
            $emailRule .= '|is_unique[customers.email]';
            $dniRule .= '|is_unique[customers.dni]';
        } else {
            $emailRule .= '|is_unique[customers.email,guid,' . $guid . ']';
            $dniRule .= '|is_unique[customers.dni,guid,' . $guid . ']';
        }

        return [
            'first_name' => 'required|min_length[2]',
            'last_name' => 'required|min_length[2]',
            'dni' => $dniRule,
            'email' => $emailRule,
            'phone' => 'required|min_length[6]',
            'estimated_income' => 'permit_empty|decimal',
            'credit_limit' => 'permit_empty|decimal',
            'credit_limit_mode' => 'required|in_list[manual,automatic]',
            'credit_status' => 'required|in_list[active,restricted]',
            'kyc_status' => 'required|in_list[pending,verified,rejected]',
            'risk_score' => 'permit_empty|decimal',
        ];
    }
}
