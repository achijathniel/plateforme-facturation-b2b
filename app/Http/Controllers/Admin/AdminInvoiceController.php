<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\GetAdminInvoicesAction;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminInvoiceFilterRequest;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

final class AdminInvoiceController extends Controller
{
    /**
     * Affiche la liste paginée et filtrée des factures de la plateforme.
     * Respecte le pattern Skinny Controller (délégation stricte à la Form Request et aux Repositories/Actions).
     */
    public function index(
        AdminInvoiceFilterRequest $request,
        GetAdminInvoicesAction $action,
        OrganizationRepositoryInterface $organizationRepository
    ): Response {
        $filterData = $request->toDTO();
        $invoices = $action->execute($filterData);
        $organizations = $organizationRepository->getAllForSelect();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices'      => $invoices,
            'organizations' => $organizations->map(fn ($org) => [
                'id'   => $org->id,
                'name' => $org->name,
            ]),
            'filters'  => [
                'search'          => $filterData->search,
                'status'          => $filterData->status?->value,
                'organization_id' => $filterData->organizationId,
                'per_page'        => $filterData->perPage,
                'sort_by'         => $filterData->sortBy,
                'sort_direction'  => $filterData->sortDirection,
            ],
            'statuses' => collect(InvoiceStatus::cases())->map(fn (InvoiceStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]),
        ]);
    }
}
