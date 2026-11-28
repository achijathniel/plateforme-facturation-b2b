<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Dashboard\DashboardFilterDTO;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Repositories\Contracts\AdminDashboardRepositoryInterface;
use App\Repositories\Eloquent\Concerns\InteractsWithDashboardFilters;
use App\Services\Dashboard\Contracts\DashboardAnalyticsFormatterInterface;
use Illuminate\Support\Facades\DB;

final class PostgresAdminDashboardRepository implements AdminDashboardRepositoryInterface
{
    use InteractsWithDashboardFilters;

    public function __construct(
        private readonly DashboardAnalyticsFormatterInterface $formatter,
    ) {}

    /**
     * Récupère les métriques financières globales, séries analytiques et dernières factures.
     * Utilise une CTE PostgreSQL 16 unique en production et un fallback SQLite pour les tests.
     */
    public function getMetrics(?DashboardFilterDTO $filter = null): array
    {
        $activeFilter = $filter ?? new DashboardFilterDTO;

        if (DB::getDriverName() === 'pgsql') {
            return $this->getPostgresCteMetrics($activeFilter);
        }

        return $this->getStandardMetrics($activeFilter);
    }

    /**
     * Requête unique optimisée PostgreSQL 16 (CTE + FILTER + json_agg) 100 % paramétrée.
     */
    private function getPostgresCteMetrics(DashboardFilterDTO $filter): array
    {
        $paidStatus = InvoiceStatus::PAID->value;
        $overdueStatus = InvoiceStatus::OVERDUE->value;
        ['whereSql' => $whereSql, 'bindings' => $whereBindings] = $this->buildPostgresWhereClause(
            filter: $filter,
            requireActiveOrganization: true
        );

        $pgDateExpr = $filter->granularity() === 'day'
            ? "to_char(i.issue_date, 'YYYY-MM-DD')"
            : "to_char(i.issue_date, 'YYYY-MM')";

        $sql = <<<SQL
            WITH invoice_stats AS (
                SELECT
                    COUNT(*) AS total_invoices,
                    COALESCE(SUM(i.total), 0) AS total_billed,
                    COALESCE(SUM(i.total) FILTER (WHERE i.status = ?), 0) AS total_paid,
                    COALESCE(SUM(i.total) FILTER (WHERE i.status = ?), 0) AS total_overdue,
                    COUNT(*) FILTER (WHERE i.status = ?) AS overdue_count
                FROM invoices i
                WHERE {$whereSql}
            ),
            org_stats AS (
                SELECT COUNT(*) AS total_organizations
                FROM organizations
                WHERE deleted_at IS NULL
            ),
            timeline_raw AS (
                SELECT
                    {$pgDateExpr} AS period_key,
                    COUNT(*) AS count,
                    COALESCE(SUM(i.total), 0) AS billed,
                    COALESCE(SUM(i.total) FILTER (WHERE i.status = ?), 0) AS paid,
                    COALESCE(SUM(i.total) FILTER (WHERE i.status = ?), 0) AS overdue
                FROM invoices i
                WHERE {$whereSql}
                GROUP BY 1
                ORDER BY 1 ASC
            ),
            status_raw AS (
                SELECT
                    i.status,
                    COUNT(*) AS count,
                    COALESCE(SUM(i.total), 0) AS amount
                FROM invoices i
                WHERE {$whereSql}
                GROUP BY i.status
            ),
            top_entities AS (
                SELECT
                    o.id,
                    o.name,
                    COUNT(*) AS invoices_count,
                    COALESCE(SUM(i.total), 0) AS total_billed,
                    COALESCE(SUM(i.total) FILTER (WHERE i.status = ?), 0) AS total_paid
                FROM invoices i
                JOIN organizations o ON i.organization_id = o.id
                WHERE {$whereSql}
                  AND o.deleted_at IS NULL
                GROUP BY o.id, o.name
                ORDER BY total_billed DESC, o.name ASC
                LIMIT 5
            ),
            recent_invoices AS (
                SELECT
                    i.id,
                    i.invoice_number,
                    i.total::text AS total,
                    i.currency,
                    i.status,
                    to_char(i.issue_date, 'YYYY-MM-DD') AS issue_date,
                    to_char(i.due_date, 'YYYY-MM-DD') AS due_date,
                    o.name AS organization_name
                FROM invoices i
                JOIN organizations o ON i.organization_id = o.id
                WHERE {$whereSql}
                  AND o.deleted_at IS NULL
                ORDER BY i.issue_date DESC, i.id DESC
                LIMIT 6
            )
            SELECT
                (SELECT total_invoices FROM invoice_stats) AS total_invoices,
                (SELECT total_billed FROM invoice_stats) AS total_billed,
                (SELECT total_paid FROM invoice_stats) AS total_paid,
                (SELECT total_overdue FROM invoice_stats) AS total_overdue,
                (SELECT overdue_count FROM invoice_stats) AS overdue_count,
                (SELECT total_organizations FROM org_stats) AS total_organizations,
                (SELECT json_agg(row_to_json(timeline_raw) ORDER BY timeline_raw.period_key ASC) FROM timeline_raw) AS timeline_raw,
                (SELECT json_agg(row_to_json(status_raw)) FROM status_raw) AS status_raw,
                (SELECT json_agg(row_to_json(top_entities) ORDER BY top_entities.total_billed DESC, top_entities.name ASC) FROM top_entities) AS top_entities,
                (SELECT json_agg(row_to_json(recent_invoices) ORDER BY recent_invoices.issue_date DESC, recent_invoices.id DESC) FROM recent_invoices) AS recent_invoices;
        SQL;

        $bindings = array_merge(
            [$paidStatus, $overdueStatus, $overdueStatus],
            $whereBindings,
            [$paidStatus, $overdueStatus],
            $whereBindings,
            $whereBindings,
            [$paidStatus],
            $whereBindings,
            $whereBindings
        );

        $result = DB::selectOne($sql, $bindings);

        return $this->assembleMetricsPayload(
            formatter: $this->formatter,
            filter: $filter,
            totalBilled: (string) ($result->total_billed ?? '0'),
            totalPaid: (string) ($result->total_paid ?? '0'),
            totalOverdue: (string) ($result->total_overdue ?? '0'),
            overdueCount: (int) ($result->overdue_count ?? 0),
            totalInvoices: (int) ($result->total_invoices ?? 0),
            timelineRaw: $this->decodeJsonArray($result->timeline_raw ?? null),
            statusRaw: $this->decodeJsonArray($result->status_raw ?? null),
            topEntitiesRaw: $this->decodeJsonArray($result->top_entities ?? null),
            recentInvoices: $this->decodeJsonArray($result->recent_invoices ?? null),
            totalOrganizations: (int) ($result->total_organizations ?? 0),
        );
    }

