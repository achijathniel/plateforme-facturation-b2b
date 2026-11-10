<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

/**
 * Data Transfer Object immutable encapsulant les critères de recherche et filtrage des organisations.
 */
final readonly class AdminOrganizationFilterData
{
    public function __construct(
        public ?string $search = null,
        public ?string $status = null, // 'all', 'active', 'inactive'
        public int $perPage = 15,
        public string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
    ) {}
}
