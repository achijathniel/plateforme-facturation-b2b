<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\DTOs\Portal\PortalDashboardStatsData;
use App\Repositories\Contracts\PortalDashboardRepositoryInterface;

final readonly class GetPortalDashboardStatsAction
{
    public function __construct(
        private PortalDashboardRepositoryInterface $repository,
    ) {}

    /**
     * Exécute la récupération des métriques financières et factures de l'organisation.
     */
    public function execute(int $organizationId): PortalDashboardStatsData
    {
        $rawMetrics = $this->repository->getMetricsForOrganization($organizationId);

        return PortalDashboardStatsData::fromRepository($rawMetrics);
    }
}
