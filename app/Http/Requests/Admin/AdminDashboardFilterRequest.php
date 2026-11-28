<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\Dashboard\DashboardFilterDTO;
use App\Enums\UserRole;
use App\Http\Requests\Dashboard\BaseDashboardFilterRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

final class AdminDashboardFilterRequest extends BaseDashboardFilterRequest
{
    /**
     * Seul un utilisateur avec le rôle ADMIN peut consulter ce tableau de bord.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::ADMIN;
    }

    /**
     * Règles de validation pour les filtres analytiques du tableau de bord administrateur.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->baseRules(),
            'organization_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('organizations', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * Convertit les paramètres validés en DTO immutable.
     */
    public function toDTO(): DashboardFilterDTO
    {
        return DashboardFilterDTO::fromValidated($this->validated(), allowOrganization: true);
    }
}
