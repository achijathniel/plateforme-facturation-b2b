<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

abstract readonly class BaseDashboardStatsData
{
    /**
     * @param  array<int, array{key: string, label: string, billed: float, paid: float, overdue: float, count: int}>  $monthlySeries
     * @param  array<int, array{status: string, label: string, count: int, amount: float}>  $statusBreakdown
     * @param  array<int, array{id: int|null, name: string, invoices_count: int, total_billed: float, total_paid: float}>  $topEntities
     * @param  array<int, array<string, mixed>>  $recentInvoices
     */
    public function __construct(
        public string $totalBilled,
        public string $totalPaid,
        public string $totalOverdue,
        public int $overdueCount,
        public int $totalInvoicesCount,
        public float $collectionRate,
        public string $averageInvoiceAmount,
        public array $monthlySeries,
        public array $statusBreakdown,
        public array $topEntities,
        public array $recentInvoices,
    ) {}

    /**
     * Sérialisation commune des métriques et séries pour Inertia.
     *
     * @return array<string, mixed>
     */
    protected function baseToArray(): array
    {
        return [
            'total_billed' => $this->totalBilled,
            'total_paid' => $this->totalPaid,
            'total_overdue' => $this->totalOverdue,
            'overdue_count' => $this->overdueCount,
            'total_invoices_count' => $this->totalInvoicesCount,
            'collection_rate' => $this->collectionRate,
            'average_invoice_amount' => $this->averageInvoiceAmount,
            'monthly_series' => $this->monthlySeries,
            'status_breakdown' => $this->statusBreakdown,
            'top_entities' => $this->topEntities,
            'recent_invoices' => $this->recentInvoices,
        ];
    }
}
