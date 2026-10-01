<?php

declare(strict_types=1);

namespace App\DTOs\Invoices;

final readonly class UpdateInvoiceDTO
{
    /**
     * @param  array<CreateInvoiceItemDTO>  $items
     */
    public function __construct(
        public string $dueDate,
        public ?string $notes,
        public array $items,
        public string $action = 'draft',
    ) {}

    /**
     * Instanciation typée depuis les données validées de la requête HTTP.
     *
     * @param  array<int, array{description: string, quantity: numeric, unit_price: numeric}>  $items
     */
    public static function fromRequest(
        string $dueDate,
        ?string $notes,
        array $items,
        string $action = 'draft',
    ): self {
        $itemDTOs = array_map(
            fn (array $item) => CreateInvoiceItemDTO::fromArray($item),
            $items
        );

        return new self(
            dueDate: $dueDate,
            notes: $notes,
            items: $itemDTOs,
            action: $action,
        );
    }
}
