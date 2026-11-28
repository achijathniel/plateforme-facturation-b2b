<?php

declare(strict_types=1);

namespace App\DTOs\Dashboard;

use App\Enums\InvoiceStatus;
use Carbon\CarbonImmutable;

final readonly class DashboardFilterDTO
{
    public const DEFAULT_PERIOD = '12m';

    /** @var array<int, string> */
    public const PERIODS = ['30d', '90d', '6m', '12m', 'all'];

    public function __construct(
        public string $period = self::DEFAULT_PERIOD,
        public ?InvoiceStatus $status = null,
        public ?int $organizationId = null,
    ) {}

    /**
     * Construit le DTO à partir des paramètres validés par une Form Request.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated, bool $allowOrganization = false): self
    {
        return new self(
            period: (string) ($validated['period'] ?? self::DEFAULT_PERIOD),
            status: isset($validated['status']) ? InvoiceStatus::from((string) $validated['status']) : null,
            organizationId: $allowOrganization && isset($validated['organization_id'])
                ? (int) $validated['organization_id']
                : null,
        );
    }

    /**
     * Indique si le filtre correspond exactement à la configuration par défaut (éligible au cache).
     */
    public function isDefault(): bool
    {
        return $this->period === self::DEFAULT_PERIOD
            && $this->status === null
            && $this->organizationId === null;
    }

    /**
     * Retourne la date de début (incluse) associée à la période sélectionnée, ou null pour "all".
     * Appelle startOfMonth() avant la soustraction pour éviter tout overflow en fin de mois (ex. 31 mars).
     */
    public function startDate(): ?CarbonImmutable
    {
        $now = CarbonImmutable::now();

        return match ($this->period) {
            '30d' => $now->subDays(29)->startOfDay(),
            '90d' => $now->subDays(89)->startOfDay(),
            '6m' => $now->startOfMonth()->subMonthsNoOverflow(5),
            '12m' => $now->startOfMonth()->subMonthsNoOverflow(11),
            default => null,
        };
    }

    /**
     * Granularité temporelle de la courbe d'évolution ('day' sur 30 jours, 'month' au-delà).
     */
    public function granularity(): string
    {
        return $this->period === '30d' ? 'day' : 'month';
    }

    /**
     * Sérialisation normalisée des filtres actifs pour Inertia.
     *
     * @return array{period: string, status: string|null, organization_id: int|null}
     */
    public function toArray(): array
    {
        return [
            'period' => $this->period,
            'status' => $this->status?->value,
            'organization_id' => $this->organizationId,
        ];
    }
}
