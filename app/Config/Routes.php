<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'DashboardController::index', ['filter' => 'session']);

service('auth')->routes($routes);

// Dashboard
$routes->get('dashboard', 'DashboardController::index', ['filter' => 'permission:dashboard.view']);

// Profile
$routes->get('perfil', 'ProfileController::index', ['filter' => 'session']);
$routes->post('perfil/password', 'ProfileController::updatePassword', ['filter' => 'session']);

// Credit simulation
$routes->match(['get', 'post'], 'simulacion-credito', 'CreditSimulationController::index', ['filter' => 'permission:simulations.create']);

// Pending installments
$routes->get('cuotas-pendientes', 'PendingInstallmentController::index', ['filter' => 'permission:payments.collect']);

// Customers
$routes->group('clientes', ['filter' => 'session'], static function($routes) {
    $routes->get('/', 'CustomerController::index', ['filter' => 'permission:customers.view']);
    $routes->get('crear', 'CustomerController::create', ['filter' => 'permission:customers.manage']);
    $routes->post('guardar', 'CustomerController::store', ['filter' => 'permission:customers.manage']);
    $routes->get('(:segment)', 'CustomerController::show/$1', ['filter' => 'permission:customers.view']);
    $routes->get('(:segment)/editar', 'CustomerController::edit/$1', ['filter' => 'permission:customers.manage']);
    $routes->put('(:segment)', 'CustomerController::update/$1', ['filter' => 'permission:customers.manage']);
    $routes->delete('(:segment)', 'CustomerController::delete/$1', ['filter' => 'permission:customers.delete']);
});

// Loan Applications
$routes->group('solicitudes', ['filter' => 'session'], static function($routes) {
    $routes->get('/', 'LoanApplicationController::index', ['filter' => 'permission:applications.view']);
    $routes->get('crear', 'LoanApplicationController::create', ['filter' => 'permission:applications.create']);
    $routes->post('guardar', 'LoanApplicationController::store', ['filter' => 'permission:applications.create']);
    $routes->get('(:segment)', 'LoanApplicationController::show/$1', ['filter' => 'permission:applications.view']);
    $routes->post('(:segment)/evaluar', 'LoanApplicationController::evaluate/$1', ['filter' => 'permission:applications.manage']);
    $routes->post('(:segment)/aprobar', 'LoanApplicationController::approve/$1', ['filter' => 'permission:applications.manage']);
    $routes->post('(:segment)/rechazar', 'LoanApplicationController::reject/$1', ['filter' => 'permission:applications.manage']);
    $routes->post('(:segment)/desembolsar', 'LoanApplicationController::disburse/$1', ['filter' => 'permission:applications.manage']);
    $routes->post('(:segment)/eliminar', 'LoanApplicationController::delete/$1', ['filter' => 'permission:applications.manage']);
});

// Loans
$routes->group('prestamos', ['filter' => 'session'], static function($routes) {
    $routes->get('/', 'LoanController::index', ['filter' => 'permission:loans.view']);
    $routes->get('(:segment)', 'LoanController::show/$1', ['filter' => 'permission:loans.view']);
    $routes->get('(:segment)/amortizacion', 'LoanController::amortization/$1', ['filter' => 'permission:loans.view']);
    $routes->get('(:segment)/estado-cuenta', 'LoanController::statement/$1', ['filter' => 'permission:loans.view']);
    $routes->get('(:segment)/pdf', 'LoanDocumentController::loan/$1', ['filter' => 'permission:documents.download']);
    $routes->get('(:segment)/amortizacion/pdf', 'LoanDocumentController::amortization/$1', ['filter' => 'permission:documents.download']);
    $routes->get('(:segment)/estado-cuenta/pdf', 'LoanDocumentController::statement/$1', ['filter' => 'permission:documents.download']);
    $routes->get('(:segment)/contrato/pdf', 'LoanDocumentController::contract/$1', ['filter' => 'permission:documents.download']);
    $routes->get('(:segment)/libre-deuda/pdf', 'LoanDocumentController::clearance/$1', ['filter' => 'permission:documents.download']);
    $routes->get('(:segment)/cuotas/(:segment)/pdf', 'LoanDocumentController::installment/$1/$2', ['filter' => 'permission:documents.download']);
    $routes->post('(:segment)/eliminar', 'LoanController::delete/$1', ['filter' => 'permission:loans.manage']);
});

