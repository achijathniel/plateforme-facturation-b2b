<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Clients\CreateClientAction;
use App\Actions\Clients\DeleteClientAction;
use App\Actions\Clients\GetClientDetailsAction;
use App\Actions\Clients\ListOrganizationClientsAction;
use App\Actions\Clients\UpdateClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\PortalClientFilterRequest;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class PortalClientController extends Controller
{
    /**
     * Affiche l'annuaire paginé et filtrable des clients de l'entreprise.
     */
    public function index(
        PortalClientFilterRequest $request,
        ListOrganizationClientsAction $action
    ): Response {
        $filterData = $request->toDTO();
        $clients = $action->execute($request->user(), $filterData);

        return Inertia::render('Portal/Clients/Index', [
            'clients' => $clients,
            'organization' => $this->organizationPayload($request),
            'filters' => [
                'search' => $filterData->search,
                'sort_by' => $filterData->sortBy,
                'sort_direction' => $filterData->sortDirection,
                'per_page' => $filterData->perPage,
            ],
            'canManageClients' => $request->user()->can('create', Client::class),
        ]);
    }

    /**
     * Affiche le formulaire de création d'un client B2B.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Client::class);

        return Inertia::render('Portal/Clients/Create', [
            'organization' => $this->organizationPayload($request),
        ]);
    }

    /**
     * Recherche instantanée de clients pour l'autocomplétion du formulaire de facturation.
     */
    public function search(
        Request $request,
        ClientRepositoryInterface $clientRepository
    ): JsonResponse {
        Gate::authorize('viewAny', Client::class);

        $organizationId = (int) $request->user()->organization_id;
        $term = $request->query('q');

        $clients = $clientRepository->search(
            $organizationId,
            is_string($term) ? $term : null,
            15
        );

        return response()->json([
            'clients' => $clients->map(fn (Client $c) => $this->formatClientSummary($c)),
        ]);
    }

    /**
     * Enregistre un nouveau client dans l'annuaire de l'entreprise (JSON autocomplétion ou Inertia).
     */
    public function store(
        StoreClientRequest $request,
        CreateClientAction $createClientAction
    ): JsonResponse|RedirectResponse {
        $client = $createClientAction->execute($request->user(), $request->toDTO());

        if (! $request->header('X-Inertia') && ($request->wantsJson() || $request->ajax())) {
            return response()->json([
                'status' => 'success',
                'message' => 'Client enregistré avec succès.',
                'client' => $this->formatClientSummary($client),
            ], 201);
        }

        return redirect()
            ->route('portal.clients.show', $client->id)
            ->with('success', sprintf("Le client %s a été ajouté à votre carnet d'adresses.", $client->name));
    }

    /**
     * Affiche la fiche 360° d'un client avec ses indicateurs et son historique de facturation.
     */
    public function show(
        int $id,
        Request $request,
        GetClientDetailsAction $action
    ): Response {
        $client = $action->execute($request->user(), $id);

        if (! $client) {
            abort(404, 'Client introuvable.');
        }

        return Inertia::render('Portal/Clients/Show', [
            'client' => $client,
            'organization' => $this->organizationPayload($request),
            'canManageClients' => $request->user()->can('update', $client),
        ]);
    }

    /**
     * Affiche le formulaire d'édition d'un client existant.
     */
    public function edit(
        int $id,
        Request $request,
        ClientRepositoryInterface $clientRepository
    ): Response {
        $client = $this->findClientOrFail($id, $request, $clientRepository);
        Gate::authorize('update', $client);

        return Inertia::render('Portal/Clients/Edit', [
            'client' => $client,
            'organization' => $this->organizationPayload($request),
        ]);
    }

    /**
     * Met à jour les coordonnées d'un client existant.
     */
    public function update(
        int $id,
        UpdateClientRequest $request,
        ClientRepositoryInterface $clientRepository,
        UpdateClientAction $action
    ): RedirectResponse {
        $client = $this->findClientOrFail($id, $request, $clientRepository);
        $updated = $action->execute($request->user(), $client, $request->toDTO());

        return redirect()
            ->route('portal.clients.show', $updated->id)
            ->with('success', sprintf('Les informations du client %s ont été mises à jour.', $updated->name));
    }

    /**
     * Archive un client (suppression douce) sans affecter ses factures historiques.
     */
    public function destroy(
        int $id,
        Request $request,
        ClientRepositoryInterface $clientRepository,
        DeleteClientAction $action
    ): RedirectResponse {
        $client = $this->findClientOrFail($id, $request, $clientRepository);
        $clientName = $client->name;
        $action->execute($request->user(), $client);

        return redirect()
            ->route('portal.clients.index')
            ->with('success', sprintf("Le client %s a été archivé de votre carnet d'adresses.", $clientName));
    }

    /**
     * Récupère un client de l'organisation courante ou renvoie 404.
     */
    private function findClientOrFail(
        int $id,
        Request $request,
        ClientRepositoryInterface $clientRepository
    ): Client {
        $client = $clientRepository->findById($id, (int) $request->user()->organization_id);

        if (! $client) {
            abort(404, 'Client introuvable.');
        }

        return $client;
    }

    /**
     * @return array{id: int|null, name: string}
     */
    private function organizationPayload(Request $request): array
    {
        $user = $request->user();
        $user->loadMissing('organization');

        return [
            'id' => $user->organization?->id,
            'name' => $user->organization?->name ?? 'Mon Entreprise',
        ];
    }

    /**
     * @return array{id: int, name: string, email: string, phone: string, address: string, tax_number: string}
     */
    private function formatClientSummary(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email ?? '',
            'phone' => $client->phone ?? '',
            'address' => $client->address ?? '',
            'tax_number' => $client->tax_number ?? '',
        ];
    }
}
