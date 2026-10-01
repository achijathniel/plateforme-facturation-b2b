<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

use App\DTOs\Invoices\UpdateInvoiceDTO;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateInvoiceRequest extends FormRequest
{
    /**
     * L'autorisation est vérifiée par la Policy dans le contrôleur.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Règles de validation pour la modification d'une facture.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'due_date'            => ['required', 'date'],
            'notes'               => ['nullable', 'string', 'max:1000'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'action'              => ['nullable', 'string', 'in:draft,send'],
        ];
    }

    /**
     * Messages de validation personnalisés en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_date.required'            => 'La date d\'échéance est obligatoire.',
            'items.required'               => 'La facture doit comporter au moins une ligne d\'article.',
            'items.min'                    => 'La facture doit comporter au moins une ligne d\'article.',
            'items.*.description.required' => 'La description de chaque ligne est obligatoire.',
            'items.*.quantity.required'    => 'La quantité est obligatoire.',
            'items.*.quantity.min'         => 'La quantité doit être supérieure à 0.',
            'items.*.unit_price.required'  => 'Le prix unitaire est obligatoire.',
            'items.*.unit_price.min'       => 'Le prix unitaire ne peut pas être négatif.',
        ];
    }

    /**
     * Convertit la requête validée en DTO typé immuable.
     */
    public function toDTO(): UpdateInvoiceDTO
    {
        $notes = $this->validated('notes');
        $items = $this->validated('items');

        return UpdateInvoiceDTO::fromRequest(
            dueDate: (string) $this->validated('due_date'),
            notes: is_string($notes) ? $notes : null,
            items: is_array($items) ? $items : [],
            action: (string) ($this->validated('action') ?? 'draft'),
        );
    }
}
