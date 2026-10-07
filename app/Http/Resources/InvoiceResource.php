<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Invoice
 */
class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'invoice_number' => $this->invoice_number,
            'status'         => [
                'code'  => $this->status->value,
                'label' => $this->status->label(),
            ],
            'financials'     => [
                'subtotal'   => (float) $this->subtotal,
                'tax_amount' => (float) $this->tax_amount,
                'total'      => (float) $this->total,
                'currency'   => $this->currency,
            ],
            'dates'          => [
                'issue_date' => $this->issue_date?->toDateString(),
                'due_date'   => $this->due_date?->toDateString(),
            ],
            'notes'          => $this->notes,
            'client'         => [
                'id'         => $this->client_id,
                'name'       => $this->client_name,
                'email'      => $this->client_email,
                'address'    => $this->client_address,
                'tax_number' => $this->client_tax_number,
                'phone'      => $this->client_phone,
            ],
            'organization'   => $this->whenLoaded('organization', function () {
                return [
                    'id'         => $this->organization->id,
                    'name'       => $this->organization->name,
                    'tax_number' => $this->organization->tax_number,
                    'email'      => $this->organization->email,
                ];
            }),
            'items'          => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
