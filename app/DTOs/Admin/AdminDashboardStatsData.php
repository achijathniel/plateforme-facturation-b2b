<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

use App\DTOs\Dashboard\BaseDashboardStatsData;

final readonly class AdminDashboardStatsData extends BaseDashboardStatsData
{
    /**
     * @param  array<int, array{key: string, label: string, billed: float, paid: float, overdue: float, count: int}>  $monthlySeries
     * @param  array<int, array{status: string, label: string, count: int, amount: float}>  $statusBreakdown
     * @param  array<int, array{id: int|null, name: string, invoices_count: int, total_billed: float, total_paid: float}>  $topEntities
     * @param  array<int, array<string, mixed>>  $recentInvoices
     */
    public function __construct(
        string $totalBilled,
        string $totalPaid,
        string $totalOverdue,
        int $overdueCount,
        int $totalInvoicesCount,
        public int $totalOrganizationsCount,
        float $collectionRate,
        string $averageInvoiceAmount,
        array $monthlySeries,
        array $statusBreakdown,
        array $topEntities,
        array $recentInvoices,
    ) {
        parent::__construct(
            totalBilled: $totalBilled,
            totalPaid: $totalPaid,
            totalOverdue: $totalOverdue,
            overdueCount: $overdueCount,
            totalInvoicesCount: $totalInvoicesCount,
            collectionRate: $collectionRate,
            averageInvoiceAmount: $averageInvoiceAmount,
            monthlySeries: $monthlySeries,
            statusBreakdown: $statusBreakdown,
            topEntities: $topEntities,
            recentInvoices: $recentInvoices,
        );
    }

    /**
     * Crée une instance depuis le résultat du repository.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromRepository(array $data): self
    {
        return new self(
            totalBilled: (string) ($data['total_billed'] ?? '0'),
            totalPaid: (string) ($data['total_paid'] ?? '0'),
            totalOverdue: (string) ($data['total_overdue'] ?? '0'),
            overdueCount: (int) ($data['overdue_count'] ?? 0),
            totalInvoicesCount: (int) ($data['total_invoices'] ?? 0),
            totalOrganizationsCount: (int) ($data['total_organizations'] ?? 0),
            collectionRate: (float) ($data['collection_rate'] ?? 0.0),
            averageInvoiceAmount: (string) ($data['average_invoice_amount'] ?? '0.00'),
            monthlySeries: (array) ($data['monthly_series'] ?? []),
            statusBreakdown: (array) ($data['status_breakdown'] ?? []),
            topEntities: (array) ($data['top_entities'] ?? []),
            recentInvoices: (array) ($data['recent_invoices'] ?? []),
        );
    }

    /**
     * Transforme l'objet en tableau pour la sérialisation Inertia.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ...$this->baseToArray(),
            'total_organizations_count' => $this->totalOrganizationsCount,
        ];
    }
}
