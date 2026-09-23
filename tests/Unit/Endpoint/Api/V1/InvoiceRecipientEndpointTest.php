<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Http\Controllers\Api\V1\InvoiceRecipientController;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceRecipient;
use App\Models\Organization;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(InvoiceRecipientController::class)]
class InvoiceRecipientEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission(): void
    {
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.invoice-recipients.index', [$data->organization->getKey()]));

        $response->assertForbidden();
    }

    public function test_index_endpoint_returns_non_archived_recipients_by_default(): void
    {
        $data = $this->createUserWithPermission(['invoice-recipients:view']);
        $archived = InvoiceRecipient::factory()->forOrganization($data->organization)->archived()->create();
        $active = InvoiceRecipient::factory()->forOrganization($data->organization)->createMany(2);
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.invoice-recipients.index', [$data->organization->getKey()]));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing($active->pluck('id')->toArray(), $response->json('data.*.id'));
    }

    public function test_store_endpoint_fails_if_user_has_no_permission(): void
    {
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoice-recipients.store', [$data->organization->getKey()]), [
            'name' => 'Acme Corp',
        ]);

        $response->assertForbidden();
    }

    public function test_store_endpoint_creates_recipient_linked_to_client(): void
    {
        $data = $this->createUserWithPermission(['invoice-recipients:create']);
        $client = Client::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoice-recipients.store', [$data->organization->getKey()]), [
            'name' => 'Acme Corp',
            'client_id' => $client->getKey(),
            'email' => 'billing@acme.test',
        ]);

        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', 'Acme Corp')
            ->where('data.client_id', $client->getKey())
            ->where('data.invoices_count', 0)
            ->where('data.has_non_draft_invoices', false)
            ->etc()
        );
    }

    public function test_store_endpoint_fails_if_client_belongs_to_different_organization(): void
    {
        $data = $this->createUserWithPermission(['invoice-recipients:create']);
        $otherOrganization = Organization::factory()->create();
        $client = Client::factory()->forOrganization($otherOrganization)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoice-recipients.store', [$data->organization->getKey()]), [
            'name' => 'Acme Corp',
            'client_id' => $client->getKey(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['client_id']);
    }

    public function test_destroy_endpoint_fails_if_recipient_has_invoices(): void
    {
        $data = $this->createUserWithPermission(['invoice-recipients:delete']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        Invoice::factory()->forRecipient($recipient)->create();
        Passport::actingAs($data->user);

        $response = $this->deleteJson(route('api.v1.invoice-recipients.destroy', [$data->organization->getKey(), $recipient->getKey()]));

        $response->assertStatus(400);
        $this->assertDatabaseHas(InvoiceRecipient::class, ['id' => $recipient->getKey()]);
    }

    public function test_destroy_endpoint_deletes_recipient_without_invoices(): void
    {
        $data = $this->createUserWithPermission(['invoice-recipients:delete']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        $response = $this->deleteJson(route('api.v1.invoice-recipients.destroy', [$data->organization->getKey(), $recipient->getKey()]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing(InvoiceRecipient::class, ['id' => $recipient->getKey()]);
    }

    public function test_duplicate_endpoint_creates_a_copy(): void
    {
        $data = $this->createUserWithPermission(['invoice-recipients:create']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create(['name' => 'Acme Corp']);
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoice-recipients.duplicate', [$data->organization->getKey(), $recipient->getKey()]), []);

        $response->assertStatus(201);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.name', 'Acme Corp (Copy)')
            ->etc()
        );
        $this->assertDatabaseCount(InvoiceRecipient::class, 2);
    }
}
