<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case DIRECTOR = 'director';
    case ACCOUNTANT = 'accountant';
    case COLLABORATOR = 'collaborator';

    /**
     * Retourne les libellés lisibles pour chaque rôle en français.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::DIRECTOR => 'Directeur',
            self::ACCOUNTANT => 'Comptable',
            self::COLLABORATOR => 'Collaborateur',
        };
    }

    /**
     * Détermine si le rôle fait partie des membres d'une entreprise autorisés sur le portail.
     */
    public function isPortalRole(): bool
    {
        return in_array($this, [self::DIRECTOR, self::ACCOUNTANT, self::COLLABORATOR], true);
    }
}
