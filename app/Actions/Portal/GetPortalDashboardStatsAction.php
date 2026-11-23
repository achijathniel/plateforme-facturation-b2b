<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\DTOs\Portal\PortalDashboardStatsData;
use App\Repositories\Contracts\PortalDashboardRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final readonly class GetPortalDashboardStatsAction
{
    public const CACHE_TTL_MINUTES = 2;

    public function __construct(
        private PortalDashboardRepositoryInterface $repository,
    ) {}

    public static function cacheKey(int $organizationId): string
    {
        return "portal.dashboard.stats.{$organizationId}";
    }

    public static function forgetForOrganization(int $organizationId): void
    {
        Cache::forget(self::cacheKey($organizationId));
    }

    /**
     * Exécute la récupération des métriques financières et factures de l'organisation
     * avec mise en cache Redis isolée par organisation.
     */
    public function execute(int $organizationId): PortalDashboardStatsData
    {
        $rawMetrics = Cache::remember(
            self::cacheKey($organizationId),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->repository->getMetricsForOrganization($organizationId),
        );

        return PortalDashboardStatsData::fromRepository($rawMetrics);
    }
}
