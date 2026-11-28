<?php

declare(strict_types=1);

namespace App\DTOs\Clients;

final readonly class ClientFilterData
{
    private const ALLOWED_SORT_FIELDS = [
        'name',
        'created_at',
        'invoices_count',
        'total_billed',
    ];

    public function __construct(
        public ?string $search = null,
        public string $sortBy = 'name',
        public string $sortDirection = 'asc',
        public int $perPage = 15,
    ) {}

    /**
     * Construit un DTO de filtrage validé à partir des paramètres de requête.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $rawSearch = isset($data['search']) && is_string($data['search'])
            ? trim($data['search'])
            : null;

        $sortBy = isset($data['sort_by']) && is_string($data['sort_by']) && in_array($data['sort_by'], self::ALLOWED_SORT_FIELDS, true)
            ? $data['sort_by']
            : 'name';

        $rawDirection = isset($data['sort_direction']) && is_string($data['sort_direction'])
            ? strtolower($data['sort_direction'])
            : 'asc';
        $sortDirection = in_array($rawDirection, ['asc', 'desc'], true) ? $rawDirection : 'asc';

        $perPage = isset($data['per_page']) ? (int) $data['per_page'] : 15;
        $perPage = max(5, min(50, $perPage));

        return new self(
            search: $rawSearch !== '' ? $rawSearch : null,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            perPage: $perPage,
        );
    }
}
