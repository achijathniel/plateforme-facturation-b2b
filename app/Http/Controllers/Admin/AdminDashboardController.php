<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\GetAdminDashboardStatsAction;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminDashboardFilterRequest;
use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

final class AdminDashboardController extends Controller
{
    /**
     * Affiche le tableau de bord financier interactif de l'administration.
     */
    public function index(
        AdminDashboardFilterRequest $request,
        GetAdminDashboardStatsAction $action,
        OrganizationRepositoryInterface $organizationRepository
    ): Response {
        $filter = $request->toDTO();
        $stats = $action->execute($filter);
        $organizations = $organizationRepository->getAllForSelect();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats->toArray(),
            'filters' => $filter->toArray(),
            'organizations' => $organizations->map(static fn (Organization $org): array => [
                'id' => $org->id,
                'name' => $org->name,
            ]),
            'statuses' => InvoiceStatus::toSelectOptions(),
        ]);
    }
}
