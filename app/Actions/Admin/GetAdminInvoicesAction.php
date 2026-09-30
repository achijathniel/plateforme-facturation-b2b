<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\AdminInvoiceFilterData;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetAdminInvoicesAction
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    /**
     * Exécute la récupération paginée des factures selon les critères du filtre.
     */
    public function execute(AdminInvoiceFilterData $filter): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginateWithFilters($filter);
    }
}
