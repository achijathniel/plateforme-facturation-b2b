<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\DTOs\Portal\PortalInvoiceFilterData;
use App\Models\User;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class GetPortalInvoicesAction
{
    public function __construct(
        private InvoiceRepositoryInterface $invoiceRepository
    ) {}

    /**
     * Récupère la liste paginée et filtrée des factures pour l'organisation de l'utilisateur connecté.
     */
    public function execute(User $user, PortalInvoiceFilterData $filter): LengthAwarePaginator
    {
        return $this->invoiceRepository->paginateForUserWithFilters($user, $filter);
    }
}
