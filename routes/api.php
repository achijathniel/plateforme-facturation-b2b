<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Connexion avec limitation stricte anti-brute-force (5 tentatives / min)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:api-login');

    // Routes protégées par jeton Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Invoices Routes (Protected by Sanctum, Multi-tenancy & Rate Limited)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Consultation (Lecture : 60 req/min)
    Route::middleware('throttle:api-invoices-read')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    });

    // Mutations financières (Écriture stricte : 15 req/min)
    Route::middleware('throttle:api-invoices-write')->group(function () {
        Route::post('/invoices', [InvoiceController::class, 'store']);
    });
});
