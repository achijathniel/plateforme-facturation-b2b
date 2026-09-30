<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Support\Collection;

final class OrganizationRepository implements OrganizationRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAllForSelect(): Collection
    {
        return Organization::query()
            ->select(['id', 'name'])
            ->orderBy('name', 'asc')
            ->get();
    }
}
