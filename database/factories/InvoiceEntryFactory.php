<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceEntry>
 */
class InvoiceEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'name' => $this->faker->words(3, true),
            'description' => null,
            'unit_price' => $this->faker->numberBetween(1000, 20000),
            'quantity' => $this->faker->randomFloat(2, 1, 40),
            'order_index' => 0,
        ];
    }

    public function forInvoice(Invoice $invoice): self
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->getKey(),
        ]);
    }
}
