<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\Admin\CreateOrganizationDTO;
use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class CreateOrganizationAction
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository
    ) {}

    /**
     * Exécute la création d'une nouvelle organisation de manière atomique.
     */
    public function execute(CreateOrganizationDTO $dto): Organization
    {
        return DB::transaction(function () use ($dto) {
            $organization = $this->organizationRepository->create($dto);

            // Invalidation du cache des statistiques du tableau de bord admin
            Cache::forget('admin.dashboard.stats');

            return $organization;
        });
    }
}
