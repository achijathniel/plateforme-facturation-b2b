<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Admin\AdminOrganizationFilterData;
use App\DTOs\Admin\CreateOrganizationDTO;
use App\DTOs\Admin\UpdateOrganizationDTO;
use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function paginateWithFilters(AdminOrganizationFilterData $filter): LengthAwarePaginator
    {
        $query = Organization::query()
            ->withCount(['invoices', 'users'])
            ->withSum('invoices as total_billed', 'total');

        // Filtrage textuel sécurisé (avec échappement des jokers SQL)
        if ($filter->search !== null && trim($filter->search) !== '') {
            $escaped = addcslashes(trim($filter->search), '%_');
            $term = '%' . mb_strtolower($escaped) . '%';

            $query->where(function ($sub) use ($term, $escaped) {
                $sub->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(tax_number) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                    ->orWhere('phone', 'LIKE', '%' . $escaped . '%');
            });
        }

        // Filtrage par statut d'activité
        if ($filter->status === 'active') {
            $query->where('is_active', true);
        } elseif ($filter->status === 'inactive') {
            $query->where('is_active', false);
        }

        // Tri sécurisé
        $direction = strtolower($filter->sortDirection) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'created_at', 'invoices_count', 'users_count', 'total_billed'];
        $sort = in_array($filter->sortBy, $allowedSorts, true) ? $filter->sortBy : 'created_at';

        return $query->orderBy($sort, $direction)
            ->paginate($filter->perPage)
            ->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdWithDetails(int $id): ?Organization
    {
        /** @var Organization|null $organization */
        $organization = Organization::query()
            ->withCount(['invoices', 'users'])
            ->withSum('invoices as total_billed', 'total')
            ->withSum(['invoices as total_paid' => fn ($q) => $q->where('status', 'paid')], 'total')
            ->with([
                'users' => fn ($q) => $q->select(['id', 'organization_id', 'name', 'email', 'role', 'created_at'])
                    ->orderBy('created_at', 'desc'),
                'invoices' => fn ($q) => $q->select([
                    'id',
                    'organization_id',
                    'invoice_number',
                    'client_name',
                    'issue_date',
                    'due_date',
                    'total',
                    'currency',
                    'status',
                    'created_at',
                ])->orderBy('created_at', 'desc')->limit(5),
            ])
            ->find($id);

        return $organization;
    }

    /**
     * {@inheritDoc}
     */
    public function create(CreateOrganizationDTO $dto): Organization
    {
        return Organization::create($dto->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function update(Organization $organization, UpdateOrganizationDTO $dto): Organization
    {
        $organization->update($dto->toArray());

        return $organization->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function toggleStatus(Organization $organization): Organization
    {
        $organization->update([
            'is_active' => ! $organization->is_active,
        ]);

        return $organization->fresh();
    }
}
