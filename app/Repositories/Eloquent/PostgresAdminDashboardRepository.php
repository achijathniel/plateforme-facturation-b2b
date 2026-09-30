<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class PostgresAdminDashboardRepository implements AdminDashboardRepositoryInterface
{
    /**
     * Récupère les métriques financières globales et les dernières factures.
     * En production sous PostgreSQL 16, utilise une CTE unique avec json_agg et FILTER pour 0 roundtrip superflu.
     * En environnement de test (SQLite en mémoire), bascule sur des agrégations compatibles sans régression.
     */
    public function getMetrics(): array
    {
        if (DB::getDriverName() === 'pgsql') {
            return $this->getPostgresCteMetrics();
        }

        return $this->getStandardMetrics();
    }

    /**
     * Requête unique hautement optimisée PostgreSQL 16 (CTE + FILTER + json_agg).
     */
    private function getPostgresCteMetrics(): array
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
                WHERE deleted_at IS NULL
            ),
            org_stats AS (
                SELECT COUNT(*) AS total_organizations 
                FROM organizations 
                WHERE deleted_at IS NULL
            ),
            recent_invoices AS (
                SELECT 
                    i.id,
                    i.invoice_number,
                    i.total,
                    i.currency,
                    i.status,
                    to_char(i.issue_date, 'YYYY-MM-DD') AS issue_date,
                    to_char(i.due_date, 'YYYY-MM-DD') AS due_date,
                    o.name AS organization_name
                FROM invoices i
                JOIN organizations o ON i.organization_id = o.id
                WHERE i.deleted_at IS NULL 
                  AND o.deleted_at IS NULL
                ORDER BY i.issue_date DESC, i.id DESC
                LIMIT 6
            )
            SELECT 
                (SELECT total_invoices FROM invoice_stats) AS total_invoices,
                (SELECT total_billed FROM invoice_stats) AS total_billed,
                (SELECT total_paid FROM invoice_stats) AS total_paid,
                (SELECT total_overdue FROM invoice_stats) AS total_overdue,
                (SELECT total_organizations FROM org_stats) AS total_organizations,
                (SELECT json_agg(row_to_json(recent_invoices)) FROM recent_invoices) AS recent_invoices;
        SQL;

        $result = DB::selectOne($sql);

        $recentInvoices = [];
        if (!empty($result->recent_invoices)) {
            $recentInvoices = is_string($result->recent_invoices)
                ? json_decode($result->recent_invoices, true)
                : (array) $result->recent_invoices;
        }

        return [
            'total_billed'        => (string) ($result->total_billed ?? '0'),
            'total_paid'          => (string) ($result->total_paid ?? '0'),
            'total_overdue'       => (string) ($result->total_overdue ?? '0'),
            'total_invoices'      => (int) ($result->total_invoices ?? 0),
            'total_organizations' => (int) ($result->total_organizations ?? 0),
            'recent_invoices'     => $recentInvoices,
        ];
    }

    /**
     * Fallback standard optimisé (Query Builder sans N+1) pour SQLite ou moteurs alternatifs.
     */
    private function getStandardMetrics(): array
    {
        $paidStatus = InvoiceStatus::PAID->value;
        $overdueStatus = InvoiceStatus::OVERDUE->value;

        $stats = DB::table('invoices')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total_invoices,
                COALESCE(SUM(total), 0) as total_billed,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_overdue
            ", [$paidStatus, $overdueStatus])
            ->first();

        $orgCount = DB::table('organizations')
            ->whereNull('deleted_at')
            ->count();

        $recentInvoices = Invoice::with('organization:id,name')
            ->latest('issue_date')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id'                => $invoice->id,
                'invoice_number'    => $invoice->invoice_number,
                'total'             => (string) $invoice->total,
                'currency'          => $invoice->currency,
                'status'            => $invoice->status?->value ?? (string) $invoice->status,
                'issue_date'        => $invoice->issue_date?->format('Y-m-d'),
                'due_date'          => $invoice->due_date?->format('Y-m-d'),
                'organization_name' => $invoice->organization?->name ?? 'N/A',
            ])
            ->all();

        return [
            'total_billed'        => (string) ($stats->total_billed ?? '0'),
            'total_paid'          => (string) ($stats->total_paid ?? '0'),
            'total_overdue'       => (string) ($stats->total_overdue ?? '0'),
            'total_invoices'      => (int) ($stats->total_invoices ?? 0),
            'total_organizations' => $orgCount,
            'recent_invoices'     => $recentInvoices,
        ];
    }
}
