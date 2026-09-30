<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\Admin\AdminInvoiceFilterData;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminInvoiceFilterRequest extends FormRequest
{
    /**
     * Seul un utilisateur avec le rôle ADMIN peut émettre cette requête.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::ADMIN;
    }

    /**
     * Règles de validation pour les paramètres de filtrage et pagination.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'          => ['nullable', 'string', 'max:100'],
            'status'          => ['nullable', Rule::enum(InvoiceStatus::class)],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'per_page'        => ['nullable', 'integer', 'min:5', 'max:100'],
            'page'            => ['nullable', 'integer', 'min:1'],
            'sort_by'         => ['nullable', 'string', 'in:issue_date,due_date,total,invoice_number,status'],
            'sort_direction'  => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * Convertit la requête validée en DTO immutable.
     */
    public function toDTO(): AdminInvoiceFilterData
    {
        return AdminInvoiceFilterData::fromRequest($this);
    }
}
