<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Invoice;

use App\Http\Requests\V1\BaseFormRequest;
use App\Models\InvoiceRecipient;
use App\Models\Organization;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Korridor\LaravelModelValidationRules\Rules\ExistsEloquent;

/**
 * @property Organization $organization Organization from model binding
 */
class InvoiceGenerateEntriesRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'invoice_recipient_id' => [
                'required',
                'string',
                ExistsEloquent::make(InvoiceRecipient::class, null, function (Builder $builder): Builder {
                    /** @var Builder<InvoiceRecipient> $builder */
                    return $builder->whereBelongsTo($this->organization, 'organization');
                })->uuid(),
            ],
            'start' => [
                'required',
                'date_format:Y-m-d',
            ],
            'end' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start',
            ],
            'group_by' => [
                'sometimes',
                'string',
                'in:project,project_task',
            ],
        ];
    }

    public function getStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', (string) $this->input('start'), 'UTC')->startOfDay();
    }

    public function getEnd(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', (string) $this->input('end'), 'UTC')->endOfDay();
    }

    public function getGroupByTask(): bool
    {
        return $this->input('group_by', 'project') === 'project_task';
    }
}
