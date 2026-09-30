<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\PortalDashboardRepositoryInterface;
use App\Repositories\Eloquent\InvoiceRepository;
use App\Repositories\Eloquent\OrganizationRepository;
use App\Repositories\Eloquent\PostgresAdminDashboardRepository;
use App\Repositories\Eloquent\PostgresPortalDashboardRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
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
        $this->app->bind(AdminDashboardRepositoryInterface::class, PostgresAdminDashboardRepository::class);
        $this->app->bind(OrganizationRepositoryInterface::class, OrganizationRepository::class);
        $this->app->bind(PortalDashboardRepositoryInterface::class, PostgresPortalDashboardRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Interdire le Lazy Loading hors production pour stopper immédiatement le problème N+1
        Model::preventLazyLoading(! $this->app->isProduction());

        // Rate limiter anti-brute-force pour l'authentification API (5 tentatives / minute par IP)
        RateLimiter::for('api-login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Rate limiter anti-brute-force pour l'authentification Web (5 tentatives / minute par IP)
        RateLimiter::for('web-login', function (Request $request) {
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

        // Rate limiter pour la consultation de l'espace d'administration (120 requêtes / minute par administrateur)
        RateLimiter::for('web-admin-read', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter pour la consultation du portail entreprise / comptable (120 requêtes / minute)
        RateLimiter::for('web-portal-read', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter pour la création et émission de factures Web (15 requêtes / minute)
        RateLimiter::for('web-invoice-create', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->id ?: $request->ip());
        });
    }
}
