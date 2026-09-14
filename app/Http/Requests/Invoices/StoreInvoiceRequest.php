<?php

namespace App\Http\Requests\Invoices;

use App\DTOs\Invoices\CreateInvoiceDTO;
use App\Enums\UserRole;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé (vérifié par la Policy).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Invoice::class) ?? false;
    }

    /**
     * Règles de validation strictes pour la création de facture.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'due_date'            => ['required', 'date', 'after_or_equal:today'],
            'notes'               => ['nullable', 'string', 'max:1000'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
        ];

        // Seul un Administrateur peut spécifier l'organisation cible (vue globale)
        if ($this->user()?->role === UserRole::ADMIN) {
            $rules['organization_id'] = ['required', 'integer', 'exists:organizations,id'];
        }

        return $rules;
    }

    /**
     * Messages d'erreur personnalisés.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_date.required'           => 'La date d\'échéance est obligatoire.',
            'due_date.after_or_equal'     => 'La date d\'échéance ne peut pas être antérieure à aujourd\'hui.',
            'items.required'              => 'La facture doit comporter au moins une ligne d\'article.',
            'items.min'                   => 'La facture doit comporter au moins une ligne d\'article.',
            'items.*.description.required'=> 'La description de chaque ligne est obligatoire.',
            'items.*.quantity.required'   => 'La quantité est obligatoire.',
            'items.*.quantity.min'        => 'La quantité doit être supérieure à 0.',
            'items.*.unit_price.required' => 'Le prix unitaire est obligatoire.',
            'items.*.unit_price.min'      => 'Le prix unitaire ne peut pas être négatif.',
            'organization_id.required'    => 'L\'organisation cible est obligatoire pour un administrateur.',
            'organization_id.exists'      => 'L\'organisation sélectionnée est introuvable.',
        ];
    }

    /**
     * Convertit la requête validée en DTO typé et immutable.
     */
    public function toDTO(): CreateInvoiceDTO
    {
        $organizationId = $this->user()->role === UserRole::ADMIN
            ? (int) $this->validated('organization_id')
            : (int) $this->user()->organization_id;

        return CreateInvoiceDTO::fromRequest(
            organizationId: $organizationId,
            dueDate: (string) $this->validated('due_date'),
            notes: $this->validated('notes'),
            items: $this->validated('items'),
        );
    }
}
