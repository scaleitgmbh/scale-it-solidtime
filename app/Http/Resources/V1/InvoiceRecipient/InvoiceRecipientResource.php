<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\InvoiceRecipient;

use App\Enums\InvoiceStatus;
use App\Http\Resources\V1\BaseResource;
use App\Models\InvoiceRecipient;
use Illuminate\Http\Request;

/**
 * @property InvoiceRecipient $resource
 */
class InvoiceRecipientResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|bool|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'organization_id' => $this->resource->organization_id,
            'client_id' => $this->resource->client_id,
            'name' => $this->resource->name,
            'vatin' => $this->resource->vatin,
            'address_line_1' => $this->resource->address_line_1,
            'address_line_2' => $this->resource->address_line_2,
            'address_line_3' => $this->resource->address_line_3,
            'address_post_code' => $this->resource->address_post_code,
            'address_city' => $this->resource->address_city,
            'address_country' => $this->resource->address_country,
            'phone' => $this->resource->phone,
            'email' => $this->resource->email,
            'is_archived' => $this->resource->is_archived,
            'archived_at' => $this->formatDateTime($this->resource->archived_at),
            'invoices_count' => $this->whenCounted('invoices', null, fn () => $this->resource->invoices()->count()),
            'has_non_draft_invoices' => $this->resource->invoices()->where('status', '!=', InvoiceStatus::Draft)->exists(),
            'created_at' => $this->formatDateTime($this->resource->created_at),
            'updated_at' => $this->formatDateTime($this->resource->updated_at),
        ];
    }
}
