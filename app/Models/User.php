<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'organization_id', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Relation : Un utilisateur appartient à une entreprise (organisation).
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Vérifie si l'utilisateur est un Administrateur global de la plateforme.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    /**
     * Vérifie si l'utilisateur est le Directeur Général / Chef d'entreprise.
     */
    public function isDirector(): bool
    {
        return $this->role === UserRole::DIRECTOR;
    }

    /**
     * Vérifie si l'utilisateur est un Comptable de l'entreprise.
     */
    public function isAccountant(): bool
    {
        return $this->role === UserRole::ACCOUNTANT;
    }

    /**
     * Vérifie si l'utilisateur est un Collaborateur interne de l'entreprise.
     */
    public function isCollaborator(): bool
    {
        return $this->role === UserRole::COLLABORATOR;
    }

    /**
     * Vérifie si l'utilisateur est rattaché à une organisation avec un rôle portail valide.
     */
    public function isPortalUser(): bool
    {
        return $this->organization_id !== null && ($this->role?->isPortalRole() ?? false);
    }
}

