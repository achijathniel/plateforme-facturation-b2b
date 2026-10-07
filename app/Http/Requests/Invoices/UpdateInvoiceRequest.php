<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

use App\DTOs\Invoices\UpdateInvoiceDTO;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $organizationId = (int) $this->user()?->organization_id;

        return [
            'client_id'           => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId)
                ),
            ],
            'client_name'         => ['required', 'string', 'max:255'],
            'client_email'        => ['required', 'email', 'max:255'],
            'client_address'      => ['nullable', 'string', 'max:500'],
            'client_tax_number'   => ['nullable', 'string', 'max:50'],
            'client_phone'        => ['nullable', 'string', 'max:30'],
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
            'client_id.exists'            => 'Le client sélectionné est introuvable ou n\'appartient pas à votre organisation.',
            'client_name.required'        => 'Le nom de l\'entreprise cliente est obligatoire.',
            'client_email.required'       => 'L\'adresse email de l\'entreprise cliente est obligatoire.',
            'client_email.email'          => 'L\'adresse email du client doit être une adresse valide.',
            'due_date.required'           => 'La date d\'échéance est obligatoire.',
            'items.required'              => 'La facture doit comporter au moins une ligne d\'article.',
            'items.min'                   => 'La facture doit comporter au moins une ligne d\'article.',
            'items.*.description.required'=> 'La description de chaque ligne est obligatoire.',
            'items.*.quantity.required'   => 'La quantité est obligatoire.',
            'items.*.quantity.min'        => 'La quantité doit être supérieure à 0.',
            'items.*.unit_price.required' => 'Le prix unitaire est obligatoire.',
            'items.*.unit_price.min'      => 'Le prix unitaire ne peut pas être négatif.',
        ];
    }

    /**
     * Convertit la requête validée en DTO typé immuable.
     */
    public function toDTO(): UpdateInvoiceDTO
    {
        $notes = $this->validated('notes');
        $items = $this->validated('items');
        $clientId = $this->filled('client_id') ? (int) $this->validated('client_id') : null;

        return UpdateInvoiceDTO::fromRequest(
            clientName: (string) $this->validated('client_name'),
            clientEmail: (string) $this->validated('client_email'),
            clientAddress: is_string($this->validated('client_address')) ? $this->validated('client_address') : null,
            clientTaxNumber: is_string($this->validated('client_tax_number')) ? $this->validated('client_tax_number') : null,
            clientPhone: is_string($this->validated('client_phone')) ? $this->validated('client_phone') : null,
            dueDate: (string) $this->validated('due_date'),
            notes: is_string($notes) ? $notes : null,
            items: is_array($items) ? $items : [],
            action: (string) ($this->validated('action') ?? 'draft'),
            clientId: $clientId,
        );
    }
}
