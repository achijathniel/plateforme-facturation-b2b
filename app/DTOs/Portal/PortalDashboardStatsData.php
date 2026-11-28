<?php

declare(strict_types=1);

namespace App\DTOs\Portal;

use App\DTOs\Dashboard\BaseDashboardStatsData;

final readonly class PortalDashboardStatsData extends BaseDashboardStatsData
{
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
        return $this->baseToArray();
    }
}
