<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ChargeBookController;
use App\Http\Controllers\Api\DerivationController;
use App\Http\Controllers\Api\UserController;

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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('employees/all', [EmployeeController::class, 'getAll']);
        Route::apiResource('employees', EmployeeController::class);

        Route::get('departments/all', [DepartmentController::class, 'getAll']);
        Route::apiResource('departments', DepartmentController::class);

        Route::get('documents/all', [DocumentController::class, 'getAll']);
        Route::get('documents/{id}/download', [DocumentController::class, 'downloadFile']);
        Route::apiResource('documents', DocumentController::class);
        
        Route::get('charge-books/all', [ChargeBookController::class, 'getAll']);
        Route::apiResource('charge-books', ChargeBookController::class);
        
        Route::get('derivations/all', [DerivationController::class, 'getAll']);
        Route::post('derivations/{id}/comments', [DerivationController::class, 'addComment']);
        Route::apiResource('derivations', DerivationController::class);
        
        Route::get('users/all', [UserController::class, 'getAll']);
        Route::get('users/roles', [UserController::class, 'getRoles']);
        Route::put('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::apiResource('users', UserController::class);
    });
});
