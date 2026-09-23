<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Enums\InvoiceDiscountType;
use App\Models\Invoice;
use App\Models\InvoiceEntry;
use App\Models\InvoiceRecipient;
use App\Service\InvoiceCalculator;
use PHPUnit\Framework\Attributes\UsesClass;
use Tests\TestCaseWithDatabase;

#[UsesClass(InvoiceCalculator::class)]
class InvoiceCalculatorTest extends TestCaseWithDatabase
{
    public function test_calculate_returns_zero_totals_for_invoice_without_entries(): void
    {
        $recipient = InvoiceRecipient::factory()->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->create();

        $totals = app(InvoiceCalculator::class)->calculate($invoice);

        $this->assertSame([
            'subtotal' => 0,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 0,
        ], $totals);
    }

    public function test_calculate_sums_line_totals_for_subtotal(): void
    {
        $recipient = InvoiceRecipient::factory()->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->create();
        InvoiceEntry::factory()->forInvoice($invoice)->create(['unit_price' => 10000, 'quantity' => 2]);
        InvoiceEntry::factory()->forInvoice($invoice)->create(['unit_price' => 5000, 'quantity' => 1]);

        $totals = app(InvoiceCalculator::class)->calculate($invoice->fresh(['entries']));

        $this->assertSame(25000, $totals['subtotal']);
        $this->assertSame(0, $totals['discount_total']);
        $this->assertSame(0, $totals['tax_total']);
        $this->assertSame(25000, $totals['total']);
    }

    public function test_calculate_applies_percentage_discount_and_tax(): void
    {
        $recipient = InvoiceRecipient::factory()->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->create([
            'discount_type' => InvoiceDiscountType::Percentage,
            'discount_amount' => 1000, // 10.00%
            'tax_rate' => 2000, // 20.00%
        ]);
        InvoiceEntry::factory()->forInvoice($invoice)->create(['unit_price' => 10000, 'quantity' => 1]);

        $totals = app(InvoiceCalculator::class)->calculate($invoice->fresh(['entries']));

        // subtotal 10000, discount 10% = 1000, taxable base = 9000, tax 20% = 1800, total = 10800
        $this->assertSame(10000, $totals['subtotal']);
        $this->assertSame(1000, $totals['discount_total']);
        $this->assertSame(1800, $totals['tax_total']);
        $this->assertSame(10800, $totals['total']);
    }

    public function test_calculate_applies_fixed_discount(): void
    {
        $recipient = InvoiceRecipient::factory()->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->create([
            'discount_type' => InvoiceDiscountType::Fixed,
            'discount_amount' => 500,
        ]);
        InvoiceEntry::factory()->forInvoice($invoice)->create(['unit_price' => 1000, 'quantity' => 1]);

        $totals = app(InvoiceCalculator::class)->calculate($invoice->fresh(['entries']));

        $this->assertSame(1000, $totals['subtotal']);
        $this->assertSame(500, $totals['discount_total']);
        $this->assertSame(500, $totals['total']);
    }

    public function test_calculate_clamps_discount_to_subtotal(): void
    {
        $recipient = InvoiceRecipient::factory()->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->create([
            'discount_type' => InvoiceDiscountType::Fixed,
            'discount_amount' => 999999,
        ]);
        InvoiceEntry::factory()->forInvoice($invoice)->create(['unit_price' => 1000, 'quantity' => 1]);

        $totals = app(InvoiceCalculator::class)->calculate($invoice->fresh(['entries']));

        $this->assertSame(1000, $totals['discount_total']);
        $this->assertSame(0, $totals['total']);
    }
}
