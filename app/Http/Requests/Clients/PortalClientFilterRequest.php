<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\DTOs\Clients\ClientFilterData;
use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class PortalClientFilterRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à filtrer l'annuaire clients de son organisation.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Client::class) ?? false;
    }

    /**
     * Règles de validation des filtres de l'annuaire clients.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:name,created_at,invoices_count,total_billed'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Convertit les paramètres validés en DTO immuable.
     */
    public function toDTO(): ClientFilterData
    {
        return ClientFilterData::fromArray($this->validated());
    }
}
