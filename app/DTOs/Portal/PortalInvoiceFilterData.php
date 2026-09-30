<?php

declare(strict_types=1);

namespace App\DTOs\Portal;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Portal\PortalInvoiceFilterRequest;

final readonly class PortalInvoiceFilterData
{
    public function __construct(
        public ?string $search = null,
        public ?InvoiceStatus $status = null,
        public int $perPage = 15,
        public int $page = 1,
        public string $sortBy = 'issue_date',
        public string $sortDirection = 'desc',
    ) {}

    /**
     * Construit le DTO à partir des données validées de la Form Request.
     */
    public static function fromRequest(PortalInvoiceFilterRequest $request): self
    {
        $statusValue = $request->validated('status');

        return new self(
            search: $request->validated('search'),
            status: $statusValue ? InvoiceStatus::tryFrom($statusValue) : null,
            perPage: (int) ($request->validated('per_page') ?? 15),
            page: (int) ($request->validated('page') ?? 1),
            sortBy: (string) ($request->validated('sort_by') ?? 'issue_date'),
            sortDirection: (string) ($request->validated('sort_direction') ?? 'desc'),
        );
    }
}
