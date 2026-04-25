<?php

namespace App\Controllers;

class SettingsController extends BaseController
{
    public function users()
    {
        return view('settings/users/index', [
            'title' => 'Usuarios',
            'users' => $this->repository->getUsers(),
        ]);
    }

    public function createUser()
    {
        return view('settings/users/create', [
            'title' => 'Nuevo usuario',
        ]);
    }

    public function storeUser()
    {
        $payload = $this->request->getPost(['username', 'email', 'password', 'active']);
        $rules = [
            'username' => 'required|min_length[3]',
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $saved = $this->repository->saveUser($payload);

        return redirect()->to('/configuracion/usuarios')->with('message', $saved ? 'Usuario creado.' : 'No se pudo persistir el usuario. La vista sigue como estructura.');
    }

    public function editUser($id)
    {
        $user = $this->repository->getUser((int) $id);
        if ($user === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Usuario no encontrado');
        }

        return view('settings/users/edit', [
            'title' => 'Editar usuario',
            'user' => $user,
        ]);
    }

    public function updateUser($id)
    {
        $payload = $this->request->getPost(['username', 'email', 'password', 'active']);
        $payload['id'] = (int) $id;
        $rules = [
            'username' => 'required|min_length[3]',
            'email' => 'required|valid_email',
            'password' => 'permit_empty|min_length[8]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $saved = $this->repository->saveUser($payload);

        return redirect()->to('/configuracion/usuarios')->with('message', $saved ? 'Usuario actualizado.' : 'No se pudo persistir el cambio.');
    }

    public function toggleUser($id)
    {
        $saved = $this->repository->toggleUserStatus((int) $id);

        return redirect()->to('/configuracion/usuarios')->with('message', $saved ? 'Estado del usuario actualizado.' : 'No se pudo actualizar el usuario.');
    }

    public function deleteUser($id)
    {
        $saved = $this->repository->deleteUser((int) $id);

        return redirect()->to('/configuracion/usuarios')->with('message', $saved ? 'Usuario eliminado.' : 'No se pudo eliminar el usuario.');
    }

    public function amortizationSystems()
    {
        return view('settings/amortization/index', [
            'title' => 'Sistemas de amortizacion',
            'systems' => $this->repository->getAmortizationSystems(),
        ]);
    }

    public function createAmortizationSystem()
    {
        return view('settings/amortization/create', [
            'title' => 'Nuevo sistema',
        ]);
    }

    public function storeAmortizationSystem()
    {
        $payload = $this->request->getPost(['code', 'name', 'description', 'status']);
        $payload = $this->normalizeAmortizationPayload($payload);
        $rules = [
            'code' => 'required|alpha_dash|min_length[2]|max_length[50]',
            'name' => 'required|min_length[3]|max_length[100]',
            'status' => 'required|in_list[active,disabled]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $existing = $this->repository->findAmortizationSystemByCode((string) $payload['code'], true);
        if ($existing !== null && empty($existing['deleted_at'])) {
            return redirect()->back()->withInput()->with('errors', ['El codigo ingresado ya existe en otro sistema.']);
        }

        $saved = $this->repository->createAmortizationSystem($payload);

        if (! $saved) {
            return redirect()->back()->withInput()->with('errors', ['No se pudo persistir el sistema.']);
        }

        $message = $existing !== null ? 'Sistema restaurado y actualizado.' : 'Sistema guardado.';

        return redirect()->to('/configuracion/amortizacion')->with('message', $message);
    }

    public function editAmortizationSystem($id)
    {
        $system = $this->repository->getAmortizationSystem($id);
        if ($system === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Sistema no encontrado');
        }

        return view('settings/amortization/edit', [
            'title' => 'Editar sistema',
            'system' => $system,
        ]);
    }

    public function updateAmortizationSystem($id)
    {
        $payload = $this->request->getPost(['code', 'name', 'description', 'status']);
        $payload = $this->normalizeAmortizationPayload($payload);
        $rules = [
            'code' => 'required|alpha_dash|min_length[2]|max_length[50]',
            'name' => 'required|min_length[3]|max_length[100]',
            'status' => 'required|in_list[active,disabled]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $existing = $this->repository->getAmortizationSystem($id);
        if ($existing === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Sistema no encontrado');
        }

        $duplicate = $this->repository->findAmortizationSystemByCode((string) $payload['code'], true);
        if ($duplicate !== null && $duplicate['guid'] !== $existing['guid']) {
            return redirect()->back()->withInput()->with('errors', ['El codigo ingresado ya existe en otro sistema.']);
        }

        $saved = $this->repository->updateAmortizationSystem($id, $payload);

        return $saved
            ? redirect()->to('/configuracion/amortizacion')->with('message', 'Sistema actualizado.')
            : redirect()->back()->withInput()->with('errors', ['No se pudo persistir el sistema.']);
    }

    public function toggleAmortizationSystem($id)
    {
        $system = $this->repository->getAmortizationSystem($id);
        if ($system === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Sistema no encontrado');
        }

        if (($system['status'] ?? 'active') === 'active' && $this->repository->countActiveAmortizationSystems() <= 1) {
            return redirect()->to('/configuracion/amortizacion')->with('errors', ['Debe quedar al menos un sistema de amortizacion activo.']);
        }

        $saved = $this->repository->toggleAmortizationSystem($id);

        return redirect()->to('/configuracion/amortizacion')->with('message', $saved ? 'Estado del sistema actualizado.' : 'No se pudo actualizar el sistema.');
    }

    public function deleteAmortizationSystem($id)
    {
        $system = $this->repository->getAmortizationSystem($id);
        if ($system === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Sistema no encontrado');
        }

        if ($this->repository->amortizationSystemInUse((string) $system['code'])) {
            return redirect()->to('/configuracion/amortizacion')->with('errors', ['No se puede eliminar un sistema que ya esta asociado a solicitudes o prestamos.']);
        }

        $saved = $this->repository->deleteAmortizationSystem($id);

        return redirect()->to('/configuracion/amortizacion')->with('message', $saved ? 'Sistema eliminado.' : 'No se pudo eliminar el sistema.');
    }

    private function normalizeAmortizationPayload(array $payload): array
    {
        $payload['code'] = strtolower(trim((string) ($payload['code'] ?? '')));
        $payload['name'] = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $payload['description'] = $description === '' ? null : $description;
        $payload['status'] = (string) ($payload['status'] ?? 'active');

        return $payload;
    }
}
