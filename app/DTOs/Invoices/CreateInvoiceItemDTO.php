<?php

namespace App\DTOs\Invoices;

readonly class CreateInvoiceItemDTO
{
    public function __construct(
        public string $description,
        public string $quantity,
        public string $unitPrice,
    ) {}

    /**
     * @param  array{description: string, quantity: numeric, unit_price: numeric}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            description: (string) $data['description'],
            quantity: (string) $data['quantity'],
            unitPrice: (string) $data['unit_price'],
        );
    }
}
