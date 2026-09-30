<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

use App\Enums\InvoiceStatus;
use App\Http\Requests\Admin\AdminInvoiceFilterRequest;

final readonly class AdminInvoiceFilterData
{
    public function __construct(
        public ?string $search = null,
        public ?InvoiceStatus $status = null,
        public ?int $organizationId = null,
        public int $perPage = 15,
        public int $page = 1,
        public string $sortBy = 'issue_date',
        public string $sortDirection = 'desc',
    ) {}

    /**
     * Instancie le DTO à partir de la Form Request validée.
     */
    public static function fromRequest(AdminInvoiceFilterRequest $request): self
    {
        $statusValue = $request->validated('status');
        $orgIdValue = $request->validated('organization_id');

        return new self(
            search: $request->validated('search'),
            status: $statusValue ? InvoiceStatus::tryFrom($statusValue) : null,
            organizationId: $orgIdValue !== null ? (int) $orgIdValue : null,
            perPage: (int) ($request->validated('per_page') ?? 15),
            page: (int) ($request->validated('page') ?? 1),
            sortBy: (string) ($request->validated('sort_by') ?? 'issue_date'),
            sortDirection: (string) ($request->validated('sort_direction') ?? 'desc'),
        );
    }
}
