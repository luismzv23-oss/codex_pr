<?php

namespace App\Authentication\Actions;

use CodeIgniter\Shield\Authentication\Actions\Email2FA;
use CodeIgniter\Shield\Entities\User;

class OptionalEmail2FA extends Email2FA
{
    /**
     * Creates an identity for the action of the user ONLY
     * if the user has explicitly opted-in for Two-Factor Authentication.
     */
    public function createIdentity(User $user): string
    {
        // We check if the user has the 2FA flag enabled.
        // By default, if the property doesn't exist or is false, we bypass 2FA.
        $is2FAEnabled = $user->two_factor_auth_enabled ?? false;

        if (! $is2FAEnabled) {
            // Do not create a database identity. 
            // Returning an empty string safely bypasses Shield's Session Auth Action loop.
            return '';
        }

        // If enabled, proceed with the normal Shield 2FA Identity creation
        return parent::createIdentity($user);
    }
}
