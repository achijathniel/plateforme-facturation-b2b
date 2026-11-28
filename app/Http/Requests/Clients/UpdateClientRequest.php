<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use App\DTOs\Clients\ClientDTO;
use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateClientRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à modifier un client de son entreprise.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Client::class) ?? false;
    }

    /**
     * Règles de validation pour la mise à jour d'un client.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $organizationId = (int) $this->user()?->organization_id;
        $routeClient = $this->route('id');
        $clientId = $routeClient instanceof Client ? $routeClient->id : (int) $routeClient;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients', 'name')
                    ->ignore($clientId)
                    ->where(
                        fn ($query) => $query->where('organization_id', $organizationId)
                            ->whereNull('deleted_at')
                    ),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Messages d'erreur en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => "Le nom ou la raison sociale de l'entreprise cliente est obligatoire.",
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'name.unique' => "Un client avec cette raison sociale existe déjà dans votre carnet d'adresses.",
            'email.email' => "L'adresse email doit être une adresse valide.",
        ];
    }

    /**
     * Transforme les données validées en DTO typé.
     */
    public function toDTO(): ClientDTO
    {
        return ClientDTO::fromArray($this->validated());
    }
}
