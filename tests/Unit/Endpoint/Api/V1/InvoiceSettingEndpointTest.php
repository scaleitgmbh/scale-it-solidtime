<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\InvoiceSettingController;
use App\Models\InvoiceSetting;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(InvoiceSettingController::class)]
class InvoiceSettingEndpointTest extends ApiEndpointTestAbstract
{
    public function test_show_endpoint_fails_if_user_has_no_permission(): void
    {
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.invoice-settings.show', [$data->organization->getKey()]));

        $response->assertForbidden();
    }

    public function test_show_endpoint_creates_settings_lazily_with_organization_name_as_default_seller_name(): void
    {
        $data = $this->createUserWithPermission(['invoice-settings:view']);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.invoice-settings.show', [$data->organization->getKey()]));

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.seller_name', $data->organization->name)
            ->where('data.next_invoice_number', 1)
            ->where('data.invoice_number_prefix', 'INV-')
            ->etc()
        );
        $this->assertDatabaseCount(InvoiceSetting::class, 1);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission(): void
    {
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $response = $this->putJson(route('api.v1.invoice-settings.update', [$data->organization->getKey()]), [
            'seller_name' => 'Acme Inc.',
        ]);

        $response->assertForbidden();
    }

    public function test_update_endpoint_updates_settings(): void
    {
        $data = $this->createUserWithPermission(['invoice-settings:update']);
        Passport::actingAs($data->user);

        $response = $this->putJson(route('api.v1.invoice-settings.update', [$data->organization->getKey()]), [
            'seller_name' => 'Acme Inc.',
            'seller_vatin' => 'DE123456789',
            'tax_rate_default' => 1900,
        ]);

        $response->assertStatus(200);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.seller_name', 'Acme Inc.')
            ->where('data.seller_vatin', 'DE123456789')
            ->where('data.tax_rate_default', 1900)
            ->etc()
        );
        $this->assertDatabaseHas(InvoiceSetting::class, [
            'organization_id' => $data->organization->getKey(),
            'seller_name' => 'Acme Inc.',
            'seller_vatin' => 'DE123456789',
            'tax_rate_default' => 1900,
        ]);
    }
}
