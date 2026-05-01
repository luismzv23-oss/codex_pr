<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

class RolesSeeder extends Seeder
{
    public function run()
    {
        // Shield automatically creates the tables, but we can seed the initial administrator here
        // if needed later. For the scaffold, we just leave the structure ready.
        
        $users = auth()->getProvider();
        $existing = $users->where('username', 'admin')->first();

        if ($existing !== null) {
            $existing->syncGroups('admin');

            return;
        }

        $user = new User([
            'username' => 'admin',
            'email'    => 'admin@fintech.local',
            'password' => 'Admin12345', // Contraseña fácil para pruebas iniciales
            'active'   => 1,
        ]);

        $users->save($user);
        $user = $users->findById($users->getInsertID());

        // Assign the role
        $user->addGroup('admin');
    }
}
