<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalDashboardController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Alias direct /admin-dashboard
Route::get('/admin-dashboard', fn () => redirect()->route('admin.dashboard'));

/*
|--------------------------------------------------------------------------
| Espace Administrateur Platform (/admin/*)
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Espace Portail Entreprise / Comptable (/portal/* et /portal-dashboard)
|--------------------------------------------------------------------------
*/
Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [PortalAuthController::class, 'create'])->name('login');
        Route::post('/login', [PortalAuthController::class, 'store'])
            ->middleware('throttle:web-login')
            ->name('login.store');
    });

    Route::middleware(['auth', 'portal'])->group(function () {
        Route::post('/logout', [PortalAuthController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', fn () => redirect()->route('portal.dashboard'));
    });
});

Route::get('/portal-dashboard', [PortalDashboardController::class, 'index'])
    ->middleware(['auth', 'portal', 'throttle:web-portal-read'])
    ->name('portal.dashboard');

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
