<?php

declare(strict_types=1);

namespace App\DTOs\Clients;

final readonly class ClientDTO
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $taxNumber = null,
        public ?string $notes = null,
    ) {}

    /**
     * Instanciation typée à partir d'un tableau validé.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: isset($data['email']) && $data['email'] !== '' ? (string) $data['email'] : null,
            phone: isset($data['phone']) && $data['phone'] !== '' ? (string) $data['phone'] : null,
            address: isset($data['address']) && $data['address'] !== '' ? (string) $data['address'] : null,
            taxNumber: isset($data['tax_number']) && $data['tax_number'] !== '' ? (string) $data['tax_number'] : null,
            notes: isset($data['notes']) && $data['notes'] !== '' ? (string) $data['notes'] : null,
        );
    }

    /**
     * Conversion en tableau pour la persistance Eloquent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'       => $this->name,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'address'    => $this->address,
            'tax_number' => $this->taxNumber,
            'notes'      => $this->notes,
        ];
    }
}
