<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Admin\AdminInvoiceFilterData;
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
    public function paginateWithFilters(AdminInvoiceFilterData $filter): LengthAwarePaginator
    {
        $query = Invoice::query()->with(['organization']);

        if ($filter->status !== null) {
            $query->where('status', $filter->status->value);
        }

        if ($filter->organizationId !== null) {
            $query->where('organization_id', $filter->organizationId);
        }

        if (! empty($filter->search)) {
            $term = '%' . addcslashes(strtolower(trim($filter->search)), '%_') . '%';
            $query->where(function ($sub) use ($term) {
                $sub->whereRaw('LOWER(invoice_number) LIKE ?', [$term])
                    ->orWhereHas('organization', function ($orgQuery) use ($term) {
                        $orgQuery->whereRaw('LOWER(name) LIKE ?', [$term]);
                    });
            });
        }

        $allowedSorts = ['issue_date', 'due_date', 'total', 'invoice_number', 'status'];
        $sortBy = in_array($filter->sortBy, $allowedSorts, true) ? $filter->sortBy : 'issue_date';
        $sortDirection = strtolower($filter->sortDirection) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDirection)
            ->paginate($filter->perPage, ['*'], 'page', $filter->page);
    }

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
