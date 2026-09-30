<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\GetPortalDashboardStatsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PortalDashboardController extends Controller
{
    /**
     * Affiche le tableau de bord de l'entreprise cliente avec ses métriques et factures récentes.
     */
    public function index(Request $request, GetPortalDashboardStatsAction $action): Response
    {
        $organizationId = (int) $request->user()->organization_id;
        $stats = $action->execute($organizationId);

        return Inertia::render('Portal/Dashboard', [
            'stats'        => $stats->toArray(),
            'organization' => [
                'id'   => $request->user()->organization?->id ?? $organizationId,
                'name' => $request->user()->organization?->name ?? 'Mon Entreprise',
            ],
        ]);
    }
}
