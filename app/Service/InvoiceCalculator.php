<?php

declare(strict_types=1);

namespace App\Service;

use App\Enums\InvoiceDiscountType;
use App\Models\Invoice;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class InvoiceCalculator
{
    /**
     * Calculates the monetary totals of an invoice (all values in minor currency units, e.g. cents).
     *
     * Discount and tax rates are stored as basis points (1/100th of a percent), e.g. 1900 = 19.00%.
     * A fixed discount is stored in minor currency units directly.
     *
     * @return array{subtotal: int, discount_total: int, tax_total: int, total: int}
     */
    public function calculate(Invoice $invoice): array
    {
        $subtotal = BigDecimal::zero();
        foreach ($invoice->entries as $entry) {
            $subtotal = $subtotal->plus(
                BigDecimal::of((string) $entry->unit_price)
                    ->multipliedBy((string) $entry->quantity)
                    ->toScale(0, RoundingMode::HALF_UP)
            );
        }

        $discountTotal = BigDecimal::zero();
        if ($invoice->discount_type !== null && $invoice->discount_amount !== null && $invoice->discount_amount > 0) {
            if ($invoice->discount_type === InvoiceDiscountType::Fixed) {
                $discountTotal = BigDecimal::of((string) $invoice->discount_amount);
            } else {
                $discountTotal = $subtotal
                    ->multipliedBy((string) $invoice->discount_amount)
                    ->dividedBy(10000, 0, RoundingMode::HALF_UP);
            }
            // A discount can never exceed the subtotal.
            if ($discountTotal->isGreaterThan($subtotal)) {
                $discountTotal = $subtotal;
            }
        }

        $taxableBase = $subtotal->minus($discountTotal);

        $taxTotal = BigDecimal::zero();
        if ($invoice->tax_rate !== null && $invoice->tax_rate > 0) {
            $taxTotal = $taxableBase
                ->multipliedBy((string) $invoice->tax_rate)
                ->dividedBy(10000, 0, RoundingMode::HALF_UP);
        }

        $total = $taxableBase->plus($taxTotal);

        return [
            'subtotal' => $subtotal->toInt(),
            'discount_total' => $discountTotal->toInt(),
            'tax_total' => $taxTotal->toInt(),
            'total' => $total->toInt(),
        ];
    }
}
