<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\Admin\UpdateOrganizationDTO;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Détermine si l'administrateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Règles de validation pour la modification d'une organisation.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationParam = $this->route('organization');
        $organizationId = $organizationParam instanceof Organization
            ? $organizationParam->id
            : (int) $organizationParam;

        return [
            'name'       => ['required', 'string', 'max:255'],
            'tax_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('organizations', 'tax_number')->ignore($organizationId),
            ],
            'email'      => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('organizations', 'email')->ignore($organizationId),
            ],
            'phone'      => ['nullable', 'string', 'max:50'],
            'address'    => ['nullable', 'string', 'max:500'],
            'is_active'  => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Messages de validation traduits en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'       => 'La raison sociale de l\'entreprise est obligatoire.',
            'name.max'            => 'La raison sociale ne peut dépasser 255 caractères.',
            'tax_number.required' => 'Le numéro fiscal / NIF / RCCM est obligatoire.',
            'tax_number.unique'   => 'Ce numéro fiscal est déjà enregistré pour une autre entreprise.',
            'email.required'      => 'L\'adresse email professionnelle est obligatoire.',
            'email.email'         => 'L\'adresse email doit être valide.',
            'email.unique'        => 'Cette adresse email est déjà associée à une entreprise.',
            'address.max'         => 'L\'adresse ne peut dépasser 500 caractères.',
        ];
    }

    /**
     * Transforme les données validées en DTO immutable.
     */
    public function toDTO(): UpdateOrganizationDTO
    {
        return new UpdateOrganizationDTO(
            name: (string) $this->validated('name'),
            taxNumber: (string) $this->validated('tax_number'),
            email: (string) $this->validated('email'),
            phone: $this->filled('phone') ? (string) $this->validated('phone') : null,
            address: $this->filled('address') ? (string) $this->validated('address') : null,
            isActive: $this->boolean('is_active', true),
        );
    }
}
