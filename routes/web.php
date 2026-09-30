<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInvoiceController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'store'])
            ->middleware('throttle:web-login')
            ->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/invoices', [AdminInvoiceController::class, 'index'])
            ->middleware('throttle:web-admin-read')
            ->name('invoices.index');
    });
});

Route::get('/api/health', function () {
    try {
        $dbName = DB::connection()->getDatabaseName();
        $dbStatus = "Connecté à PostgreSQL (Base : {$dbName})";
    } catch (\Exception $e) {
        $dbStatus = "Erreur PostgreSQL : " . $e->getMessage();
    }

    try {
        $redisPong = Redis::ping();
        $redisStatus = "Connecté à Redis ({$redisPong})";
    } catch (\Exception $e) {
        $redisStatus = "Erreur Redis : " . $e->getMessage();
    }

    return response()->json([
        'status'   => 'success',
        'message'  => 'Tous les conteneurs communiquent parfaitement !',
        'services' => [
            'web_server' => 'Nginx (Reverse Proxy)',
            'app_server' => 'PHP ' . phpversion() . ' FPM',
            'database'   => $dbStatus,
            'redis'      => $redisStatus,
        ],
    ]);
});
