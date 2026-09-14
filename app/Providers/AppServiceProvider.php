<?php

namespace App\Providers;

use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Eloquent\InvoiceRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Liaison de l'interface Repository à son implémentation Eloquent (Inversion de dépendances - SOLID D)
        $this->app->bind(InvoiceRepositoryInterface::class, InvoiceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rate limiter anti-brute-force pour l'authentification API (5 tentatives / minute par IP)
        RateLimiter::for('api-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limiter pour la consultation des factures (60 requêtes / minute par utilisateur)
        RateLimiter::for('api-invoices-read', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter strict pour les mutations financières (15 requêtes / minute par utilisateur)
        RateLimiter::for('api-invoices-write', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });
    }
}
