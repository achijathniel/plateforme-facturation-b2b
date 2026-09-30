<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\GetAdminDashboardStatsAction;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Affiche le tableau de bord financier de l'administration.
     */
    public function index(GetAdminDashboardStatsAction $action): Response
    {
        $stats = $action->execute();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats->toArray(),
        ]);
    }
}
