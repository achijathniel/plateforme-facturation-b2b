<?php

declare(strict_types=1);

namespace App\Http\Requests\Dashboard;

use App\DTOs\Dashboard\DashboardFilterDTO;
use App\Enums\InvoiceStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class BaseDashboardFilterRequest extends FormRequest
{
    /**
     * Règles de validation partagées entre les tableaux de bord Admin et Portail.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function baseRules(): array
    {
        return [
            'period' => ['nullable', 'string', Rule::in(DashboardFilterDTO::PERIODS)],
            'status' => ['nullable', Rule::enum(InvoiceStatus::class)],
        ];
    }

    /**
     * Convertit la requête validée en DTO immutable.
     */
    abstract public function toDTO(): DashboardFilterDTO;
}
