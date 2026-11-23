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
use Illuminate\Support\Facades\DB;

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
        $invoiceStats = DB::table('invoices')
            ->select([
                'organization_id',
                DB::raw('COUNT(*) as invoices_count'),
                DB::raw('COALESCE(SUM(total), 0) as total_billed'),
            ])
            ->whereNull('deleted_at')
            ->groupBy('organization_id');

        $userStats = DB::table('users')
            ->select([
                'organization_id',
                DB::raw('COUNT(*) as users_count'),
            ])
            ->groupBy('organization_id');

        $query = Organization::query()
            ->leftJoinSub($invoiceStats, 'inv_stats', function ($join): void {
                $join->on('organizations.id', '=', 'inv_stats.organization_id');
            })
            ->leftJoinSub($userStats, 'usr_stats', function ($join): void {
                $join->on('organizations.id', '=', 'usr_stats.organization_id');
            })
            ->select([
                'organizations.*',
                DB::raw('COALESCE(inv_stats.invoices_count, 0) as invoices_count'),
                DB::raw('COALESCE(inv_stats.total_billed, 0) as total_billed'),
                DB::raw('COALESCE(usr_stats.users_count, 0) as users_count'),
            ]);

        // Filtrage textuel sécurisé (avec échappement des jokers SQL)
        if ($filter->search !== null && trim($filter->search) !== '') {
            $escaped = addcslashes(trim($filter->search), '%_');
            $term = '%'.mb_strtolower($escaped).'%';

            $query->where(function ($sub) use ($term, $escaped): void {
                $sub->whereRaw('LOWER(organizations.name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(organizations.tax_number) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(organizations.email) LIKE ?', [$term])
                    ->orWhere('organizations.phone', 'LIKE', '%'.$escaped.'%');
            });
        }

        // Filtrage par statut d'activité
        if ($filter->status === 'active') {
            $query->where('organizations.is_active', true);
        } elseif ($filter->status === 'inactive') {
            $query->where('organizations.is_active', false);
        }

        // Tri sécurisé via liste blanche explicite
        $direction = strtolower($filter->sortDirection) === 'asc' ? 'asc' : 'desc';
        $sortMap = [
            'name' => 'organizations.name',
            'created_at' => 'organizations.created_at',
            'invoices_count' => 'COALESCE(inv_stats.invoices_count, 0)',
            'users_count' => 'COALESCE(usr_stats.users_count, 0)',
            'total_billed' => 'COALESCE(inv_stats.total_billed, 0)',
        ];
        $sortColumn = $sortMap[$filter->sortBy] ?? 'organizations.created_at';

        return $query->orderByRaw("{$sortColumn} {$direction}")
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

    /**
     * {@inheritDoc}
     */
    public function findExistingIdentifiers(array $emails, array $taxNumbers): array
    {
        $normalizedEmails = array_values(array_unique(array_filter(array_map(
            static fn (string $email): string => mb_strtolower(trim($email)),
            $emails
        ))));

        $normalizedTaxes = array_values(array_unique(array_filter(array_map(
            static fn (string $tax): string => mb_strtolower(trim($tax)),
            $taxNumbers
        ))));

        $existingEmails = [];
        $existingTaxNumbers = [];

        foreach (array_chunk($normalizedEmails, 200) as $emailChunk) {
            $placeholders = implode(', ', array_fill(0, count($emailChunk), '?'));
            $matched = DB::table('organizations')
                ->whereRaw("LOWER(email) IN ({$placeholders})", $emailChunk)
                ->pluck('email');

            foreach ($matched as $email) {
                if (is_string($email) && trim($email) !== '') {
                    $existingEmails[mb_strtolower(trim($email))] = true;
                }
            }
        }

        foreach (array_chunk($normalizedTaxes, 200) as $taxChunk) {
            $placeholders = implode(', ', array_fill(0, count($taxChunk), '?'));
            $matched = DB::table('organizations')
                ->whereRaw("LOWER(tax_number) IN ({$placeholders})", $taxChunk)
                ->pluck('tax_number');

            foreach ($matched as $tax) {
                if (is_string($tax) && trim($tax) !== '') {
                    $existingTaxNumbers[mb_strtolower(trim($tax))] = true;
                }
            }
        }

        return [
            'emails' => $existingEmails,
            'tax_numbers' => $existingTaxNumbers,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function createMany(array $dtos): int
    {
        if ($dtos === []) {
            return 0;
        }

        $now = now();
        $records = [];

        foreach ($dtos as $dto) {
            $records[] = [
                ...$dto->toArray(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($records, 200) as $chunk) {
            Organization::insert($chunk);
        }

        return count($records);
    }
}
