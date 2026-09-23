<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InvoiceSetting;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceSetting>
 */
class InvoiceSettingFactory extends Factory
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
            'seller_name' => $this->faker->company(),
            'seller_vatin' => null,
            'seller_address_line_1' => $this->faker->streetAddress(),
            'seller_address_line_2' => null,
            'seller_address_line_3' => null,
            'seller_address_post_code' => $this->faker->postcode(),
            'seller_address_city' => $this->faker->city(),
            'seller_address_country' => $this->faker->countryCode(),
            'seller_phone' => null,
            'seller_email' => $this->faker->companyEmail(),
            'footer_default' => null,
            'notes_default' => null,
            'tax_rate_default' => null,
            'invoice_number_prefix' => null,
            'next_invoice_number' => 1,
        ];
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->getKey(),
        ]);
    }
}
