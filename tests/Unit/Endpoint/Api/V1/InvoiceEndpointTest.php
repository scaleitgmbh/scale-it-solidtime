<?php

declare(strict_types=1);

namespace Tests\Unit\Endpoint\Api\V1;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceRecipient;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\UsesClass;

#[UsesClass(InvoiceController::class)]
class InvoiceEndpointTest extends ApiEndpointTestAbstract
{
    public function test_index_endpoint_fails_if_user_has_no_permission(): void
    {
        $data = $this->createUserWithPermission();
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.invoices.index', [$data->organization->getKey()]));

        $response->assertForbidden();
    }

    public function test_index_endpoint_filters_by_status(): void
    {
        $data = $this->createUserWithPermission(['invoices:view']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $draft = Invoice::factory()->forRecipient($recipient)->draft()->create();
        Invoice::factory()->forRecipient($recipient)->sent()->create();
        Passport::actingAs($data->user);

        $response = $this->getJson(route('api.v1.invoices.index', [$data->organization->getKey(), 'status' => 'draft']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $draft->getKey());
    }

    public function test_store_endpoint_fails_if_user_has_no_permission(): void
    {
        $data = $this->createUserWithPermission();
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), [
            'invoice_recipient_id' => $recipient->getKey(),
            'date' => '2026-01-01',
            'reference' => 'INV-0001',
            'currency' => $data->organization->currency,
            'seller_name' => 'Acme Inc.',
        ]);

        $response->assertForbidden();
    }