    /**
     * Fallback standard (Query Builder sans N+1) compatible SQLite et tests PHPUnit.
     */
    private function getStandardMetrics(DashboardFilterDTO $filter): array
    {
        $paidStatus = InvoiceStatus::PAID->value;
        $aggregates = $this->fetchStandardAggregates($filter, requireActiveOrganization: true);
        $kpi = $aggregates['kpi'];

        $orgCount = DB::table('organizations')
            ->whereNull('deleted_at')
            ->count();

        $topEntitiesRaw = $this->applyStandardInvoiceFilters(
            DB::table('invoices')->join('organizations', 'invoices.organization_id', '=', 'organizations.id'),
            $filter
        )
            ->whereNull('organizations.deleted_at')
            ->selectRaw(
                'organizations.id,
                organizations.name,
                COUNT(*) as invoices_count,
                COALESCE(SUM(invoices.total), 0) as total_billed,
                COALESCE(SUM(CASE WHEN invoices.status = ? THEN invoices.total ELSE 0 END), 0) as total_paid',
                [$paidStatus]
            )
            ->groupBy('organizations.id', 'organizations.name')
            ->orderByDesc('total_billed')
            ->orderBy('organizations.name')
            ->limit(5)
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        $recentQuery = Invoice::with('organization:id,name')->whereHas('organization');
        if ($filter->organizationId !== null) {
            $recentQuery->where('organization_id', $filter->organizationId);
        }
        if ($filter->startDate() !== null) {
            $recentQuery->where('issue_date', '>=', $filter->startDate()->toDateString());
        }
        if ($filter->status !== null) {
            $recentQuery->where('status', $filter->status);
        }

        $recentInvoices = $recentQuery
            ->latest('issue_date')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => (string) $invoice->total,
                'currency' => $invoice->currency,
                'status' => $invoice->status?->value ?? (string) $invoice->status,
                'issue_date' => $invoice->issue_date?->format('Y-m-d'),
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'organization_name' => $invoice->organization?->name ?? 'N/A',
            ])
            ->all();

        return $this->assembleMetricsPayload(
            formatter: $this->formatter,
            filter: $filter,
            totalBilled: (string) ($kpi->total_billed ?? '0'),
            totalPaid: (string) ($kpi->total_paid ?? '0'),
            totalOverdue: (string) ($kpi->total_overdue ?? '0'),
            overdueCount: (int) ($kpi->overdue_count ?? 0),
            totalInvoices: (int) ($kpi->total_invoices ?? 0),
            timelineRaw: $aggregates['timeline_raw'],
            statusRaw: $aggregates['status_raw'],
            topEntitiesRaw: $topEntitiesRaw,
            recentInvoices: $recentInvoices,
            totalOrganizations: $orgCount,
        );
    }
}
