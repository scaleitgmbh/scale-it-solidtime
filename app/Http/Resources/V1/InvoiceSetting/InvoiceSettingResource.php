<?php

declare(strict_types=1);

namespace App\Http\Resources\V1\InvoiceSetting;

use App\Http\Resources\V1\BaseResource;
use App\Models\InvoiceSetting;
use Illuminate\Http\Request;

/**
 * @property InvoiceSetting $resource
 */
class InvoiceSettingResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, string|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'organization_id' => $this->resource->organization_id,
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
            'footer_default' => $this->resource->footer_default,
            'notes_default' => $this->resource->notes_default,
            'tax_rate_default' => $this->resource->tax_rate_default,
            'invoice_number_prefix' => $this->resource->getReferencePrefix(),
            'next_invoice_number' => $this->resource->next_invoice_number,
        ];
    }
}
