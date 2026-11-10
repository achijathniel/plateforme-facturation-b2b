<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\Admin\AdminOrganizationFilterData;
use Illuminate\Foundation\Http\FormRequest;

final class AdminOrganizationFilterRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Règles de validation pour les paramètres de filtrage d'organisations.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'         => ['nullable', 'string', 'max:150'],
            'status'         => ['nullable', 'string', 'in:all,active,inactive'],
            'per_page'       => ['nullable', 'integer', 'min:5', 'max:100'],
            'sort_by'        => ['nullable', 'string', 'in:name,created_at,invoices_count,users_count'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * Transforme les filtres d'URL validés en DTO immutable.
     */
    public function toDTO(): AdminOrganizationFilterData
    {
        return new AdminOrganizationFilterData(
            search: $this->filled('search') ? (string) $this->validated('search') : null,
            status: $this->filled('status') && $this->validated('status') !== 'all'
                ? (string) $this->validated('status')
                : null,
            perPage: (int) $this->input('per_page', 15),
            sortBy: (string) $this->input('sort_by', 'created_at'),
            sortDirection: (string) $this->input('sort_direction', 'desc'),
        );
    }
}
