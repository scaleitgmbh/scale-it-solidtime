<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Invoice;

use App\Http\Resources\V1\BaseResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @property Invoice $resource
 */
class InvoiceResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'organization_id' => $this->resource->organization_id,
            'invoice_recipient_id' => $this->resource->invoice_recipient_id,
            'reference' => $this->resource->reference,
            'seller_name' => $this->resource->seller_name,
            'recipient' => $this->resource->recipient->name,
            'status' => $this->resource->status->value,
            'status_label' => Str::headline($this->resource->status->value),
            'currency' => $this->resource->currency,
            'date' => $this->formatDate($this->resource->date),
            'due_at' => $this->formatDate($this->resource->due_at),
            'paid_date' => $this->formatDate($this->resource->paid_date),
            'total' => $this->resource->totals()['total'],
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
