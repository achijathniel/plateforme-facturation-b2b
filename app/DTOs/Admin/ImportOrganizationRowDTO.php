<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

/**
 * Data Transfer Object immutable représentant une ligne d'entreprise validée issue d'un fichier Excel/CSV.
 */
final readonly class ImportOrganizationRowDTO
{
    public function __construct(
        public int $rowNumber,
        public string $name,
        public string $taxNumber,
        public string $email,
        public ?string $phone = null,
        public ?string $address = null,
        public bool $isActive = true,
    ) {}

    /**
     * Convertit la ligne validée en DTO de création d'organisation.
     */
    public function toCreateDTO(): CreateOrganizationDTO
    {
        return new CreateOrganizationDTO(
            name: $this->name,
            taxNumber: $this->taxNumber,
            email: $this->email,
            phone: $this->phone,
            address: $this->address,
            isActive: $this->isActive,
        );
    }
}
