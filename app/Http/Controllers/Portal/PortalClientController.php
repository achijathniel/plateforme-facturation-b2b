<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Clients\CreateClientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class PortalClientController extends Controller
{
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
            'clients' => $clients->map(fn (Client $c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'email'      => $c->email ?? '',
                'phone'      => $c->phone ?? '',
                'address'    => $c->address ?? '',
                'tax_number' => $c->tax_number ?? '',
            ]),
        ]);
    }

    /**
     * Enregistre un nouveau client dans l'annuaire de l'entreprise.
     */
    public function store(
        StoreClientRequest $request,
        CreateClientAction $createClientAction
    ): JsonResponse|RedirectResponse {
        $client = $createClientAction->execute($request->user(), $request->toDTO());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Client enregistré avec succès.',
                'client'  => [
                    'id'         => $client->id,
                    'name'       => $client->name,
                    'email'      => $client->email ?? '',
                    'phone'      => $client->phone ?? '',
                    'address'    => $client->address ?? '',
                    'tax_number' => $client->tax_number ?? '',
                ],
            ], 201);
        }

        return redirect()->back()->with('success', sprintf('Le client %s a été ajouté à votre carnet d\'adresses.', $client->name));
    }
}
