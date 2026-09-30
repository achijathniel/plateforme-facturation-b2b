<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface AdminDashboardRepositoryInterface
{
    /**
     * Récupère les métriques financières globales et les factures récentes pour le tableau de bord.
     *
     * @return array{
     *     total_billed: string,
     *     total_paid: string,
     *     total_overdue: string,
     *     total_invoices: int,
     *     total_organizations: int,
     *     recent_invoices: array<int, array<string, mixed>>
     * }
     */
    public function getMetrics(): array;
}
