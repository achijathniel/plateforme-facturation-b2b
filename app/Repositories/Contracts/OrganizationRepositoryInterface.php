<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\Admin\AdminOrganizationFilterData;
use App\DTOs\Admin\CreateOrganizationDTO;
use App\DTOs\Admin\UpdateOrganizationDTO;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrganizationRepositoryInterface
{
    /**
     * Récupère la liste simplifiée des entreprises pour les listes déroulantes de sélection.
     *
     * @return Collection<int, Organization>
     */
    public function getAllForSelect(): Collection;

    /**
     * Récupère la liste paginée et filtrée des entreprises pour l'administration.
     *
     * @return LengthAwarePaginator<Organization>
     */
    public function paginateWithFilters(AdminOrganizationFilterData $filter): LengthAwarePaginator;

    /**
     * Récupère une organisation par son identifiant avec les comptages et détails relationnels.
     */
    public function findByIdWithDetails(int $id): ?Organization;

    /**
     * Persiste une nouvelle organisation en base de données.
     */
    public function create(CreateOrganizationDTO $dto): Organization;

    /**
     * Met à jour les informations d'une organisation existante.
     */
    public function update(Organization $organization, UpdateOrganizationDTO $dto): Organization;

    /**
     * Inverse le statut actif/inactif d'une organisation.
     */
    public function toggleStatus(Organization $organization): Organization;
}
