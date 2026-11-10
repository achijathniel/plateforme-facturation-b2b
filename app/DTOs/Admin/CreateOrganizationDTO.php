<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

/**
 * Data Transfer Object immutable pour la création d'une organisation.
 */
final readonly class CreateOrganizationDTO
{
    public function __construct(
        public string $name,
        public string $taxNumber,
        public string $email,
        public ?string $phone = null,
        public ?string $address = null,
        public bool $isActive = true,
    ) {}

    /**
     * Mappe les données validées en tableau pour Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'tax_number' => $this->taxNumber,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'address'    => $this->address,
            'is_active'  => $this->isActive,
        ];
    }
}
