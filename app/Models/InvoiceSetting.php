<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use Database\Factories\InvoiceSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $organization_id
 * @property string|null $seller_name
 * @property string|null $seller_vatin
 * @property string|null $seller_address_line_1
 * @property string|null $seller_address_line_2
 * @property string|null $seller_address_line_3
 * @property string|null $seller_address_post_code
 * @property string|null $seller_address_city
 * @property string|null $seller_address_country
 * @property string|null $seller_phone
 * @property string|null $seller_email
 * @property string|null $footer_default
 * @property string|null $notes_default
 * @property int|null $tax_rate_default
 * @property string|null $invoice_number_prefix
 * @property int $next_invoice_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 *
 * @method static InvoiceSettingFactory factory()
 */
class InvoiceSetting extends Model implements AuditableContract
{
    use CustomAuditable;

    /** @use HasFactory<InvoiceSettingFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'next_invoice_number' => 1,
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tax_rate_default' => 'integer',
        'next_invoice_number' => 'integer',
    ];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function getReferencePrefix(): string
    {
        return $this->invoice_number_prefix ?? 'INV-';
    }
}
