<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\DTOs\Dashboard\DashboardFilterDTO;
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
     * Récupère les métriques financières et séries analytiques de l'organisation.
     * La vue par défaut bénéficie du cache Redis isolé par organisation.
     */
    public function execute(int $organizationId, ?DashboardFilterDTO $filter = null): PortalDashboardStatsData
    {
        $activeFilter = $filter ?? new DashboardFilterDTO;

        if ($activeFilter->isDefault()) {
            $rawMetrics = Cache::remember(
                self::cacheKey($organizationId),
                now()->addMinutes(self::CACHE_TTL_MINUTES),
                fn (): array => $this->repository->getMetricsForOrganization($organizationId, $activeFilter),
            );
        } else {
            $rawMetrics = $this->repository->getMetricsForOrganization($organizationId, $activeFilter);
        }

        return PortalDashboardStatsData::fromRepository($rawMetrics);
    }
}
