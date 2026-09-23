<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\InvoiceSetting;

use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * @property Organization $organization Organization from model binding
 */
class InvoiceSettingUpdateRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'seller_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_vatin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_line_3' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_post_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'footer_default' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'notes_default' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'tax_rate_default' => array_merge(['sometimes', 'nullable'], $this->moneyRules()),
            'invoice_number_prefix' => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}
