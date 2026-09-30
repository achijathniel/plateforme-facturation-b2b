<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface PortalDashboardRepositoryInterface
{
    /**
     * Récupère les métriques financières et les factures récentes d'une entreprise spécifique.
     *
     * @param int $organizationId
     * @return array{
     *     total_billed: string,
     *     total_paid: string,
     *     total_overdue: string,
     *     total_invoices: int,
     *     recent_invoices: array<int, array<string, mixed>>
     * }
     */
    public function getMetricsForOrganization(int $organizationId): array;
}
