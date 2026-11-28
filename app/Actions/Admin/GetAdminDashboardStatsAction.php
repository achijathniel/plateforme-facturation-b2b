<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\AdminDashboardStatsData;
use App\DTOs\Dashboard\DashboardFilterDTO;
use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final readonly class GetAdminDashboardStatsAction
{
    public const CACHE_KEY = 'admin.dashboard.stats';

    public const CACHE_TTL_MINUTES = 5;

    public function __construct(
        private AdminDashboardRepositoryInterface $dashboardRepository,
    ) {}

    /**
     * Récupère et structure les indicateurs financiers du tableau de bord.
     * La vue par défaut reste mise en cache 5 minutes ; les vues filtrées sont calculées à la demande.
     */
    public function execute(?DashboardFilterDTO $filter = null): AdminDashboardStatsData
    {
        $activeFilter = $filter ?? new DashboardFilterDTO;

        if ($activeFilter->isDefault()) {
            $metrics = Cache::remember(
                self::CACHE_KEY,
                now()->addMinutes(self::CACHE_TTL_MINUTES),
                fn (): array => $this->dashboardRepository->getMetrics($activeFilter),
            );
        } else {
            $metrics = $this->dashboardRepository->getMetrics($activeFilter);
        }

        return AdminDashboardStatsData::fromRepository($metrics);
    }
}
