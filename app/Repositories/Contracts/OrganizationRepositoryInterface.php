<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface OrganizationRepositoryInterface
{
    /**
     * Récupère la liste simplifiée des entreprises pour les listes déroulantes de sélection.
     *
     * @return Collection<int, \App\Models\Organization>
     */
    public function getAllForSelect(): Collection;
}
