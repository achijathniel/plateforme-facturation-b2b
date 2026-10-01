<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Repositories\Contracts\PortalDashboardRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class PostgresPortalDashboardRepository implements PortalDashboardRepositoryInterface
{
    /**
     * Récupère les métriques financières et les dernières factures d'une organisation.
     * En production sous PostgreSQL 16, utilise une CTE unique avec FILTER et json_agg pour 0 roundtrip superflu.
     * En environnement de test (SQLite en mémoire), bascule sur des agrégations compatibles sans régression.
     */
    public function getMetricsForOrganization(int $organizationId): array
    {
        if (DB::getDriverName() === 'pgsql') {
            return $this->getPostgresCteMetrics($organizationId);
        }

        return $this->getStandardMetrics($organizationId);
    }

    /**
     * Requête unique hautement optimisée PostgreSQL 16 (CTE + FILTER + json_agg) filtrée par organization_id.
     */
    private function getPostgresCteMetrics(int $organizationId): array
    {
        $paidStatus = InvoiceStatus::PAID->value;
        $overdueStatus = InvoiceStatus::OVERDUE->value;

        $sql = <<<SQL
            WITH invoice_stats AS (
                SELECT 
                    COUNT(*) AS total_invoices,
                    COALESCE(SUM(total), 0) AS total_billed,
                    COALESCE(SUM(total) FILTER (WHERE status = '{$paidStatus}'), 0) AS total_paid,
                    COALESCE(SUM(total) FILTER (WHERE status = '{$overdueStatus}'), 0) AS total_overdue
                FROM invoices 
                WHERE organization_id = :org_id 
                  AND deleted_at IS NULL
            ),
            recent_invoices AS (
                SELECT 
                    i.id,
                    i.invoice_number,
                    i.client_name,
                    i.total,
                    i.currency,
                    i.status,
                    to_char(i.issue_date, 'YYYY-MM-DD') AS issue_date,
                    to_char(i.due_date, 'YYYY-MM-DD') AS due_date,
                    o.name AS organization_name
                FROM invoices i
                JOIN organizations o ON i.organization_id = o.id
                WHERE i.organization_id = :org_id
                  AND i.deleted_at IS NULL 
                  AND o.deleted_at IS NULL
                ORDER BY i.issue_date DESC, i.id DESC
                LIMIT 6
            )
            SELECT 
                (SELECT total_invoices FROM invoice_stats) AS total_invoices,
                (SELECT total_billed FROM invoice_stats) AS total_billed,
                (SELECT total_paid FROM invoice_stats) AS total_paid,
                (SELECT total_overdue FROM invoice_stats) AS total_overdue,
                (SELECT json_agg(row_to_json(recent_invoices)) FROM recent_invoices) AS recent_invoices;
        SQL;

        $result = DB::selectOne($sql, ['org_id' => $organizationId]);

        $recentInvoices = [];
        if (!empty($result->recent_invoices)) {
            $recentInvoices = is_string($result->recent_invoices)
                ? json_decode($result->recent_invoices, true)
                : (array) $result->recent_invoices;
        }

        return [
            'total_billed'    => (string) ($result->total_billed ?? '0'),
            'total_paid'      => (string) ($result->total_paid ?? '0'),
            'total_overdue'   => (string) ($result->total_overdue ?? '0'),
            'total_invoices'  => (int) ($result->total_invoices ?? 0),
            'recent_invoices' => $recentInvoices,
        ];
    }

    /**
     * Fallback standard (Query Builder) compatible SQLite et multi-driver.
     */
    private function getStandardMetrics(int $organizationId): array
    {
        $paidStatus = InvoiceStatus::PAID->value;
        $overdueStatus = InvoiceStatus::OVERDUE->value;

        $stats = DB::table('invoices')
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_invoices,
                COALESCE(SUM(total), 0) as total_billed,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_overdue
            ", [$paidStatus, $overdueStatus])
            ->first();

        $recentInvoices = Invoice::with('organization:id,name')
            ->where('organization_id', $organizationId)
            ->latest('issue_date')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id'                => $invoice->id,
                'invoice_number'    => $invoice->invoice_number,
                'client_name'       => $invoice->client_name,
                'total'             => (string) $invoice->total,
                'currency'          => $invoice->currency,
                'status'            => $invoice->status?->value ?? (string) $invoice->status,
                'issue_date'        => $invoice->issue_date?->format('Y-m-d'),
                'due_date'          => $invoice->due_date?->format('Y-m-d'),
                'organization_name' => $invoice->organization?->name ?? 'N/A',
            ])
            ->all();

        return [
            'total_billed'    => (string) ($stats->total_billed ?? '0'),
            'total_paid'      => (string) ($stats->total_paid ?? '0'),
            'total_overdue'   => (string) ($stats->total_overdue ?? '0'),
            'total_invoices'  => (int) ($stats->total_invoices ?? 0),
            'recent_invoices' => $recentInvoices,
        ];
    }
}