// Payments
$routes->group('pagos', ['filter' => 'session'], static function($routes) {
    $routes->get('/', 'PaymentController::index', ['filter' => 'permission:payments.view']);
    $routes->get('crear', 'PaymentController::create', ['filter' => 'permission:payments.collect']);
    $routes->get('crear/(:segment)', 'PaymentController::create/$1', ['filter' => 'permission:payments.collect']);
    $routes->post('guardar', 'PaymentController::store', ['filter' => 'permission:payments.collect']);
});

// Reports & Audit
$routes->group('reportes', ['filter' => 'session'], static function($routes) {
    $routes->get('dashboard', 'ReportController::dashboard', ['filter' => 'permission:reports.view']);
    $routes->get('mora', 'ReportController::overdue', ['filter' => 'permission:reports.view']);
    $routes->get('auditoria', 'AuditController::index', ['filter' => 'permission:reports.view']);
});

$routes->group('configuracion', ['filter' => 'session'], static function($routes) {
    $routes->group('usuarios', static function($routes) {
        $routes->get('/', 'SettingsController::users', ['filter' => 'permission:users.view']);
        $routes->get('crear', 'SettingsController::createUser', ['filter' => 'permission:users.create']);
        $routes->post('guardar', 'SettingsController::storeUser', ['filter' => 'permission:users.create']);
        $routes->get('(:num)/editar', 'SettingsController::editUser/$1', ['filter' => 'permission:users.edit']);
        $routes->put('(:num)', 'SettingsController::updateUser/$1', ['filter' => 'permission:users.edit']);
        $routes->post('(:num)/toggle', 'SettingsController::toggleUser/$1', ['filter' => 'permission:users.delete']);
        $routes->delete('(:num)', 'SettingsController::deleteUser/$1', ['filter' => 'permission:users.delete']);
    });

    $routes->group('amortizacion', static function($routes) {
        $routes->get('/', 'SettingsController::amortizationSystems', ['filter' => 'permission:settings.manage']);
        $routes->get('crear', 'SettingsController::createAmortizationSystem', ['filter' => 'permission:settings.manage']);
        $routes->post('guardar', 'SettingsController::storeAmortizationSystem', ['filter' => 'permission:settings.manage']);
        $routes->get('(:segment)/editar', 'SettingsController::editAmortizationSystem/$1', ['filter' => 'permission:settings.manage']);
        $routes->put('(:segment)', 'SettingsController::updateAmortizationSystem/$1', ['filter' => 'permission:settings.manage']);
        $routes->post('(:segment)/toggle', 'SettingsController::toggleAmortizationSystem/$1', ['filter' => 'permission:settings.manage']);
        $routes->delete('(:segment)', 'SettingsController::deleteAmortizationSystem/$1', ['filter' => 'permission:settings.manage']);
    });

    $routes->group('cobros', static function($routes) {
        $routes->get('/', 'SettingsController::collectionMethods', ['filter' => 'permission:settings.manage']);
        $routes->get('crear', 'SettingsController::createCollectionMethod', ['filter' => 'permission:settings.manage']);
        $routes->post('guardar', 'SettingsController::storeCollectionMethod', ['filter' => 'permission:settings.manage']);
        $routes->get('(:segment)/editar', 'SettingsController::editCollectionMethod/$1', ['filter' => 'permission:settings.manage']);
        $routes->put('(:segment)', 'SettingsController::updateCollectionMethod/$1', ['filter' => 'permission:settings.manage']);
        $routes->post('(:segment)/toggle', 'SettingsController::toggleCollectionMethod/$1', ['filter' => 'permission:settings.manage']);
        $routes->delete('(:segment)', 'SettingsController::deleteCollectionMethod/$1', ['filter' => 'permission:settings.manage']);
    });
});

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
