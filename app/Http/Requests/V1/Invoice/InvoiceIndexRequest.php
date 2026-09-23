<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Invoice;

use App\Enums\InvoiceStatus;
use App\Http\Requests\V1\BaseFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class InvoiceIndexRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule|\Illuminate\Contracts\Validation\Rule|\Closure>>
     */
    public function rules(): array
    {
        return [
            'page' => [
                'integer',
                'min:1',
                'max:2147483647',
            ],
            'status' => [
                'nullable',
                Rule::enum(InvoiceStatus::class),
            ],
        ];
    }

    public function getStatus(): ?InvoiceStatus
    {
        $status = $this->input('status');

        return $status !== null ? InvoiceStatus::from($status) : null;
    }
}
