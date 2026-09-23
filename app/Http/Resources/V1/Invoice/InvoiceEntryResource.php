<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Invoice;

use App\Http\Resources\V1\BaseResource;
use App\Models\InvoiceEntry;
use Illuminate\Http\Request;

/**
 * @property InvoiceEntry $resource
 */
class InvoiceEntryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|int|float|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'invoice_id' => $this->resource->invoice_id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'unit_price' => $this->resource->unit_price,
            'quantity' => (float) $this->resource->quantity,
            'line_total' => $this->resource->line_total,
            'order_index' => $this->resource->order_index,
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
