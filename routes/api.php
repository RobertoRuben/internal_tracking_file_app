<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ChargeBookController;
use App\Http\Controllers\Api\DerivationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    // Rutas públicas para autenticación
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        // Rutas de autenticación protegidas
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/change-password', [AuthController::class, 'changePassword']);
        
        // Rutas para empleados - requieren permisos específicos
        Route::middleware(['api.permission:view_any_employee'])->get('employees/all', [EmployeeController::class, 'getAll']);
        Route::apiResource('employees', EmployeeController::class)->middleware([
            'index' => 'api.permission:view_any_employee',
            'show' => 'api.permission:view_employee',
            'store' => 'api.permission:create_employee',
            'update' => 'api.permission:update_employee',
            'destroy' => 'api.permission:delete_employee',
        ]);

        // Rutas para departamentos - requieren permisos específicos
        Route::middleware(['api.permission:view_any_department'])->get('departments/all', [DepartmentController::class, 'getAll']);
        Route::apiResource('departments', DepartmentController::class)->middleware([
            'index' => 'api.permission:view_any_department',
            'show' => 'api.permission:view_department',
            'store' => 'api.permission:create_department',
            'update' => 'api.permission:update_department',
            'destroy' => 'api.permission:delete_department',
        ]);

        // Rutas para documentos - requieren permisos específicos
        Route::middleware(['api.permission:view_any_document'])->get('documents/all', [DocumentController::class, 'getAll']);
        Route::middleware(['api.permission:view_document'])->get('documents/{id}/download', [DocumentController::class, 'downloadFile']);
        Route::apiResource('documents', DocumentController::class)->middleware([
            'index' => 'api.permission:view_any_document',
            'show' => 'api.permission:view_document',
            'store' => 'api.permission:create_document',
            'update' => 'api.permission:update_document',
            'destroy' => 'api.permission:delete_document',
        ]);
        
        // Rutas para libros de cargo - requieren permisos específicos
        Route::middleware(['api.permission:view_any_chargebook'])->get('charge-books/all', [ChargeBookController::class, 'getAll']);
        Route::apiResource('charge-books', ChargeBookController::class)->middleware([
            'index' => 'api.permission:view_any_chargebook',
            'show' => 'api.permission:view_chargebook',
            'store' => 'api.permission:create_chargebook',
            'update' => 'api.permission:update_chargebook',
            'destroy' => 'api.permission:delete_chargebook',
        ]);
        
        // Rutas para derivaciones - requieren permisos específicos
        Route::middleware(['api.permission:view_any_derivation'])->get('derivations/all', [DerivationController::class, 'getAll']);
        Route::middleware(['api.permission:update_derivation'])->post('derivations/{id}/comments', [DerivationController::class, 'addComment']);
        Route::apiResource('derivations', DerivationController::class)->middleware([
            'index' => 'api.permission:view_any_derivation',
            'show' => 'api.permission:view_derivation',
            'store' => 'api.permission:create_derivation',
            'update' => 'api.permission:update_derivation',
            'destroy' => 'api.permission:delete_derivation',
        ]);
        
        // Rutas para usuarios - requieren permisos específicos (solo superadmin o roles con permisos especiales)
        Route::middleware(['api.permission:view_any_user'])->get('users/all', [UserController::class, 'getAll']);
        Route::middleware(['api.permission:view_any_role'])->get('users/roles', [UserController::class, 'getRoles']);
        Route::middleware(['api.permission:update_user'])->put('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::apiResource('users', UserController::class)->middleware([
            'index' => 'api.permission:view_any_user',
            'show' => 'api.permission:view_user',
            'store' => 'api.permission:create_user',
            'update' => 'api.permission:update_user',
            'destroy' => 'api.permission:delete_user',
        ]);
    });
});
