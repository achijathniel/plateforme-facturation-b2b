<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Concerns;

use App\DTOs\Dashboard\DashboardFilterDTO;
use App\Enums\InvoiceStatus;
use App\Services\Dashboard\Contracts\DashboardAnalyticsFormatterInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

trait InteractsWithDashboardFilters
{
    /**
     * Construit la clause WHERE paramétrée commune aux CTE PostgreSQL.
     *
     * @return array{whereSql: string, bindings: array<int, mixed>}
     */
    protected function buildPostgresWhereClause(
        DashboardFilterDTO $filter,
        ?int $forcedOrganizationId = null,
        bool $requireActiveOrganization = false
    ): array {
        $conditions = ['i.deleted_at IS NULL'];
        $bindings = [];

        if ($requireActiveOrganization) {
            $conditions[] = 'EXISTS (SELECT 1 FROM organizations o_active WHERE o_active.id = i.organization_id AND o_active.deleted_at IS NULL)';
        }

        $organizationId = $forcedOrganizationId ?? $filter->organizationId;
        if ($organizationId !== null) {
            $conditions[] = 'i.organization_id = ?';
            $bindings[] = $organizationId;
        }

        $startDate = $filter->startDate();
        if ($startDate !== null) {
            $conditions[] = 'i.issue_date >= ?';
            $bindings[] = $startDate->toDateString();
        }

        if ($filter->status !== null) {
            $conditions[] = 'i.status = ?';
            $bindings[] = $filter->status->value;
        }

        return [
            'whereSql' => implode(' AND ', $conditions),
            'bindings' => $bindings,
        ];
    }

    /**
     * Applique les filtres standards sur un Query Builder multi-driver (SQLite / PostgreSQL).
     */
    protected function applyStandardInvoiceFilters(
        Builder $query,
        DashboardFilterDTO $filter,
        string $tableAlias = 'invoices',
        ?int $forcedOrganizationId = null,
        bool $requireActiveOrganization = false
    ): Builder {
        if (! in_array($tableAlias, ['invoices'], true)) {
            throw new InvalidArgumentException("Alias de table non autorisé : {$tableAlias}");
        }

        $query->whereNull("{$tableAlias}.deleted_at");

        if ($requireActiveOrganization) {
            $query->whereExists(function (Builder $sub) use ($tableAlias): void {
                $sub->selectRaw('1')
                    ->from('organizations')
                    ->whereColumn('organizations.id', "{$tableAlias}.organization_id")
                    ->whereNull('organizations.deleted_at');
            });
        }

        $organizationId = $forcedOrganizationId ?? $filter->organizationId;
        if ($organizationId !== null) {
            $query->where("{$tableAlias}.organization_id", $organizationId);
        }

        $startDate = $filter->startDate();
        if ($startDate !== null) {
            $query->where("{$tableAlias}.issue_date", '>=', $startDate->toDateString());
        }

        if ($filter->status !== null) {
            $query->where("{$tableAlias}.status", $filter->status->value);
        }

        return $query;
    }

