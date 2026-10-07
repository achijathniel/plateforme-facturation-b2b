<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Bypasser toutes les vérifications pour un Administrateur global.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return null;
    }

    /**
     * Détermine si l'utilisateur peut lister les clients de son entreprise.
     */
    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    /**
     * Détermine si l'utilisateur peut consulter la fiche d'un client.
     * Règle Multi-Tenancy : L'utilisateur doit appartenir à la même entreprise que le client.
     */
    public function view(User $user, Client $client): bool
    {
        return $user->organization_id !== null
            && $user->organization_id === $client->organization_id;
    }

    /**
     * Détermine si l'utilisateur peut créer un nouveau client dans l'annuaire.
     * Règle RBAC : Directeur ou Comptable de l'entreprise.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::DIRECTOR, UserRole::ACCOUNTANT], true)
            && $user->organization_id !== null;
    }

    /**
     * Détermine si l'utilisateur peut modifier un client existant.
     */
    public function update(User $user, Client $client): bool
    {
        return in_array($user->role, [UserRole::DIRECTOR, UserRole::ACCOUNTANT], true)
            && $user->organization_id === $client->organization_id;
    }

    /**
     * Détermine si l'utilisateur peut supprimer un client de l'annuaire.
     */
    public function delete(User $user, Client $client): bool
    {
        return in_array($user->role, [UserRole::DIRECTOR, UserRole::ACCOUNTANT], true)
            && $user->organization_id === $client->organization_id;
    }
}
