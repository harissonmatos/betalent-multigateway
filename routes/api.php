<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Login
Route::post('/login', [AuthController::class, 'login']);

// Products
// Rotas de listagem de produtos e detalhes fica publica para o cliente (não usuário logado) pode ve-los
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Checkout
Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Users
    Route::middleware('role:ADMIN,MANAGER')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // Products
    Route::middleware('role:ADMIN,MANAGER,FINANCE')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    // Gateways
    Route::middleware('role:ADMIN')->group(function () {
        Route::put('/gateways/{gateway}/activate', [GatewayController::class, 'activate']);
        Route::put('/gateways/{gateway}/deactivate', [GatewayController::class, 'deactivate']);
        Route::put('/gateways/{gateway}/priority', [GatewayController::class, 'updatePriority']);
    });
    Route::middleware('role:ADMIN,MANAGER,FINANCE,USER')->group(function () {
        Route::get('/gateways', [GatewayController::class, 'index']);
    });

    // Clients
    Route::middleware('role:ADMIN,MANAGER,FINANCE,USER')->group(function () {
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/{client}', [ClientController::class, 'show']);
    });

    // Transactions
    Route::middleware('role:ADMIN,MANAGER,FINANCE,USER')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    });

    // Refund
    Route::middleware('role:ADMIN,FINANCE')->group(function () {
        Route::put('/transactions/{transaction}/refund', [TransactionController::class, 'refund']);
    });
});
