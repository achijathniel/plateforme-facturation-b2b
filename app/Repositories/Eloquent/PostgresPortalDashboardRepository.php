<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Dashboard\DashboardFilterDTO;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Repositories\Contracts\PortalDashboardRepositoryInterface;
use App\Repositories\Eloquent\Concerns\InteractsWithDashboardFilters;
use App\Services\Dashboard\Contracts\DashboardAnalyticsFormatterInterface;
use Illuminate\Support\Facades\DB;

final class PostgresPortalDashboardRepository implements PortalDashboardRepositoryInterface
{
    use InteractsWithDashboardFilters;

    public function __construct(
        private readonly DashboardAnalyticsFormatterInterface $formatter,
    ) {}

    /**
     * Récupère les métriques financières, séries analytiques et dernières factures d'une organisation.
     */
    public function getMetricsForOrganization(int $organizationId, ?DashboardFilterDTO $filter = null): array
    {
        $activeFilter = $filter ?? new DashboardFilterDTO;

        if (DB::getDriverName() === 'pgsql') {
            return $this->getPostgresCteMetrics($organizationId, $activeFilter);
        }

        return $this->getStandardMetrics($organizationId, $activeFilter);
    }

    /**
     * Requête unique optimisée PostgreSQL 16 (CTE + FILTER + json_agg) isolée par organization_id.
     */
    private function getPostgresCteMetrics(int $organizationId, DashboardFilterDTO $filter): array
    {
        $paidStatus = InvoiceStatus::PAID->value;
        $overdueStatus = InvoiceStatus::OVERDUE->value;
        ['whereSql' => $whereSql, 'bindings' => $whereBindings] = $this->buildPostgresWhereClause($filter, $organizationId);

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
                    MIN(i.client_id) AS id,
                    COALESCE(NULLIF(TRIM(i.client_name), ''), 'Client divers') AS name,
                    COUNT(*) AS invoices_count,
                    COALESCE(SUM(i.total), 0) AS total_billed,
                    COALESCE(SUM(i.total) FILTER (WHERE i.status = ?), 0) AS total_paid
                FROM invoices i
                WHERE {$whereSql}
                GROUP BY 2
                ORDER BY total_billed DESC, 2 ASC
                LIMIT 5
            ),
            recent_invoices AS (
                SELECT
                    i.id,
                    i.invoice_number,
                    i.client_name,
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
        );
    }

    /**
     * Fallback standard (Query Builder) compatible SQLite et multi-driver.
     */
    private function getStandardMetrics(int $organizationId, DashboardFilterDTO $filter): array
    {
        $paidStatus = InvoiceStatus::PAID->value;
        $aggregates = $this->fetchStandardAggregates($filter, $organizationId);
        $kpi = $aggregates['kpi'];

        $clientNameExpr = "COALESCE(NULLIF(TRIM(client_name), ''), 'Client divers')";

        $topEntitiesRaw = $this->applyStandardInvoiceFilters(DB::table('invoices'), $filter, 'invoices', $organizationId)
            ->selectRaw(
                "MIN(client_id) as id,
                {$clientNameExpr} as name,
                COUNT(*) as invoices_count,
                COALESCE(SUM(total), 0) as total_billed,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_paid",
                [$paidStatus]
            )
            ->groupByRaw($clientNameExpr)
            ->orderByDesc('total_billed')
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        $recentQuery = Invoice::with('organization:id,name')
            ->where('organization_id', $organizationId);

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
                'client_name' => $invoice->client_name,
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
        );
    }
}
