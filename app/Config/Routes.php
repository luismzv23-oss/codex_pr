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
$routes->get('dashboard', 'DashboardController::index', ['filter' => 'session']);

// Customers
$routes->group('clientes', ['filter' => 'session'], static function($routes) {
    $routes->get('/', 'CustomerController::index');
    $routes->get('crear', 'CustomerController::create');
    $routes->post('guardar', 'CustomerController::store');
    $routes->get('(:segment)', 'CustomerController::show/$1');
    $routes->get('(:segment)/editar', 'CustomerController::edit/$1');
    $routes->put('(:segment)', 'CustomerController::update/$1');
    $routes->delete('(:segment)', 'CustomerController::delete/$1');
});

// Loan Applications
$routes->group('solicitudes', ['filter' => 'session'], static function($routes) {
    $routes->get('/', 'LoanApplicationController::index');
    $routes->get('crear', 'LoanApplicationController::create');
    $routes->post('guardar', 'LoanApplicationController::store');
    $routes->get('(:segment)', 'LoanApplicationController::show/$1');
    $routes->post('(:segment)/evaluar', 'LoanApplicationController::evaluate/$1');
    $routes->post('(:segment)/aprobar', 'LoanApplicationController::approve/$1');
    $routes->post('(:segment)/rechazar', 'LoanApplicationController::reject/$1');
    $routes->post('(:segment)/desembolsar', 'LoanApplicationController::disburse/$1');
    $routes->post('(:segment)/eliminar', 'LoanApplicationController::delete/$1');
});

// Loans
$routes->group('prestamos', ['filter' => 'session'], static function($routes) {
    $routes->get('/', 'LoanController::index');
    $routes->get('(:segment)', 'LoanController::show/$1');
    $routes->get('(:segment)/amortizacion', 'LoanController::amortization/$1');
    $routes->get('(:segment)/estado-cuenta', 'LoanController::statement/$1');
    $routes->get('(:segment)/pdf', 'LoanDocumentController::loan/$1');
    $routes->get('(:segment)/amortizacion/pdf', 'LoanDocumentController::amortization/$1');
    $routes->get('(:segment)/estado-cuenta/pdf', 'LoanDocumentController::statement/$1');
    $routes->get('(:segment)/contrato/pdf', 'LoanDocumentController::contract/$1');
    $routes->get('(:segment)/libre-deuda/pdf', 'LoanDocumentController::clearance/$1');
    $routes->get('(:segment)/cuotas/(:segment)/pdf', 'LoanDocumentController::installment/$1/$2');
    $routes->post('(:segment)/eliminar', 'LoanController::delete/$1');
});

// Payments
$routes->group('pagos', ['filter' => 'session'], static function($routes) {
    $routes->get('/', 'PaymentController::index');
    $routes->get('crear', 'PaymentController::create');
    $routes->get('crear/(:segment)', 'PaymentController::create/$1');
    $routes->post('guardar', 'PaymentController::store');
});

// Reports & Audit
$routes->group('reportes', ['filter' => 'session'], static function($routes) {
    $routes->get('dashboard', 'ReportController::dashboard');
    $routes->get('mora', 'ReportController::overdue');
    $routes->get('auditoria', 'AuditController::index');
});

$routes->group('configuracion', ['filter' => 'session'], static function($routes) {
    $routes->group('usuarios', static function($routes) {
        $routes->get('/', 'SettingsController::users');
        $routes->get('crear', 'SettingsController::createUser');
        $routes->post('guardar', 'SettingsController::storeUser');
        $routes->get('(:num)/editar', 'SettingsController::editUser/$1');
        $routes->put('(:num)', 'SettingsController::updateUser/$1');
        $routes->post('(:num)/toggle', 'SettingsController::toggleUser/$1');
        $routes->delete('(:num)', 'SettingsController::deleteUser/$1');
    });

    $routes->group('amortizacion', static function($routes) {
        $routes->get('/', 'SettingsController::amortizationSystems');
        $routes->get('crear', 'SettingsController::createAmortizationSystem');
        $routes->post('guardar', 'SettingsController::storeAmortizationSystem');
        $routes->get('(:segment)/editar', 'SettingsController::editAmortizationSystem/$1');
        $routes->put('(:segment)', 'SettingsController::updateAmortizationSystem/$1');
        $routes->post('(:segment)/toggle', 'SettingsController::toggleAmortizationSystem/$1');
        $routes->delete('(:segment)', 'SettingsController::deleteAmortizationSystem/$1');
    });

    $routes->group('cobros', static function($routes) {
        $routes->get('/', 'SettingsController::collectionMethods');
        $routes->get('crear', 'SettingsController::createCollectionMethod');
        $routes->post('guardar', 'SettingsController::storeCollectionMethod');
        $routes->get('(:segment)/editar', 'SettingsController::editCollectionMethod/$1');
        $routes->put('(:segment)', 'SettingsController::updateCollectionMethod/$1');
        $routes->post('(:segment)/toggle', 'SettingsController::toggleCollectionMethod/$1');
        $routes->delete('(:segment)', 'SettingsController::deleteCollectionMethod/$1');
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
