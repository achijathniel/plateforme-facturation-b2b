<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InvoiceRepository implements InvoiceRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginateForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::query()->with(['organization', 'items']);

        // Règle Multi-Tenancy : Si l'utilisateur n'est pas Admin, restreindre à son organisation
        if ($user->role !== UserRole::ADMIN) {
            $query->where('organization_id', $user->organization_id);
        }

        return $query->latest('issue_date')->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id): ?Invoice
    {
        return Invoice::with(['organization', 'items', 'payments'])->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $attributes): Invoice
    {
        return Invoice::create($attributes);
    }
}
