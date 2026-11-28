<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\PortalDashboardRepositoryInterface;
use App\Repositories\Eloquent\ClientRepository;
use App\Repositories\Eloquent\InvoiceRepository;
use App\Repositories\Eloquent\OrganizationRepository;
use App\Repositories\Eloquent\PostgresAdminDashboardRepository;
use App\Repositories\Eloquent\PostgresPortalDashboardRepository;
use App\Services\Dashboard\Contracts\DashboardAnalyticsFormatterInterface;
use App\Services\Dashboard\DashboardAnalyticsFormatter;
use App\Services\Imports\Contracts\OrganizationSpreadsheetReaderInterface;
use App\Services\Imports\Contracts\OrganizationTemplateGeneratorInterface;
use App\Services\Imports\OpenSpoutOrganizationReader;
use App\Services\Imports\OpenSpoutOrganizationTemplateGenerator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $this->app->bind(DashboardAnalyticsFormatterInterface::class, DashboardAnalyticsFormatter::class);
        $this->app->bind(ClientRepositoryInterface::class, ClientRepository::class);
        $this->app->bind(OrganizationSpreadsheetReaderInterface::class, OpenSpoutOrganizationReader::class);
        $this->app->bind(OrganizationTemplateGeneratorInterface::class, OpenSpoutOrganizationTemplateGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Protection absolue : interdire toute commande destructive (migrate:fresh, db:wipe) sur la base principale
        DB::prohibitDestructiveCommands(
            $this->app->isProduction() || config('database.connections.pgsql.database') === 'billing_db'
        );

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

        // Rate limiter pour les mutations administratives d'organisations et paramètres (30 requêtes / minute)
        RateLimiter::for('web-admin-write', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter strict pour l'import massif d'entreprises par fichier Excel (5 requêtes / minute)
        RateLimiter::for('web-admin-import', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });
    }
}
