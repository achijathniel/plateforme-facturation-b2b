<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;
use App\Models\User;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\Gate;

final readonly class GetClientDetailsAction
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    /**
     * Récupère les détails 360° d'un client avec ses indicateurs et dernières factures.
     */
    public function execute(User $user, int $clientId): ?Client
    {
        $client = $this->clientRepository->findWithStats(
            $clientId,
            (int) $user->organization_id
        );

        if ($client !== null) {
            Gate::forUser($user)->authorize('view', $client);
        }

        return $client;
    }
}
