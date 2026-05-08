<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $users = auth()->getProvider();

        // --- superadmin ---
        $this->ensureUser($users, 'superadmin', 'superadmin@fintech.local', 'superadmin', $this->initialSuperAdminPassword());

        // --- admin ---
        $this->ensureUser($users, 'admin', 'admin@fintech.local', 'admin', $this->initialAdminPassword());
    }

    private function ensureUser($users, string $username, string $email, string $group, string $password): void
    {
        $existing = $users->where('username', $username)->first();

        if ($existing !== null) {
            $existing->syncGroups($group);

            return;
        }

        $user = new User([
            'username' => $username,
            'email'    => $email,
            'password' => $password,
            'active'   => 1,
        ]);

        $users->save($user);
        $user = $users->findById($users->getInsertID());

        $user->addGroup($group);
    }

    private function initialSuperAdminPassword(): string
    {
        $password = (string) (getenv('SEED_SUPERADMIN_PASSWORD') ?: '');

        if ($password === '' && ENVIRONMENT !== 'production') {
            $password = 'SuperAdmin!2026';
        }

        if (strlen($password) < 12) {
            throw new \RuntimeException('Define SEED_SUPERADMIN_PASSWORD con al menos 12 caracteres antes de ejecutar el seeder.');
        }

        return $password;
    }

    private function initialAdminPassword(): string
    {
        $password = (string) (getenv('SEED_ADMIN_PASSWORD') ?: getenv('ADMIN_PASSWORD') ?: '');

        if ($password === '' && ENVIRONMENT !== 'production') {
            $password = 'ChangeMeNow!2026';
        }

        if (strlen($password) < 12) {
            throw new \RuntimeException('Define SEED_ADMIN_PASSWORD con al menos 12 caracteres antes de ejecutar el seeder de administrador.');
        }

        return $password;
    }
}
