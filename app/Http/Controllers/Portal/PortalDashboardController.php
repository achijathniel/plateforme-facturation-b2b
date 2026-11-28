<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\GetPortalDashboardStatsAction;
use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PortalDashboardFilterRequest;
use Inertia\Inertia;
use Inertia\Response;

final class PortalDashboardController extends Controller
{
    /**
     * Affiche le tableau de bord interactif de l'entreprise cliente.
     */
    public function index(PortalDashboardFilterRequest $request, GetPortalDashboardStatsAction $action): Response
    {
        $user = $request->user();
        $user->loadMissing('organization:id,name');

        $organizationId = (int) $user->organization_id;
        $filter = $request->toDTO();
        $stats = $action->execute($organizationId, $filter);

        return Inertia::render('Portal/Dashboard', [
            'stats' => $stats->toArray(),
            'filters' => $filter->toArray(),
            'organization' => [
                'id' => $user->organization?->id ?? $organizationId,
                'name' => $user->organization?->name ?? 'Mon Entreprise',
            ],
            'statuses' => InvoiceStatus::toSelectOptions(),
        ]);
    }
}
