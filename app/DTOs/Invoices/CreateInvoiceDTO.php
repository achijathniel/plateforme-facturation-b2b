<?php

namespace App\DTOs\Invoices;

readonly class CreateInvoiceDTO
{
    /**
     * @param  array<CreateInvoiceItemDTO>  $items
     */
    public function __construct(
        public int $organizationId,
        public string $dueDate,
        public ?string $notes,
        public array $items,
    ) {}

    /**
     * Instanciation typée depuis les données validées de la requête HTTP.
     *
     * @param  array<int, array{description: string, quantity: numeric, unit_price: numeric}>  $items
     */
    public static function fromRequest(
        int $organizationId,
        string $dueDate,
        ?string $notes,
        array $items,
    ): self {
        $itemDTOs = array_map(
            fn (array $item) => CreateInvoiceItemDTO::fromArray($item),
            $items
        );

        return new self(
            organizationId: $organizationId,
            dueDate: $dueDate,
            notes: $notes,
            items: $itemDTOs,
        );
    }
}
