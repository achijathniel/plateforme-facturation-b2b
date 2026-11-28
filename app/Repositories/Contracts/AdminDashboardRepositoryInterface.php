<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Dashboard\DashboardFilterDTO;

interface AdminDashboardRepositoryInterface
{
    /**
     * Récupère les métriques financières, séries graphiques et factures récentes filtrées.
     *
     * @return array{
     *     total_billed: string,
     *     total_paid: string,
     *     total_overdue: string,
     *     overdue_count: int,
     *     total_invoices: int,
     *     total_organizations: int,
     *     collection_rate: float,
     *     average_invoice_amount: string,
     *     monthly_series: array<int, array{key: string, label: string, billed: float, paid: float, overdue: float, count: int}>,
     *     status_breakdown: array<int, array{status: string, label: string, count: int, amount: float}>,
     *     top_entities: array<int, array{id: int|null, name: string, invoices_count: int, total_billed: float, total_paid: float}>,
     *     recent_invoices: array<int, array<string, mixed>>
     * }
     */
    public function getMetrics(?DashboardFilterDTO $filter = null): array;
}
