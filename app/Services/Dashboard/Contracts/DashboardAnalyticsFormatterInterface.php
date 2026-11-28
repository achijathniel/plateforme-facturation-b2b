<?php

declare(strict_types=1);

namespace App\Services\Dashboard\Contracts;

use App\DTOs\Dashboard\DashboardFilterDTO;

interface DashboardAnalyticsFormatterInterface
{
    /**
     * Construit une série temporelle continue (sans jours ni mois manquants) à partir des agrégats SQL.
     *
     * @param  array<int, array<string, mixed>>  $rawRows
     * @return array<int, array{key: string, label: string, billed: float, paid: float, overdue: float, count: int}>
     */
    public function buildTimelineSeries(DashboardFilterDTO $filter, array $rawRows): array;

    /**
     * Normalise la répartition par statut dans un ordre stable et lisible.
     *
     * @param  array<int, array<string, mixed>>  $rawStatusRows
     * @return array<int, array{status: string, label: string, count: int, amount: float}>
     */
    public function buildStatusBreakdown(array $rawStatusRows): array;

    /**
     * Calcule le taux de recouvrement en pourcentage (1 décimale).
     */
    public function computeCollectionRate(string $totalBilled, string $totalPaid): float;

    /**
     * Calcule le montant moyen par facture (format chaîne à 2 décimales).
     */
    public function computeAverageInvoiceAmount(string $totalBilled, int $totalCount): string;
}
