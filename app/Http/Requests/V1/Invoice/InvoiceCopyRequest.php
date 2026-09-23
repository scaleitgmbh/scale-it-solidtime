<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Invoice;

use App\Http\Requests\V1\BaseFormRequest;
use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Korridor\LaravelModelValidationRules\Rules\UniqueEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class InvoiceCopyRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'reference' => [
                'required',
                'string',
                'max:255',
                UniqueEloquent::make(Invoice::class, 'reference', function (Builder $builder): Builder {
                    /** @var Builder<Invoice> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->withCustomTranslation('validation.invoice_reference_already_exists'),
            ],
        ];
    }
}
