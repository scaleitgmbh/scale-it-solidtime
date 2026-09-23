<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\CustomAuditable;
use App\Models\Concerns\HasUuids;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Database\Factories\InvoiceEntryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * @property string $id
 * @property string $invoice_id
 * @property string $name
 * @property string|null $description
 * @property int $unit_price
 * @property float $quantity
 * @property int $order_index
 * @property-read int $line_total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Invoice $invoice
 * @property-read Collection<int, TimeEntry> $timeEntries
 *
 * @method static InvoiceEntryFactory factory()
 */
class InvoiceEntry extends Model implements AuditableContract
{
    use CustomAuditable;

    /** @use HasFactory<InvoiceEntryFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'name' => 'string',
        'unit_price' => 'integer',
        'quantity' => 'decimal:4',
        'order_index' => 'integer',
    ];

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, 'invoice_entry_id');
    }

    /**
     * @return Attribute<int, never>
     */
    protected function lineTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): int => BigDecimal::of((string) $this->unit_price)
                ->multipliedBy((string) $this->quantity)
                ->toScale(0, RoundingMode::HALF_UP)
                ->toInt(),
        );
    }
}
