<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Bypasser toutes les vérifications pour un Administrateur système.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return null;
    }

    /**
     * Détermine si l'utilisateur peut lister les factures.
     */
    public function viewAny(User $user): bool
    {
        return $user->organization_id !== null;
    }

    /**
     * Détermine si l'utilisateur peut consulter une facture donnée.
     * Règle Multi-Tenancy : L'utilisateur doit appartenir à la même entreprise que la facture.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->organization_id !== null
            && $user->organization_id === $invoice->organization_id;
    }

    /**
     * Détermine si l'utilisateur peut créer une facture.
     * Règle RBAC : Le Directeur et le Comptable peuvent créer une facture. Le Collaborateur est en lecture seule.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::DIRECTOR, UserRole::ACCOUNTANT], true)
            && $user->organization_id !== null;
    }

    /**
     * Détermine si l'utilisateur peut modifier une facture.
     * Règles :
     * 1. Multi-tenancy : Même entreprise.
     * 2. RBAC : Directeur ou Comptable.
     * 3. Intégrité comptable : Impossible de modifier une facture déjà payée ou annulée.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        if (in_array($invoice->status, [InvoiceStatus::PAID, InvoiceStatus::CANCELLED], true)) {
            return false;
        }

        return in_array($user->role, [UserRole::DIRECTOR, UserRole::ACCOUNTANT], true)
            && $user->organization_id === $invoice->organization_id;
    }

    /**
     * Détermine si l'utilisateur peut supprimer une facture.
     * Règle comptable stricte : Seules les factures au statut Brouillon (DRAFT) peuvent être supprimées.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        if ($invoice->status !== InvoiceStatus::DRAFT) {
            return false;
        }

        return in_array($user->role, [UserRole::DIRECTOR, UserRole::ACCOUNTANT], true)
            && $user->organization_id === $invoice->organization_id;
    }

    /**
     * Détermine si l'utilisateur peut valider/approuver une facture.
     * Règle RBAC : Réservé au Directeur Général de l'entreprise propriétaire.
     */
    public function approve(User $user, Invoice $invoice): bool
    {
        return $user->role === UserRole::DIRECTOR
            && $user->organization_id === $invoice->organization_id;
    }
}

