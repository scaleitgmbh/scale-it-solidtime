<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Client;
use App\Models\InvoiceRecipient;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceRecipient>
 */
class InvoiceRecipientFactory extends Factory
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
            'client_id' => null,
            'name' => $this->faker->company(),
            'vatin' => null,
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => null,
            'address_line_3' => null,
            'address_post_code' => $this->faker->postcode(),
            'address_city' => $this->faker->city(),
            'address_country' => $this->faker->countryCode(),
            'phone' => null,
            'email' => $this->faker->companyEmail(),
            'archived_at' => null,
        ];
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->getKey(),
        ]);
    }

    public function forClient(Client $client): self
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->getKey(),
            'organization_id' => $client->organization_id,
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => $this->faker->dateTime(),
        ]);
    }
}
