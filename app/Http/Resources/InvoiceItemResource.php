<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\InvoiceItem
 */
class InvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'description' => $this->description,
            'quantity'    => (float) $this->quantity,
            'unit_price'  => (float) $this->unit_price,
            'tax_rate'    => (float) $this->tax_rate,
            'total'       => (float) $this->total,
        ];
    }
}
