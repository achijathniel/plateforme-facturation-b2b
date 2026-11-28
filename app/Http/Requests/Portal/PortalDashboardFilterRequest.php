<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\DTOs\Dashboard\DashboardFilterDTO;
use App\Http\Requests\Dashboard\BaseDashboardFilterRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class PortalDashboardFilterRequest extends BaseDashboardFilterRequest
{
    /**
     * Seul un utilisateur portail rattaché à une organisation peut consulter ce tableau de bord.
     */
    public function authorize(): bool
    {
        return $this->user()?->isPortalUser() ?? false;
    }

    /**
     * Règles de validation pour les filtres analytiques du tableau de bord portail.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->baseRules();
    }

    /**
     * Convertit les paramètres validés en DTO immutable (isolation multi-tenant stricte).
     */
    public function toDTO(): DashboardFilterDTO
    {
        return DashboardFilterDTO::fromValidated($this->validated(), allowOrganization: false);
    }
}