    public function test_store_endpoint_fails_if_currency_does_not_match_organization_currency(): void
    {
        $data = $this->createUserWithPermission(['invoices:create']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), [
            'invoice_recipient_id' => $recipient->getKey(),
            'date' => '2026-01-01',
            'currency' => $data->organization->currency === 'USD' ? 'EUR' : 'USD',
            'seller_name' => 'Acme Inc.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency']);
    }

    public function test_store_endpoint_auto_generates_sequential_reference_when_omitted(): void
    {
        $data = $this->createUserWithPermission(['invoices:create']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        $payload = [
            'invoice_recipient_id' => $recipient->getKey(),
            'date' => '2026-01-01',
            'currency' => $data->organization->currency,
            'seller_name' => 'Acme Inc.',
        ];

        $response1 = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), $payload);
        $response2 = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), $payload);

        $response1->assertStatus(201);
        $response2->assertStatus(201);
        $this->assertSame('INV-0001', $response1->json('data.reference'));
        $this->assertSame('INV-0002', $response2->json('data.reference'));
    }

    public function test_store_endpoint_fails_if_reference_already_exists_in_organization(): void
    {
        $data = $this->createUserWithPermission(['invoices:create']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        Invoice::factory()->forRecipient($recipient)->create(['reference' => 'INV-DUPLICATE']);
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), [
            'invoice_recipient_id' => $recipient->getKey(),
            'reference' => 'INV-DUPLICATE',
            'date' => '2026-01-01',
            'currency' => $data->organization->currency,
            'seller_name' => 'Acme Inc.',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['reference']);
    }

    public function test_store_endpoint_creates_invoice_with_entries_and_correct_totals(): void
    {
        $data = $this->createUserWithPermission(['invoices:create']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoices.store', [$data->organization->getKey()]), [
            'invoice_recipient_id' => $recipient->getKey(),
            'date' => '2026-01-01',
            'currency' => $data->organization->currency,
            'seller_name' => 'Acme Inc.',
            'tax_rate' => 1900,
            'discount_type' => 'fixed',
            'discount_amount' => 100,
            'entries' => [
                ['name' => 'Consulting', 'unit_price' => 10000, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(201);
        // subtotal = 10000 * 2 = 20000; discount = 100; taxable = 19900; tax = round(19900 * 0.19) = 3781; total = 23681
        $response->assertJson(fn (AssertableJson $json) => $json
            ->has('data')
            ->where('data.status', 'draft')
            ->where('data.totals.subtotal', 20000)
            ->where('data.totals.discount_total', 100)
            ->where('data.totals.tax_total', 3781)
            ->where('data.totals.total', 23681)
            ->etc()
        );
    }

    public function test_generate_entries_endpoint_returns_empty_array_when_recipient_has_no_linked_client(): void
    {
        $data = $this->createUserWithPermission(['invoices:create']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create(['client_id' => null]);
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoices.generate-entries', [$data->organization->getKey()]), [
            'invoice_recipient_id' => $recipient->getKey(),
            'start' => '2026-01-01',
            'end' => '2026-01-31',
        ]);

        $response->assertStatus(200);
        $response->assertExactJson(['data' => []]);
    }

    public function test_generate_entries_endpoint_groups_billable_time_entries_by_project(): void
    {
        $data = $this->createUserWithPermission(['invoices:create']);
        $client = Client::factory()->forOrganization($data->organization)->create();
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->forClient($client)->create();
        $project = Project::factory()->forOrganization($data->organization)->forClient($client)->create();
        TimeEntry::factory()
            ->forOrganization($data->organization)
            ->forMember($data->member)
            ->forProject($project)
            ->billable()
            ->billableRate(6000)
            ->startWithDuration(Carbon::parse('2026-01-10 10:00:00'), 3600)
            ->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoices.generate-entries', [$data->organization->getKey()]), [
            'invoice_recipient_id' => $recipient->getKey(),
            'start' => '2026-01-01',
            'end' => '2026-01-31',
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', $project->name);
        $response->assertJsonPath('data.0.unit_price', 6000);
        $this->assertEqualsWithDelta(1.0, $response->json('data.0.quantity'), 0.0001);
    }

    public function test_update_endpoint_fails_if_user_has_no_permission(): void
    {
        $data = $this->createUserWithPermission();
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->create();
        Passport::actingAs($data->user);

        $response = $this->putJson(route('api.v1.invoices.update', [$data->organization->getKey(), $invoice->getKey()]), [
            'status' => 'sent',
        ]);

        $response->assertForbidden();
    }

    public function test_update_endpoint_allows_changing_entries_while_draft(): void
    {
        $data = $this->createUserWithPermission(['invoices:update']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->draft()->withEntries(1)->create();
        Passport::actingAs($data->user);

        $response = $this->putJson(route('api.v1.invoices.update', [$data->organization->getKey(), $invoice->getKey()]), [
            'entries' => [
                ['name' => 'New line', 'unit_price' => 5000, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.entries');
        $response->assertJsonPath('data.entries.0.name', 'New line');
    }

    public function test_update_endpoint_rejects_changing_entries_once_invoice_is_sent(): void
    {
        $data = $this->createUserWithPermission(['invoices:update']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->sent()->withEntries(1)->create();
        Passport::actingAs($data->user);

        $response = $this->putJson(route('api.v1.invoices.update', [$data->organization->getKey(), $invoice->getKey()]), [
            'entries' => [
                ['name' => 'New line', 'unit_price' => 5000, 'quantity' => 3],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['entries']);
    }

    public function test_update_endpoint_allows_valid_status_transition_from_draft_to_sent(): void
    {
        $data = $this->createUserWithPermission(['invoices:update']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->draft()->create();
        Passport::actingAs($data->user);

        $response = $this->putJson(route('api.v1.invoices.update', [$data->organization->getKey(), $invoice->getKey()]), [
            'status' => 'sent',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'sent');
    }

    public function test_update_endpoint_rejects_invalid_status_transition_from_sent_to_draft(): void
    {
        $data = $this->createUserWithPermission(['invoices:update']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->sent()->create();
        Passport::actingAs($data->user);

        $response = $this->putJson(route('api.v1.invoices.update', [$data->organization->getKey(), $invoice->getKey()]), [
            'status' => 'draft',
        ]);

        $response->assertStatus(422);
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Sent, $invoice->status);
    }

    public function test_destroy_endpoint_fails_if_invoice_is_not_draft(): void
    {
        $data = $this->createUserWithPermission(['invoices:delete']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->sent()->create();
        Passport::actingAs($data->user);

        $response = $this->deleteJson(route('api.v1.invoices.destroy', [$data->organization->getKey(), $invoice->getKey()]));

        $response->assertStatus(422);
        $this->assertDatabaseHas(Invoice::class, ['id' => $invoice->getKey()]);
    }

    public function test_destroy_endpoint_deletes_draft_invoice(): void
    {
        $data = $this->createUserWithPermission(['invoices:delete']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->draft()->create();
        Passport::actingAs($data->user);

        $response = $this->deleteJson(route('api.v1.invoices.destroy', [$data->organization->getKey(), $invoice->getKey()]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing(Invoice::class, ['id' => $invoice->getKey()]);
    }

    public function test_copy_endpoint_creates_new_draft_with_same_entries(): void
    {
        $data = $this->createUserWithPermission(['invoices:create']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->sent()->withEntries(2)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoices.copy', [$data->organization->getKey(), $invoice->getKey()]), [
            'reference' => 'INV-COPY-1',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'draft');
        $response->assertJsonPath('data.reference', 'INV-COPY-1');
        $response->assertJsonCount(2, 'data.entries');
        $this->assertDatabaseCount(Invoice::class, 2);
    }

    public function test_download_endpoint_fails_if_user_has_no_permission(): void
    {
        $data = $this->createUserWithPermission();
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->create();
        Passport::actingAs($data->user);

        $response = $this->postJson(route('api.v1.invoices.download', [$data->organization->getKey(), $invoice->getKey()]));

        $response->assertForbidden();
    }

    public function test_download_endpoint_fails_if_gotenberg_is_not_configured(): void
    {
        $data = $this->createUserWithPermission(['invoices:download']);
        $recipient = InvoiceRecipient::factory()->forOrganization($data->organization)->create();
        $invoice = Invoice::factory()->forRecipient($recipient)->withEntries(1)->create();
        Passport::actingAs($data->user);
        Config::set('services.gotenberg.url', null);

        $response = $this->postJson(route('api.v1.invoices.download', [$data->organization->getKey(), $invoice->getKey()]));

        $response->assertStatus(400);
        $response->assertJsonPath('key', 'pdf_renderer_is_not_configured');
    }
}
