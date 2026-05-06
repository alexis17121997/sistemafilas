<?php
// ─── Bootstrap ────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// Core
require_once APP_PATH . '/app/Core/Database.php';
require_once APP_PATH . '/app/Core/Router.php';
require_once APP_PATH . '/app/Core/Controller.php';
require_once APP_PATH . '/app/Core/Auth.php';

// Models
require_once APP_PATH . '/app/Models/Models.php';
require_once APP_PATH . '/app/Models/Ticket.php';

// Controllers
require_once APP_PATH . '/app/Controllers/Controllers.php';

// Session
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'httponly' => true,
]);
session_start();

// ─── Routes ───────────────────────────────────────────────────────────────────
$router = new Router();

// Auth
$router->get('/login',  'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');
$router->get('/',       'AuthController@loginForm');

// Admin
$router->get('/admin/dashboard',           'AdminController@dashboard');
$router->get('/admin/users',               'AdminController@users');
$router->post('/admin/users/create',       'AdminController@createUser');
$router->post('/admin/users/{id}/update',  'AdminController@updateUser');
$router->post('/admin/users/{id}/delete',  'AdminController@deleteUser');
$router->get('/admin/branches',            'AdminController@branches');
$router->post('/admin/branches/create',    'AdminController@createBranch');
$router->post('/admin/branches/{id}/update','AdminController@updateBranch');
$router->get('/admin/windows',             'AdminController@windows');
$router->post('/admin/windows/create',     'AdminController@createWindow');
$router->post('/admin/windows/{id}/delete','AdminController@deleteWindow');
$router->get('/admin/printer',             'AdminController@printer');
$router->post('/admin/printer/save',       'AdminController@savePrinter');
$router->get('/admin/advertising',         'AdminController@advertising');
$router->post('/admin/advertising/save',   'AdminController@saveAdvertising');
$router->post('/admin/advertising/{id}/delete','AdminController@deleteAdvertising');
$router->get('/admin/specialties',         'AdminController@specialties');
$router->post('/admin/specialties/save',   'AdminController@saveSpecialty');
$router->post('/admin/specialties/{id}/delete','AdminController@deleteSpecialty');

// Supervisor
$router->get('/supervisor/dashboard', 'SupervisorController@dashboard');
$router->get('/supervisor/reports',   'SupervisorController@reports');

// Cashier
$router->get('/cashier',               'CashierController@index');
$router->get('/cashier/select-window', 'CashierController@selectWindowForm');
$router->post('/cashier/select-window','CashierController@assignWindow');
$router->get('/cashier/release',       'CashierController@releaseWindow');

// Dispenser
$router->get('/dispenser', 'DispenserController@kiosk');

// Display
$router->get('/display', 'DisplayController@screen');

// API
$router->post('/api/issue-ticket',      'ApiController@issueTicket');
$router->post('/api/call-next',         'ApiController@callNext');
$router->post('/api/recall',            'ApiController@recall');
$router->post('/api/serve',             'ApiController@serve');
$router->post('/api/complete',          'ApiController@complete');
$router->post('/api/no-show',           'ApiController@noShow');
$router->get('/api/last-calls',         'ApiController@lastCalls');
$router->get('/api/queue-status',       'ApiController@queueStatus');
$router->get('/api/ticket/{id}/print',  'ApiController@ticketPrint');

// Dispatch
$router->dispatch();
