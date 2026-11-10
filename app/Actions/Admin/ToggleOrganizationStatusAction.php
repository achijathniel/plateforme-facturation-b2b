<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class ToggleOrganizationStatusAction
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository
    ) {}

    /**
     * Active ou suspend une organisation.
     */
    public function execute(Organization $organization): Organization
    {
        return DB::transaction(function () use ($organization) {
            $updated = $this->organizationRepository->toggleStatus($organization);

            Cache::forget('admin.dashboard.stats');

            return $updated;
        });
    }
}