    /**
     * Agrège les métriques KPI et séries brutes via Query Builder (fallback SQLite / multi-driver).
     *
     * @return array{
     *     kpi: object|null,
     *     timeline_raw: array<int, array<string, mixed>>,
     *     status_raw: array<int, array<string, mixed>>
     * }
     */
    protected function fetchStandardAggregates(
        DashboardFilterDTO $filter,
        ?int $forcedOrganizationId = null,
        bool $requireActiveOrganization = false
    ): array {
        $paidStatus = InvoiceStatus::PAID->value;
        $overdueStatus = InvoiceStatus::OVERDUE->value;

        $kpi = $this->applyStandardInvoiceFilters(DB::table('invoices'), $filter, 'invoices', $forcedOrganizationId, $requireActiveOrganization)
            ->selectRaw(
                'COUNT(*) as total_invoices,
                COALESCE(SUM(total), 0) as total_billed,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_overdue,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as overdue_count',
                [$paidStatus, $overdueStatus, $overdueStatus]
            )
            ->first();

        $dateFormat = $filter->granularity() === 'day' ? '%Y-%m-%d' : '%Y-%m';
        $periodExpr = DB::getDriverName() === 'pgsql'
            ? ($filter->granularity() === 'day' ? "to_char(issue_date, 'YYYY-MM-DD')" : "to_char(issue_date, 'YYYY-MM')")
            : "strftime('{$dateFormat}', issue_date)";

        $timelineRaw = $this->applyStandardInvoiceFilters(DB::table('invoices'), $filter, 'invoices', $forcedOrganizationId, $requireActiveOrganization)
            ->selectRaw(
                "{$periodExpr} as period_key,
                COUNT(*) as count,
                COALESCE(SUM(total), 0) as billed,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as paid,
                COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as overdue",
                [$paidStatus, $overdueStatus]
            )
            ->groupByRaw($periodExpr)
            ->orderByRaw("{$periodExpr} asc")
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        $statusRaw = $this->applyStandardInvoiceFilters(DB::table('invoices'), $filter, 'invoices', $forcedOrganizationId, $requireActiveOrganization)
            ->selectRaw('status, COUNT(*) as count, COALESCE(SUM(total), 0) as amount')
            ->groupBy('status')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();

        return [
            'kpi' => $kpi,
            'timeline_raw' => $timelineRaw,
            'status_raw' => $statusRaw,
        ];
    }

    /**
     * Décode une colonne json_agg PostgreSQL en tableau PHP sûr.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function decodeJsonArray(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return (array) $value;
    }

    /**
     * Normalise les lignes du classement Top 5 pour le frontend.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{id: int|null, name: string, invoices_count: int, total_billed: float, total_paid: float}>
     */
    protected function formatTopEntities(array $rows): array
    {
        return array_map(
            static fn (array $row): array => [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'name' => (string) ($row['name'] ?? 'N/A'),
                'invoices_count' => (int) ($row['invoices_count'] ?? 0),
                'total_billed' => round((float) ($row['total_billed'] ?? 0), 2),
                'total_paid' => round((float) ($row['total_paid'] ?? 0), 2),
            ],
            $rows
        );
    }

    /**
     * Assemble le tableau final de métriques et séries analytiques via DashboardAnalyticsFormatterInterface.
     *
     * @param  array<int, array<string, mixed>>  $timelineRaw
     * @param  array<int, array<string, mixed>>  $statusRaw
     * @param  array<int, array<string, mixed>>  $topEntitiesRaw
     * @param  array<int, array<string, mixed>>  $recentInvoices
     * @return array<string, mixed>
     */
    protected function assembleMetricsPayload(
        DashboardAnalyticsFormatterInterface $formatter,
        DashboardFilterDTO $filter,
        string $totalBilled,
        string $totalPaid,
        string $totalOverdue,
        int $overdueCount,
        int $totalInvoices,
        array $timelineRaw,
        array $statusRaw,
        array $topEntitiesRaw,
        array $recentInvoices,
        ?int $totalOrganizations = null
    ): array {
        $payload = [
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'total_overdue' => $totalOverdue,
            'overdue_count' => $overdueCount,
            'total_invoices' => $totalInvoices,
            'collection_rate' => $formatter->computeCollectionRate($totalBilled, $totalPaid),
            'average_invoice_amount' => $formatter->computeAverageInvoiceAmount($totalBilled, $totalInvoices),
            'monthly_series' => $formatter->buildTimelineSeries($filter, $timelineRaw),
            'status_breakdown' => $formatter->buildStatusBreakdown($statusRaw),
            'top_entities' => $this->formatTopEntities($topEntitiesRaw),
            'recent_invoices' => $recentInvoices,
        ];

        if ($totalOrganizations !== null) {
            $payload['total_organizations'] = $totalOrganizations;
        }

        return $payload;
    }
}
