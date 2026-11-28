<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\DashboardFilterDTO;
use App\Enums\InvoiceStatus;
use App\Services\Dashboard\Contracts\DashboardAnalyticsFormatterInterface;
use Carbon\CarbonImmutable;
use Throwable;

final class DashboardAnalyticsFormatter implements DashboardAnalyticsFormatterInterface
{
    private const DAYS_IN_30D_OFFSET = 29;

    private const DEFAULT_MONTHS_OFFSET = 11;

    private const MAX_ALL_MONTHS_OFFSET = 23;

    private const FRENCH_SHORT_MONTHS = [
        1 => 'Jan',
        2 => 'Fév',
        3 => 'Mar',
        4 => 'Avr',
        5 => 'Mai',
        6 => 'Juin',
        7 => 'Juil',
        8 => 'Août',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Déc',
    ];

    /**
     * {@inheritDoc}
     */
    public function buildTimelineSeries(DashboardFilterDTO $filter, array $rawRows): array
    {
        $indexed = [];
        foreach ($rawRows as $row) {
            $key = (string) ($row['period_key'] ?? '');
            if ($key !== '') {
                $indexed[$key] = $row;
            }
        }

        if ($filter->granularity() === 'day') {
            return $this->buildDailySeries($filter, $indexed);
        }

        return $this->buildMonthlySeries($filter, $indexed);
    }

    /**
     * {@inheritDoc}
     */
    public function buildStatusBreakdown(array $rawStatusRows): array
    {
        $indexed = [];
        foreach ($rawStatusRows as $row) {
            $statusKey = (string) ($row['status'] ?? '');
            if ($statusKey !== '') {
                $indexed[$statusKey] = $row;
            }
        }

        $ordered = [
            InvoiceStatus::PAID,
            InvoiceStatus::SENT,
            InvoiceStatus::OVERDUE,
            InvoiceStatus::DRAFT,
        ];

        $result = [];
        foreach ($ordered as $status) {
            $row = $indexed[$status->value] ?? null;
            $result[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($row['count'] ?? 0),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
            ];
        }

        $cancelledRow = $indexed[InvoiceStatus::CANCELLED->value] ?? null;
        if ($cancelledRow !== null && (int) ($cancelledRow['count'] ?? 0) > 0) {
            $result[] = [
                'status' => InvoiceStatus::CANCELLED->value,
                'label' => InvoiceStatus::CANCELLED->label(),
                'count' => (int) $cancelledRow['count'],
                'amount' => round((float) ($cancelledRow['amount'] ?? 0), 2),
            ];
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function computeCollectionRate(string $totalBilled, string $totalPaid): float
    {
        $billed = (float) $totalBilled;
        if ($billed <= 0.0) {
            return 0.0;
        }

        return round(((float) $totalPaid / $billed) * 100, 1);
    }

    /**
     * {@inheritDoc}
     */
    public function computeAverageInvoiceAmount(string $totalBilled, int $totalCount): string
    {
        if ($totalCount <= 0) {
            return '0.00';
        }

        return number_format((float) $totalBilled / $totalCount, 2, '.', '');
    }

    /**
     * @param  array<string, array<string, mixed>>  $indexed
     * @return array<int, array{key: string, label: string, billed: float, paid: float, overdue: float, count: int}>
     */
    private function buildDailySeries(DashboardFilterDTO $filter, array $indexed): array
    {
        $start = ($filter->startDate() ?? CarbonImmutable::now()->subDays(self::DAYS_IN_30D_OFFSET))->startOfDay();
        $end = CarbonImmutable::now()->startOfDay();
        $series = [];

        for ($cursor = $start; $cursor->lessThanOrEqualTo($end); $cursor = $cursor->addDay()) {
            $key = $cursor->format('Y-m-d');
            $series[] = $this->formatTimelinePoint($key, $cursor->format('d/m'), $indexed[$key] ?? null);
        }

        return $series;
    }

    /**
     * @param  array<string, array<string, mixed>>  $indexed
     * @return array<int, array{key: string, label: string, billed: float, paid: float, overdue: float, count: int}>
     */
    private function buildMonthlySeries(DashboardFilterDTO $filter, array $indexed): array
    {
        $end = CarbonImmutable::now()->startOfMonth();
        $startDate = $filter->startDate();

        if ($startDate !== null) {
            $start = $startDate->startOfMonth();
        } elseif ($indexed !== []) {
            $keys = array_keys($indexed);
            sort($keys, SORT_STRING);
            $earliest = $this->parseMonthKey($keys[0], $end->subMonthsNoOverflow(self::DEFAULT_MONTHS_OFFSET));
            $minStart = $end->subMonthsNoOverflow(self::MAX_ALL_MONTHS_OFFSET);
            $start = $earliest->lessThan($minStart) ? $minStart : $earliest->startOfMonth();
        } else {
            $start = $end->subMonthsNoOverflow(self::DEFAULT_MONTHS_OFFSET);
        }

        $series = [];
        for ($cursor = $start; $cursor->lessThanOrEqualTo($end); $cursor = $cursor->addMonthNoOverflow()) {
            $key = $cursor->format('Y-m');
            $monthName = self::FRENCH_SHORT_MONTHS[(int) $cursor->format('n')] ?? $cursor->format('m');
            $label = "{$monthName} {$cursor->format('y')}";
            $series[] = $this->formatTimelinePoint($key, $label, $indexed[$key] ?? null);
        }

        return $series;
    }

    /**
     * Parse une clé mensuelle YYYY-MM sans réutiliser le jour courant (préfixe '!') pour éviter tout overflow.
     */
    private function parseMonthKey(string|int $key, CarbonImmutable $fallback): CarbonImmutable
    {
        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m', (string) $key);

            return $parsed !== false ? $parsed->startOfMonth() : $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * @param  array<string, mixed>|null  $row
     * @return array{key: string, label: string, billed: float, paid: float, overdue: float, count: int}
     */
    private function formatTimelinePoint(string $key, string $label, ?array $row): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'billed' => round((float) ($row['billed'] ?? 0), 2),
            'paid' => round((float) ($row['paid'] ?? 0), 2),
            'overdue' => round((float) ($row['overdue'] ?? 0), 2),
            'count' => (int) ($row['count'] ?? 0),
        ];
    }
}
