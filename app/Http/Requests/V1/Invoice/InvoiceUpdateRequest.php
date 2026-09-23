<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Invoice;

use App\Enums\InvoiceDiscountType;
use App\Enums\InvoiceStatus;
use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Invoice;
use App\Models\InvoiceRecipient;
use App\Models\Organization;
use App\Models\TimeEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property Organization $organization Organization from model binding
 * @property Invoice $invoice Invoice from model binding
 */
class InvoiceUpdateRequest extends BaseFormRequest
{
    /**
     * Fields that can only be changed while the invoice is still a draft.
     *
     * @var array<int, string>
     */
    private const array LOCKED_AFTER_DRAFT_FIELDS = [
        'invoice_recipient_id',
        'reference',
        'currency',
        'date',
        'billing_period_start',
        'billing_period_end',
        'seller_name',
        'seller_vatin',
        'seller_address_line_1',
        'seller_address_line_2',
        'seller_address_line_3',
        'seller_address_post_code',
        'seller_address_city',
        'seller_address_country',
        'seller_phone',
        'seller_email',
        'payment_iban',
        'tax_rate',
        'discount_amount',
        'discount_type',
        'is_eu_reverse_charge',
        'entries',
    ];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|\Illuminate\Contracts\Validation\Rule|\Closure|\Stringable>>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)],
            'invoice_recipient_id' => [
                'sometimes',
                'string',
                ExistsEloquent::make(InvoiceRecipient::class, null, function (Builder $builder): Builder {
                    /** @var Builder<InvoiceRecipient> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            'reference' => [
                'sometimes',
                'string',
                'max:255',
                UniqueEloquent::make(Invoice::class, 'reference', function (Builder $builder): Builder {
                    /** @var Builder<Invoice> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->ignore($this->invoice->getKey())->withCustomTranslation('validation.invoice_reference_already_exists'),
            ],
            'currency' => ['sometimes', 'string', Rule::in([$this->organization->currency])],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'due_at' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'paid_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'billing_period_start' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'billing_period_end' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:billing_period_start'],
            'seller_name' => ['sometimes', 'string', 'max:255'],
            'seller_vatin' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_line_3' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_post_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_address_country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seller_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'payment_iban' => ['sometimes', 'nullable', 'string', 'max:34'],
            'payment_terms' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'tax_rate' => array_merge(['sometimes', 'nullable'], $this->moneyRules()),
            'discount_amount' => array_merge(['sometimes', 'nullable', 'required_with:discount_type'], $this->moneyRules(true)),
            'discount_type' => ['sometimes', 'nullable', 'required_with:discount_amount', Rule::enum(InvoiceDiscountType::class)],
            'is_eu_reverse_charge' => ['sometimes', 'boolean'],
            'footer' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'entries' => ['sometimes', 'array'],
            'entries.*.id' => ['sometimes', 'nullable', 'string'],
            'entries.*.name' => ['required', 'string', 'max:255'],
            'entries.*.description' => ['nullable', 'string', 'max:2000'],
            'entries.*.unit_price' => array_merge(['required'], $this->moneyRules(true)),
            'entries.*.quantity' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'entries.*.time_entry_ids' => ['sometimes', 'array'],
            'entries.*.time_entry_ids.*' => [
                'string',
                ExistsEloquent::make(TimeEntry::class, null, function (Builder $builder): Builder {
                    /** @var Builder<TimeEntry> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->invoice->status === InvoiceStatus::Draft) {
                return;
            }
            foreach (self::LOCKED_AFTER_DRAFT_FIELDS as $field) {
                if ($this->has($field)) {
                    $validator->errors()->add($field, __('validation.invoice_field_locked'));
                }
            }
        });
    }

    public function getStatus(): ?InvoiceStatus
    {
        $status = $this->input('status');

        return $status !== null ? InvoiceStatus::from($status) : null;
    }
}
