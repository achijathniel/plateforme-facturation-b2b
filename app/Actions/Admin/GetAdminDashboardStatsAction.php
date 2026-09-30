<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\AdminDashboardStatsData;
use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final class GetAdminDashboardStatsAction
{
    public const CACHE_KEY = 'admin.dashboard.stats';
    public const CACHE_TTL_MINUTES = 5;

    public function __construct(
        private readonly AdminDashboardRepositoryInterface $dashboardRepository,
    ) {}

    /**
     * Récupère et structure les indicateurs financiers du tableau de bord
     * avec une mise en cache de 5 minutes pour préserver les performances en production.
     */
    public function execute(): AdminDashboardStatsData
    {
        $metrics = Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->dashboardRepository->getMetrics(),
        );

        return AdminDashboardStatsData::fromRepository($metrics);
    }
}
