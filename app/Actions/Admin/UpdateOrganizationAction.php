<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\UpdateOrganizationDTO;
use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class UpdateOrganizationAction
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository
    ) {}

    /**
     * Exécute la mise à jour d'une organisation existante.
     */
    public function execute(Organization $organization, UpdateOrganizationDTO $dto): Organization
    {
        return DB::transaction(function () use ($organization, $dto) {
            $updated = $this->organizationRepository->update($organization, $dto);

            // Invalidation du cache des statistiques
            Cache::forget('admin.dashboard.stats');

            return $updated;
        });
    }
}
