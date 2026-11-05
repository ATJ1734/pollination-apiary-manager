<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Controllers\AdminAuditController;
use App\Controllers\AdminUserController;
use App\Controllers\ApiaryController;
use App\Controllers\AuthController;
use App\Controllers\ContractController;
use App\Controllers\DashboardController;
use App\Controllers\ErrorController;
use App\Controllers\ExportController;
use App\Controllers\GrowerController;
use App\Controllers\HiveController;
use App\Controllers\InspectionController;
use App\Controllers\PlacementController;
use App\Controllers\PlannerController;
use App\Controllers\TreatmentController;
use App\Routing\Router;

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['any']);

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

$admin = ['admin'];
$staff = ['admin', 'manager'];
$everyone = ['admin', 'manager', 'field_tech'];

$router->get('/apiaries', [ApiaryController::class, 'index'], $everyone);
$router->get('/apiaries/search', [ApiaryController::class, 'search'], $everyone);
$router->get('/apiaries/create', [ApiaryController::class, 'create'], $staff);
$router->post('/apiaries', [ApiaryController::class, 'store'], $staff);
$router->get('/apiaries/{id}/edit', [ApiaryController::class, 'edit'], $staff);
$router->post('/apiaries/{id}', [ApiaryController::class, 'update'], $staff);
$router->post('/apiaries/{id}/delete', [ApiaryController::class, 'destroy'], $staff);

$router->get('/hives', [HiveController::class, 'index'], $everyone);
$router->get('/hives/search', [HiveController::class, 'search'], $everyone);
$router->get('/hives/create', [HiveController::class, 'create'], $staff);
$router->post('/hives', [HiveController::class, 'store'], $staff);
$router->get('/hives/{id}', [HiveController::class, 'show'], $everyone);
$router->get('/hives/{id}/edit', [HiveController::class, 'edit'], $staff);
$router->post('/hives/{id}', [HiveController::class, 'update'], $staff);
$router->post('/hives/{id}/delete', [HiveController::class, 'destroy'], $staff);

$router->get('/inspections', [InspectionController::class, 'index'], $everyone);
$router->get('/inspections/create', [InspectionController::class, 'create'], $everyone);
$router->post('/inspections', [InspectionController::class, 'store'], $everyone);
$router->get('/inspections/{id}/edit', [InspectionController::class, 'edit'], $staff);
$router->post('/inspections/{id}', [InspectionController::class, 'update'], $staff);
$router->post('/inspections/{id}/delete', [InspectionController::class, 'destroy'], $staff);

$router->get('/treatments', [TreatmentController::class, 'index'], $everyone);
$router->get('/treatments/create', [TreatmentController::class, 'create'], $everyone);
$router->post('/treatments', [TreatmentController::class, 'store'], $everyone);
$router->get('/treatments/{id}/edit', [TreatmentController::class, 'edit'], $staff);
$router->post('/treatments/{id}', [TreatmentController::class, 'update'], $staff);
$router->post('/treatments/{id}/delete', [TreatmentController::class, 'destroy'], $staff);

$router->get('/growers', [GrowerController::class, 'index'], $staff);
$router->get('/growers/search', [GrowerController::class, 'search'], $staff);
$router->get('/growers/create', [GrowerController::class, 'create'], $staff);
$router->post('/growers', [GrowerController::class, 'store'], $staff);
$router->get('/growers/{id}/edit', [GrowerController::class, 'edit'], $staff);
$router->post('/growers/{id}', [GrowerController::class, 'update'], $staff);
$router->post('/growers/{id}/delete', [GrowerController::class, 'destroy'], $staff);

$router->get('/contracts', [ContractController::class, 'index'], $everyone);
$router->get('/contracts/create', [ContractController::class, 'create'], $staff);
$router->post('/contracts', [ContractController::class, 'store'], $staff);
$router->get('/contracts/{id}', [ContractController::class, 'show'], $everyone);
$router->get('/contracts/{id}/edit', [ContractController::class, 'edit'], $staff);
$router->post('/contracts/{id}', [ContractController::class, 'update'], $staff);
$router->post('/contracts/{id}/delete', [ContractController::class, 'destroy'], $staff);
$router->get('/contracts/{id}/print', [ContractController::class, 'print'], $staff);
$router->get('/contracts/{id}/planner', [PlannerController::class, 'show'], $staff);
$router->post('/contracts/{id}/planner/assign', [PlannerController::class, 'assign'], $staff);
$router->post('/placements/{id}/remove', [PlacementController::class, 'remove'], $staff);

$router->get('/export/{entity}', [ExportController::class, 'csv'], $everyone);

$router->get('/admin/users', [AdminUserController::class, 'index'], $admin);
$router->get('/admin/users/create', [AdminUserController::class, 'create'], $admin);
$router->post('/admin/users', [AdminUserController::class, 'store'], $admin);
$router->get('/admin/users/{id}/edit', [AdminUserController::class, 'edit'], $admin);
$router->post('/admin/users/{id}', [AdminUserController::class, 'update'], $admin);
$router->post('/admin/users/{id}/delete', [AdminUserController::class, 'destroy'], $admin);
$router->get('/admin/audit-log', [AdminAuditController::class, 'index'], $admin);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $e) {
    (new ErrorController())->serverError($e);
}
