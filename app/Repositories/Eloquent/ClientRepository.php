<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Clients\ClientDTO;
use App\DTOs\Clients\ClientFilterData;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ClientRepository implements ClientRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginateForOrganization(int $organizationId, ClientFilterData $filter): LengthAwarePaginator
    {
        $paidStatus = InvoiceStatus::PAID->value;

        $invoiceStats = DB::table('invoices')
            ->select([
                'client_id',
                DB::raw('COUNT(*) as invoices_count'),
                DB::raw('COALESCE(SUM(total), 0) as total_billed'),
                DB::raw('COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_paid'),
            ])
            ->addBinding($paidStatus, 'select')
            ->where('organization_id', $organizationId)
            ->whereNotNull('client_id')
            ->whereNull('deleted_at')
            ->groupBy('client_id');

        $query = Client::query()
            ->leftJoinSub($invoiceStats, 'inv_stats', function ($join): void {
                $join->on('clients.id', '=', 'inv_stats.client_id');
            })
            ->where('clients.organization_id', $organizationId)
            ->select([
                'clients.*',
                DB::raw('COALESCE(inv_stats.invoices_count, 0) as invoices_count'),
                DB::raw('COALESCE(inv_stats.total_billed, 0) as total_billed'),
                DB::raw('COALESCE(inv_stats.total_paid, 0) as total_paid'),
            ]);

        if ($filter->search !== null && trim($filter->search) !== '') {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filter->search));
            $term = '%'.mb_strtolower($escaped).'%';
            $rawTerm = '%'.$escaped.'%';

            $query->where(function ($sub) use ($term, $rawTerm): void {
                $sub->whereRaw("LOWER(clients.name) LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("LOWER(clients.email) LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("LOWER(clients.tax_number) LIKE ? ESCAPE '!'", [$term])
                    ->orWhereRaw("clients.phone LIKE ? ESCAPE '!'", [$rawTerm]);
            });
        }

        $direction = strtolower($filter->sortDirection) === 'desc' ? 'desc' : 'asc';
        $sortMap = [
            'name' => 'clients.name',
            'created_at' => 'clients.created_at',
            'invoices_count' => 'COALESCE(inv_stats.invoices_count, 0)',
            'total_billed' => 'COALESCE(inv_stats.total_billed, 0)',
        ];
        $sortColumn = $sortMap[$filter->sortBy] ?? 'clients.name';

        return $query->orderByRaw("{$sortColumn} {$direction}")
            ->orderBy('clients.id', 'asc')
            ->paginate($filter->perPage)
            ->withQueryString();
    }

    /**
     * Recherche des clients par mot-clé (nom, email, nif) pour l'autocomplétion.
     *
     * @return Collection<int, Client>
     */
    public function search(int $organizationId, ?string $term = null, int $limit = 10): Collection
    {
        $query = Client::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name');

        if ($term !== null && trim($term) !== '') {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($term));
            $lowerTerm = '%'.mb_strtolower($escaped).'%';

            $query->where(function ($q) use ($lowerTerm): void {
                $q->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$lowerTerm])
                    ->orWhereRaw("LOWER(email) LIKE ? ESCAPE '!'", [$lowerTerm])
                    ->orWhereRaw("LOWER(tax_number) LIKE ? ESCAPE '!'", [$lowerTerm]);
            });
        }

        return $query->limit($limit)->get();
    }

    /**
     * Recherche un client par ID et organisation.
     */
    public function findById(int $id, int $organizationId): ?Client
    {
        return Client::query()
            ->where('organization_id', $organizationId)
            ->where('id', $id)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findWithStats(int $id, int $organizationId): ?Client
    {
        $paidStatus = InvoiceStatus::PAID->value;
        $sentStatus = InvoiceStatus::SENT->value;
        $overdueStatus = InvoiceStatus::OVERDUE->value;

        $invoiceStats = DB::table('invoices')
            ->select([
                'client_id',
                DB::raw('COUNT(*) as invoices_count'),
                DB::raw('COALESCE(SUM(total), 0) as total_billed'),
                DB::raw('COALESCE(SUM(CASE WHEN status = ? THEN total ELSE 0 END), 0) as total_paid'),
                DB::raw('COALESCE(SUM(CASE WHEN status IN (?, ?) THEN total ELSE 0 END), 0) as total_pending'),
            ])
            ->addBinding([$paidStatus, $sentStatus, $overdueStatus], 'select')
            ->where('organization_id', $organizationId)
            ->where('client_id', $id)
            ->whereNull('deleted_at')
            ->groupBy('client_id');

        return Client::query()
            ->leftJoinSub($invoiceStats, 'inv_stats', function ($join): void {
                $join->on('clients.id', '=', 'inv_stats.client_id');
            })
            ->where('clients.organization_id', $organizationId)
            ->where('clients.id', $id)
            ->select([
                'clients.*',
                DB::raw('COALESCE(inv_stats.invoices_count, 0) as invoices_count'),
                DB::raw('COALESCE(inv_stats.total_billed, 0) as total_billed'),
                DB::raw('COALESCE(inv_stats.total_paid, 0) as total_paid'),
                DB::raw('COALESCE(inv_stats.total_pending, 0) as total_pending'),
            ])
            ->with([
                'invoices' => fn ($q) => $q->select([
                    'id',
                    'organization_id',
                    'client_id',
                    'invoice_number',
                    'issue_date',
                    'due_date',
                    'total',
                    'currency',
                    'status',
                    'created_at',
                ])->where('organization_id', $organizationId)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(20),
            ])
            ->first();
    }

    /**
     * Recherche un client par son nom exact pour une organisation.
     */
    public function findByName(string $name, int $organizationId): ?Client
    {
        return Client::query()
            ->where('organization_id', $organizationId)
            ->where('name', $name)
            ->first();
    }

    /**
     * Crée un nouveau client pour une organisation.
     */
    public function create(int $organizationId, ClientDTO $dto): Client
    {
        return Client::create(array_merge(
            $dto->toArray(),
            ['organization_id' => $organizationId]
        ));
    }

    /**
     * Met à jour les informations d'un client.
     */
    public function update(Client $client, ClientDTO $dto): Client
    {
        $client->update($dto->toArray());

        return $client->refresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Client $client): bool
    {
        return (bool) $client->delete();
    }
}
