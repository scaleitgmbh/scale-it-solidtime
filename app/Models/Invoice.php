<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceDiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use App\Service\InvoiceCalculator;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $organization_id
 * @property string $invoice_recipient_id
 * @property string $reference
 * @property InvoiceStatus $status
 * @property string $currency
 * @property Carbon $date
 * @property Carbon|null $due_at
 * @property Carbon|null $paid_date
 * @property Carbon|null $billing_period_start
 * @property Carbon|null $billing_period_end
 * @property string $seller_name
 * @property string|null $seller_vatin
 * @property string|null $seller_address_line_1
 * @property string|null $seller_address_line_2
 * @property string|null $seller_address_line_3
 * @property string|null $seller_address_post_code
 * @property string|null $seller_address_city
 * @property string|null $seller_address_country
 * @property string|null $seller_phone
 * @property string|null $seller_email
 * @property string|null $payment_iban
 * @property string|null $payment_terms
 * @property int|null $tax_rate
 * @property InvoiceDiscountType|null $discount_type
 * @property int|null $discount_amount
 * @property bool $is_eu_reverse_charge
 * @property string|null $footer
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read InvoiceRecipient $recipient
 * @property-read Collection<int, InvoiceEntry> $entries
 *
 * @method static InvoiceFactory factory()
 */
class Invoice extends Model implements AuditableContract
{
    use CustomAuditable;

    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => InvoiceStatus::class,
        'discount_type' => InvoiceDiscountType::class,
        'date' => 'date',
        'due_at' => 'date',
        'paid_date' => 'date',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'tax_rate' => 'integer',
        'discount_amount' => 'integer',
        'is_eu_reverse_charge' => 'boolean',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return BelongsTo<InvoiceRecipient, $this>
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(InvoiceRecipient::class, 'invoice_recipient_id');
    }

    /**
     * @return HasMany<InvoiceEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(InvoiceEntry::class, 'invoice_id')->orderBy('order_index');
    }

    /**
     * @return array{subtotal: int, discount_total: int, tax_total: int, total: int}
     */
    public function totals(): array
    {
        return app(InvoiceCalculator::class)->calculate($this);
    }

    public function isEditable(): bool
    {
        return $this->status === InvoiceStatus::Draft;
    }
}
