<?php

declare(strict_types=1);

namespace App\DTOs\Portal;

final readonly class PortalDashboardStatsData
{
    /**
     * @param array<int, array<string, mixed>> $recentInvoices
     */
    public function __construct(
        public string $totalBilled,
        public string $totalPaid,
        public string $totalOverdue,
        public int $totalInvoicesCount,
        public array $recentInvoices,
    ) {}

    /**
     * Crée une instance depuis le résultat du repository.
     *
     * @param array<string, mixed> $data
     */
    public static function fromRepository(array $data): self
    {
        return new self(
            totalBilled: (string) ($data['total_billed'] ?? '0'),
            totalPaid: (string) ($data['total_paid'] ?? '0'),
            totalOverdue: (string) ($data['total_overdue'] ?? '0'),
            totalInvoicesCount: (int) ($data['total_invoices'] ?? 0),
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
            'total_billed'         => $this->totalBilled,
            'total_paid'           => $this->totalPaid,
            'total_overdue'        => $this->totalOverdue,
            'total_invoices_count' => $this->totalInvoicesCount,
            'recent_invoices'      => $this->recentInvoices,
        ];
    }
}
