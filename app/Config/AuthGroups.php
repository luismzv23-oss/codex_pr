<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    public string $defaultGroup = 'operator';

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * An associative array of the available groups in the system, where the keys
     * are the group names and the values are arrays of the group info.
     *
     * Whatever value you assign as the key will be used to refer to the group
     * when using functions such as:
     *      $user->addGroup('superadmin');
     *
     * @var array<string, array<string, string>>
     *
     * @see https://codeigniter4.github.io/shield/quick_start_guide/using_authorization/#change-available-groups for more info
     */
    public array $groups = [
        'admin' => [
            'title'       => 'Administrador',
            'description' => 'Acceso pleno a todas las funcionalidades del sistema.',
        ],
        'operator' => [
            'title'       => 'Operador',
            'description' => 'Operador de solicitudes, cuotas, simulaciones, usuarios y documentos.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * The available permissions in the system.
     *
     * If a permission is not listed here it cannot be used.
     */
    public array $permissions = [
        'admin.access'        => 'Acceso pleno administrativo',
        'dashboard.view'      => 'Ver panel principal',
        'customers.view'      => 'Ver fichas de clientes',
        'customers.manage'    => 'Registrar y editar clientes',
        'customers.delete'    => 'Eliminar clientes',
        'applications.view'   => 'Ver solicitudes',
        'applications.create' => 'Crear solicitudes',
        'applications.manage' => 'Evaluar, aprobar, rechazar y eliminar solicitudes',
        'loans.view'          => 'Ver prestamos',
        'loans.manage'        => 'Administrar prestamos',
        'payments.view'       => 'Ver historial de pagos',
        'payments.collect'    => 'Cobrar cuotas pendientes de prestamos',
        'simulations.create'  => 'Simular solicitudes de credito',
        'documents.download'  => 'Descargar documentos PDF de prestamos',
        'reports.view'        => 'Ver reportes',
        'settings.manage'     => 'Administrar configuracion',
        'users.view'          => 'Ver usuarios',
        'users.create'        => 'Registrar usuarios',
        'users.edit'          => 'Editar usuarios',
        'users.delete'        => 'Eliminar usuarios',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
    public array $matrix = [
        'admin' => [
            'admin.access',
            'dashboard.view',
            'customers.view',
            'customers.manage',
            'customers.delete',
            'applications.view',
            'applications.create',
            'applications.manage',
            'loans.view',
            'loans.manage',
            'payments.view',
            'payments.collect',
            'simulations.create',
            'documents.download',
            'reports.view',
            'settings.manage',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
        ],
        'operator' => [
            'customers.view',
            'customers.manage',
            'applications.view',
            'applications.create',
            'loans.view',
            'payments.collect',
            'simulations.create',
            'documents.download',
        ],
    ];
}
