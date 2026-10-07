<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\DTOs\Clients\ClientDTO;
use App\Models\Client;
use App\Models\User;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\Gate;

final readonly class CreateClientAction
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
    ) {}

    /**
     * Enregistre un nouveau client dans l'annuaire de l'entreprise.
     */
    public function execute(User $user, ClientDTO $dto): Client
    {
        Gate::forUser($user)->authorize('create', Client::class);

        return $this->clientRepository->create(
            (int) $user->organization_id,
            $dto
        );
    }
}
