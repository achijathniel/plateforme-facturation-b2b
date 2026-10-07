<?php

declare(strict_types=1);

namespace App\DTOs\Invoices;

final readonly class UpdateInvoiceDTO
{
    /**
     * @param  array<CreateInvoiceItemDTO>  $items
     */
    public function __construct(
        public string $clientName,
        public string $clientEmail,
        public ?string $clientAddress,
        public ?string $clientTaxNumber,
        public ?string $clientPhone,
        public string $dueDate,
        public ?string $notes,
        public array $items,
        public string $action = 'draft',
        public ?int $clientId = null,
    ) {}

    /**
     * Instanciation typée depuis les données validées de la requête HTTP.
     *
     * @param  array<int, array{description: string, quantity: numeric, unit_price: numeric}>  $items
     */
    public static function fromRequest(
        string $clientName,
        string $clientEmail,
        ?string $clientAddress,
        ?string $clientTaxNumber,
        ?string $clientPhone,
        string $dueDate,
        ?string $notes,
        array $items,
        string $action = 'draft',
        ?int $clientId = null,
    ): self {
        $itemDTOs = array_map(
            fn (array $item) => CreateInvoiceItemDTO::fromArray($item),
            $items
        );

        return new self(
            clientName: trim($clientName),
            clientEmail: trim($clientEmail),
            clientAddress: $clientAddress ? trim($clientAddress) : null,
            clientTaxNumber: $clientTaxNumber ? trim($clientTaxNumber) : null,
            clientPhone: $clientPhone ? trim($clientPhone) : null,
            dueDate: $dueDate,
            notes: $notes,
            items: $itemDTOs,
            action: $action,
            clientId: $clientId,
        );
    }
}
