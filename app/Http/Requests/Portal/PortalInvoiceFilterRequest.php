<?php

declare(strict_types=1);

namespace App\Http\Requests\Portal;

use App\DTOs\Portal\PortalInvoiceFilterData;
use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PortalInvoiceFilterRequest extends FormRequest
{
    /**
     * Seuls les utilisateurs autorisés à consulter les factures du portail peuvent filtrer.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Règles de validation des filtres de factures du portail comptable.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'         => ['nullable', 'string', 'max:100'],
            'status'         => ['nullable', 'string', Rule::enum(InvoiceStatus::class)],
            'per_page'       => ['nullable', 'integer', 'min:5', 'max:100'],
            'page'           => ['nullable', 'integer', 'min:1'],
            'sort_by'        => ['nullable', 'string', 'in:issue_date,due_date,total,invoice_number,status'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
        ];
    }

    /**
     * Convertit la requête validée en DTO immuable.
     */
    public function toDTO(): PortalInvoiceFilterData
    {
        return PortalInvoiceFilterData::fromRequest($this);
    }
}
