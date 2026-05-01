<?php

namespace App\Controllers;

use CodeIgniter\Shield\Models\UserModel;

class ProfileController extends BaseController
{
    public function index()
    {
        return view('profile/index', [
            'title' => 'Mi perfil',
            'user' => auth()->user(),
        ]);
    }

    public function updatePassword()
    {
        $rules = [
            'current_password' => 'required',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = auth()->user();
        if ($user === null) {
            return redirect()->to('/login');
        }

        $currentPassword = (string) $this->request->getPost('current_password');
        if (! password_verify($currentPassword, (string) $user->getPasswordHash())) {
            return redirect()->back()->with('errors', ['La contrasena actual no es correcta.']);
        }

        $model = model(UserModel::class);
        $user->password = (string) $this->request->getPost('password');

        if (! $model->save($user)) {
            return redirect()->back()->with('errors', ['No se pudo actualizar la contrasena.']);
        }

        return redirect()->to('/perfil')->with('message', 'Contrasena actualizada.');
    }
}
