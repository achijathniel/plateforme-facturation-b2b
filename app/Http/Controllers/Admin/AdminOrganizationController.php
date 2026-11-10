<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateOrganizationAction;
use App\Actions\Admin\ToggleOrganizationStatusAction;
use App\Actions\Admin\UpdateOrganizationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOrganizationFilterRequest;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class AdminOrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository
    ) {}

    /**
     * Affiche la liste paginée et filtrée des entreprises de la plateforme.
     */
    public function index(AdminOrganizationFilterRequest $request): Response
    {
        $filterData = $request->toDTO();
        $organizations = $this->organizationRepository->paginateWithFilters($filterData);

        return Inertia::render('Admin/Organizations/Index', [
            'organizations' => $organizations,
            'filters' => [
                'search'         => $filterData->search,
                'status'         => $filterData->status ?? 'all',
                'per_page'       => $filterData->perPage,
                'sort_by'        => $filterData->sortBy,
                'sort_direction' => $filterData->sortDirection,
            ],
        ]);
    }

    /**
     * Affiche le formulaire de création d'une nouvelle entreprise.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Organizations/Create');
    }

    /**
     * Enregistre une nouvelle entreprise en base de données.
     */
    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $action): RedirectResponse
    {
        $organization = $action->execute($request->toDTO());

        return redirect()
            ->route('admin.organizations.show', $organization->id)
            ->with('success', "L'entreprise « {$organization->name} » a été créée avec succès.");
    }

    /**
     * Affiche la fiche détaillée d'une entreprise (vue 360°).
     */
    public function show(int $id): Response
    {
        $organization = $this->organizationRepository->findByIdWithDetails($id);

        if (! $organization) {
            abort(404, 'Entreprise introuvable.');
        }

        return Inertia::render('Admin/Organizations/Show', [
            'organization' => $organization,
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'une entreprise existante.
     */
    public function edit(int $id): Response
    {
        $organization = $this->organizationRepository->findByIdWithDetails($id);

        if (! $organization) {
            abort(404, 'Entreprise introuvable.');
        }

        return Inertia::render('Admin/Organizations/Edit', [
            'organization' => $organization,
        ]);
    }

    /**
     * Met à jour les informations d'une entreprise.
     */
    public function update(
        int $id,
        UpdateOrganizationRequest $request,
        UpdateOrganizationAction $action
    ): RedirectResponse {
        $organization = $this->organizationRepository->findByIdWithDetails($id);

        if (! $organization) {
            abort(404, 'Entreprise introuvable.');
        }

        $updated = $action->execute($organization, $request->toDTO());

        return redirect()
            ->route('admin.organizations.show', $updated->id)
            ->with('success', "Les informations de « {$updated->name} » ont été mises à jour avec succès.");
    }

    /**
     * Active ou suspend l'accès d'une entreprise.
     */
    public function toggleStatus(int $id, ToggleOrganizationStatusAction $action): RedirectResponse
    {
        $organization = $this->organizationRepository->findByIdWithDetails($id);

        if (! $organization) {
            abort(404, 'Entreprise introuvable.');
        }

        $updated = $action->execute($organization);

        $statusLabel = $updated->is_active ? 'activée' : 'suspendue';

        return redirect()
            ->back()
            ->with('success', "L'entreprise « {$updated->name} » est désormais {$statusLabel}.");
    }
}
