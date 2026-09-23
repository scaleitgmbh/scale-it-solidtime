<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceEntry;
use App\Models\InvoiceRecipient;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'invoice_recipient_id' => InvoiceRecipient::factory(),
            'reference' => 'INV-'.$this->faker->unique()->numerify('####'),
            'status' => InvoiceStatus::Draft,
            'currency' => 'EUR',
            'date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'due_at' => null,
            'paid_date' => null,
            'billing_period_start' => null,
            'billing_period_end' => null,
            'seller_name' => $this->faker->company(),
            'seller_vatin' => null,
            'seller_address_line_1' => null,
            'seller_address_line_2' => null,
            'seller_address_line_3' => null,
            'seller_address_post_code' => null,
            'seller_address_city' => null,
            'seller_address_country' => null,
            'seller_phone' => null,
            'seller_email' => null,
            'payment_iban' => null,
            'payment_terms' => null,
            'tax_rate' => null,
            'discount_type' => null,
            'discount_amount' => null,
            'is_eu_reverse_charge' => false,
            'footer' => null,
            'notes' => null,
        ];
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->getKey(),
        ]);
    }

    public function forRecipient(InvoiceRecipient $recipient): self
    {
        return $this->state(fn (array $attributes) => [
            'invoice_recipient_id' => $recipient->getKey(),
            'organization_id' => $recipient->organization_id,
        ]);
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Draft,
        ]);
    }

    public function sent(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Sent,
        ]);
    }

    public function paid(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'paid_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Cancelled,
        ]);
    }

    public function withEntries(int $count = 1): self
    {
        return $this->afterCreating(function (Invoice $invoice) use ($count): void {
            InvoiceEntry::factory()
                ->count($count)
                ->sequence(fn (Sequence $sequence) => ['order_index' => $sequence->index])
                ->for($invoice, 'invoice')
                ->create();
        });
    }
}
