<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

class RolesSeeder extends Seeder
{
    public function run()
    {
        // Shield automatically creates the tables, but we can seed the initial Superadmin here
        // if needed later. For the scaffold, we just leave the structure ready.
        
        $users = auth()->getProvider();

        $user = new User([
            'username' => 'superadmin',
            'email'    => 'admin@fintech.local',
            'password' => '12345678', // Contraseña fácil para pruebas iniciales
            'active'   => 1,
        ]);

        $users->save($user);
        $user = $users->findById($users->getInsertID());

        // Assign the role
        $user->addGroup('superadmin');
    }
}
