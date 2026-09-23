<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\Invoice;

use App\Http\Resources\V1\BaseResource;
use App\Http\Resources\V1\InvoiceRecipient\InvoiceRecipientResource;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @property Invoice $resource
 */
class DetailedInvoiceResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'organization_id' => $this->resource->organization_id,
            'invoice_recipient_id' => $this->resource->invoice_recipient_id,
            'reference' => $this->resource->reference,
            'status' => $this->resource->status->value,
            'status_label' => Str::headline($this->resource->status->value),
            'currency' => $this->resource->currency,
            'date' => $this->formatDate($this->resource->date),
            'due_at' => $this->formatDate($this->resource->due_at),
            'paid_date' => $this->formatDate($this->resource->paid_date),
            'billing_period_start' => $this->formatDate($this->resource->billing_period_start),
            'billing_period_end' => $this->formatDate($this->resource->billing_period_end),
            'seller_name' => $this->resource->seller_name,
            'seller_vatin' => $this->resource->seller_vatin,
            'seller_address_line_1' => $this->resource->seller_address_line_1,
            'seller_address_line_2' => $this->resource->seller_address_line_2,
            'seller_address_line_3' => $this->resource->seller_address_line_3,
            'seller_address_post_code' => $this->resource->seller_address_post_code,
            'seller_address_city' => $this->resource->seller_address_city,
            'seller_address_country' => $this->resource->seller_address_country,
            'seller_phone' => $this->resource->seller_phone,
            'seller_email' => $this->resource->seller_email,
            'payment_iban' => $this->resource->payment_iban,
            'payment_terms' => $this->resource->payment_terms,
            'tax_rate' => $this->resource->tax_rate,
            'discount_type' => $this->resource->discount_type?->value,
            'discount_amount' => $this->resource->discount_amount,
            'is_eu_reverse_charge' => $this->resource->is_eu_reverse_charge,
            'footer' => $this->resource->footer,
            'notes' => $this->resource->notes,
            'recipient' => new InvoiceRecipientResource($this->resource->recipient),
            'entries' => InvoiceEntryResource::collection($this->resource->entries),
            'totals' => $this->resource->totals(),
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
