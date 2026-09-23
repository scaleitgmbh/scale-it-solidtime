<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\InvoiceStatus;
use App\Exceptions\Api\PdfRendererIsNotConfiguredException;
use App\Http\Requests\V1\Invoice\InvoiceCopyRequest;
use App\Http\Requests\V1\Invoice\InvoiceGenerateEntriesRequest;
use App\Http\Requests\V1\Invoice\InvoiceIndexRequest;
use App\Http\Requests\V1\Invoice\InvoiceStoreRequest;
use App\Http\Requests\V1\Invoice\InvoiceUpdateRequest;
use App\Http\Resources\V1\Invoice\DetailedInvoiceResource;
use App\Http\Resources\V1\Invoice\InvoiceCollection;
use App\Http\Resources\V1\Invoice\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceEntry;
use App\Models\InvoiceRecipient;
use App\Models\InvoiceSetting;
use App\Models\Organization;
use App\Service\InvoiceGenerationService;
use App\Service\LocalizationService;
use Gotenberg\Gotenberg;
use Gotenberg\Stream;
use GuzzleHttp\Client;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\TemporaryDirectory\TemporaryDirectory;

class InvoiceController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    protected function checkPermission(Organization $organization, string $permission, ?Invoice $invoice = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($invoice !== null && $invoice->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Invoice does not belong to organization');
        }
    }

    /**
     * Get invoices
     *
     * @return InvoiceCollection<InvoiceResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getInvoices
     */
    public function index(Organization $organization, InvoiceIndexRequest $request): InvoiceCollection
    {
        $this->checkPermission($organization, 'invoices:view');

        $invoicesQuery = Invoice::query()
            ->whereBelongsTo($organization, 'organization')
            ->with(['recipient', 'entries'])
            ->orderBy('date', 'desc')
            ->orderBy('id');

        $status = $request->getStatus();
        if ($status !== null) {
            $invoicesQuery->where('status', '=', $status);
        }

        $invoices = $invoicesQuery->paginate(config('app.pagination_per_page_default'));

        return new InvoiceCollection($invoices);
    }

    /**
     * Preview invoice entries generated from tracked time entries
     *
     * Proposes invoice line items built from billable, not-yet-invoiced time entries of the recipient's
     * linked client in the given date range. This endpoint does not persist anything.
     *
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     *
     * @operationId generateInvoiceEntries
     */
    public function generateEntries(Organization $organization, InvoiceGenerateEntriesRequest $request, InvoiceGenerationService $service): array
    {
        $this->checkPermission($organization, 'invoices:create');

        /** @var InvoiceRecipient $recipient */
        $recipient = InvoiceRecipient::query()->whereBelongsTo($organization, 'organization')->findOrFail($request->input('invoice_recipient_id'));

        $entries = $service->previewEntries($organization, $recipient, $request->getStart(), $request->getEnd(), $request->getGroupByTask());

        return ['data' => $entries];
    }

    /**
     * Create invoice
     *
     * @throws AuthorizationException
     *
     * @operationId createInvoice
     */
    public function store(Organization $organization, InvoiceStoreRequest $request, InvoiceGenerationService $service): DetailedInvoiceResource
    {
        $this->checkPermission($organization, 'invoices:create');

        $invoice = DB::transaction(function () use ($organization, $request, $service): Invoice {
            $settings = InvoiceSetting::query()
                ->where('organization_id', '=', $organization->getKey())
                ->lockForUpdate()
                ->first();
            if ($settings === null) {
                $settings = InvoiceSetting::query()->create([
                    'organization_id' => $organization->getKey(),
                    'seller_name' => $organization->name,
                ]);
                // A freshly inserted row only has the explicitly-set columns in memory; reload it
                // so every column (defaults included) is populated before it's read from below.
                $settings->refresh();
            }

            $reference = $request->input('reference');
            if (! is_string($reference) || $reference === '') {
                $reference = $settings->getReferencePrefix().str_pad((string) $settings->next_invoice_number, 4, '0', STR_PAD_LEFT);
                $settings->increment('next_invoice_number');
            }

            $invoice = new Invoice;
            $invoice->organization()->associate($organization);
            $invoice->invoice_recipient_id = $request->input('invoice_recipient_id');
            $invoice->reference = $reference;
            $invoice->status = InvoiceStatus::Draft;
            $invoice->currency = $request->input('currency');
            $invoice->date = $request->input('date');
            $invoice->due_at = $request->input('due_at');
            $invoice->paid_date = $request->input('paid_date');
            $invoice->billing_period_start = $request->input('billing_period_start');
            $invoice->billing_period_end = $request->input('billing_period_end');
            $invoice->payment_iban = $request->input('payment_iban');
            $invoice->payment_terms = $request->input('payment_terms');
            $invoice->tax_rate = $request->input('tax_rate');
            $invoice->discount_amount = $request->input('discount_amount');
            $invoice->discount_type = $request->input('discount_type');
            $invoice->is_eu_reverse_charge = $request->boolean('is_eu_reverse_charge');
            $invoice->footer = $request->input('footer') ?? $settings->footer_default;
            $invoice->notes = $request->input('notes') ?? $settings->notes_default;

            foreach ([
                'seller_name' => $organization->name,
                'seller_vatin' => $settings->seller_vatin,
                'seller_address_line_1' => $settings->seller_address_line_1,
                'seller_address_line_2' => $settings->seller_address_line_2,
                'seller_address_line_3' => $settings->seller_address_line_3,
                'seller_address_post_code' => $settings->seller_address_post_code,
                'seller_address_city' => $settings->seller_address_city,
                'seller_address_country' => $settings->seller_address_country,
                'seller_phone' => $settings->seller_phone,
                'seller_email' => $settings->seller_email,
            ] as $field => $default) {
                $invoice->{$field} = $request->filled($field) ? $request->input($field) : ($settings->{$field} ?? $default);
            }

            $invoice->save();

            $entries = $request->input('entries', []);
            foreach (is_array($entries) ? $entries : [] as $index => $entryData) {
                $entry = new InvoiceEntry;
                $entry->invoice()->associate($invoice);
                $entry->name = $entryData['name'];
                $entry->description = $entryData['description'] ?? null;
                $entry->unit_price = (int) $entryData['unit_price'];
                $entry->quantity = (float) $entryData['quantity'];
                $entry->order_index = $index;
                $entry->save();

                $timeEntryIds = $entryData['time_entry_ids'] ?? [];
                if (is_array($timeEntryIds) && $timeEntryIds !== []) {
                    $service->claimTimeEntries($entry, $timeEntryIds, $organization);
                }
            }

            return $invoice;
        });

        return new DetailedInvoiceResource($invoice->load(['recipient', 'entries']));
    }

    /**
     * Copy invoice
     *
     * Creates a new draft invoice with the same fields and line items. Time entries claimed by the
     * original invoice are not carried over, so they remain available for future invoices.
     *
     * @throws AuthorizationException
     *
     * @operationId copyInvoice
     */
    public function copy(Organization $organization, Invoice $invoice, InvoiceCopyRequest $request): DetailedInvoiceResource
    {
        $this->checkPermission($organization, 'invoices:create', $invoice);

        $copy = DB::transaction(function () use ($invoice, $request): Invoice {
            $copy = $invoice->replicate();
            $copy->reference = $request->input('reference');
            $copy->status = InvoiceStatus::Draft;
            $copy->paid_date = null;
            $copy->save();

            foreach ($invoice->entries as $entry) {
                $newEntry = $entry->replicate(['invoice_id']);
                $newEntry->invoice()->associate($copy);
                $newEntry->save();
            }

            return $copy;
        });

        return new DetailedInvoiceResource($copy->load(['recipient', 'entries']));
    }

    /**
     * Get invoice
     *
     * @throws AuthorizationException
     *
     * @operationId getInvoice
     */
    public function show(Organization $organization, Invoice $invoice): DetailedInvoiceResource
    {
        $this->checkPermission($organization, 'invoices:view', $invoice);

        return new DetailedInvoiceResource($invoice->load(['recipient', 'entries']));
    }

    /**
     * Update invoice
     *
     * While an invoice is a draft, all fields including line items can be changed. Once it has been
     * marked as sent, most fields are locked and only the status, due date, paid date and free-text
     * fields (notes, footer, payment terms) can still be changed.
     *
     * @throws AuthorizationException
     *
     * @operationId updateInvoice
     */
    public function update(Organization $organization, Invoice $invoice, InvoiceUpdateRequest $request, InvoiceGenerationService $service): DetailedInvoiceResource
    {
        $this->checkPermission($organization, 'invoices:update', $invoice);

        DB::transaction(function () use ($invoice, $request, $service): void {
            $simpleFields = [
                'invoice_recipient_id', 'reference', 'currency', 'date', 'due_at', 'paid_date',
                'billing_period_start', 'billing_period_end', 'seller_name', 'seller_vatin',
                'seller_address_line_1', 'seller_address_line_2', 'seller_address_line_3',
                'seller_address_post_code', 'seller_address_city', 'seller_address_country',
                'seller_phone', 'seller_email', 'payment_iban', 'payment_terms', 'tax_rate',
                'discount_amount', 'discount_type', 'footer', 'notes',
            ];
            foreach ($simpleFields as $field) {
                if ($request->has($field)) {
                    $invoice->{$field} = $request->input($field);
                }
            }
            if ($request->has('is_eu_reverse_charge')) {
                $invoice->is_eu_reverse_charge = $request->boolean('is_eu_reverse_charge');
            }

            $status = $request->getStatus();
            if ($status !== null) {
                $this->assertValidTransition($invoice->status, $status);
                $invoice->status = $status;
                if ($status === InvoiceStatus::Paid && $invoice->paid_date === null && ! $request->has('paid_date')) {
                    $invoice->paid_date = now();
                }
            }

            $invoice->save();

            if ($request->has('entries')) {
                $this->syncEntries($invoice, (array) $request->input('entries', []), $service, $invoice->organization);
            }
        });

        return new DetailedInvoiceResource($invoice->fresh(['recipient', 'entries']));
    }

    /**
     * Delete invoice
     *
     * Only draft invoices can be deleted. Sent, paid or cancelled invoices are financial records and
     * must be kept; cancel them instead.
     *
     * @throws AuthorizationException
     *
     * @operationId deleteInvoice
     */
    public function destroy(Organization $organization, Invoice $invoice): JsonResponse
    {
        $this->checkPermission($organization, 'invoices:delete', $invoice);

        if ($invoice->status !== InvoiceStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => __('validation.invoice_field_locked'),
            ]);
        }

        $invoice->delete();

        return response()->json(null, 204);
    }

    /**
     * Download invoice as PDF
     *
     * @return array{download_link: string}
     *
     * @throws AuthorizationException
     *
     * @operationId downloadInvoice
     */
    public function download(Organization $organization, Invoice $invoice): array
    {
        $this->checkPermission($organization, 'invoices:download', $invoice);

        if (config('services.gotenberg.url') === null) {
            throw new PdfRendererIsNotConfiguredException;
        }

        $invoice->load(['recipient', 'entries']);

        $viewFile = file_get_contents(resource_path('views/invoices/pdf.blade.php'));
        if ($viewFile === false) {
            throw new \LogicException('View file not found');
        }
        $html = Blade::render($viewFile, [
            'invoice' => $invoice,
            'totals' => $invoice->totals(),
            'localization' => LocalizationService::forOrganization($organization),
        ]);

        $footerViewFile = file_get_contents(resource_path('views/invoices/pdf-footer.blade.php'));
        if ($footerViewFile === false) {
            throw new \LogicException('View file not found');
        }
        $footerHtml = Blade::render($footerViewFile);

        $client = new Client([
            'auth' => config('services.gotenberg.basic_auth_username') !== null && config('services.gotenberg.basic_auth_password') !== null ? [
                config('services.gotenberg.basic_auth_username'),
                config('services.gotenberg.basic_auth_password'),
            ] : null,
        ]);
        $gotenbergRequest = Gotenberg::chromium(config('services.gotenberg.url'))
            ->pdf()
            ->assets(
                Stream::path(resource_path('pdf/Outfit-VariableFont_wght.ttf'), 'outfit.ttf'),
            )
            ->margins(0.39, 0.78, 0.39, 0.39)
            ->paperSize('8.27', '11.7')
            ->footer(Stream::string('footer', $footerHtml))
            ->html(Stream::string('body', $html));

        $tempFolder = TemporaryDirectory::make();
        $filename = 'invoice-'.Str::slug($invoice->reference).'-'.Str::uuid().'.pdf';
        $folderPath = 'invoices/'.$organization->getKey();
        $filenameTemp = Gotenberg::save($gotenbergRequest, $tempFolder->path(), $client);
        Storage::disk(config('filesystems.private'))
            ->putFileAs($folderPath, new File($tempFolder->path($filenameTemp)), $filename);

        return [
            'download_link' => Storage::disk(config('filesystems.private'))
                ->temporaryUrl($folderPath.'/'.$filename, now()->addMinutes(5), [
                    'ResponseContentDisposition' => 'attachment; filename="'.$filename.'"',
                ]),
        ];
    }

    private function assertValidTransition(InvoiceStatus $from, InvoiceStatus $to): void
    {
        $allowed = match ($from) {
            InvoiceStatus::Draft => [InvoiceStatus::Draft, InvoiceStatus::Sent, InvoiceStatus::Cancelled],
            InvoiceStatus::Sent => [InvoiceStatus::Sent, InvoiceStatus::Paid, InvoiceStatus::Cancelled],
            InvoiceStatus::Paid, InvoiceStatus::Cancelled => [$from],
        };
        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => __('validation.invoice_field_locked'),
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    private function syncEntries(Invoice $invoice, array $entries, InvoiceGenerationService $service, Organization $organization): void
    {
        $keptIds = [];
        foreach ($entries as $index => $entryData) {
            $id = $entryData['id'] ?? null;
            /** @var InvoiceEntry|null $entry */
            $entry = $id !== null ? $invoice->entries->firstWhere('id', $id) : null;
            if ($entry === null) {
                $entry = new InvoiceEntry;
                $entry->invoice()->associate($invoice);
            }
            $entry->name = $entryData['name'];
            $entry->description = $entryData['description'] ?? null;
            $entry->unit_price = (int) $entryData['unit_price'];
            $entry->quantity = (float) $entryData['quantity'];
            $entry->order_index = $index;
            $entry->save();
            $keptIds[] = $entry->getKey();

            $timeEntryIds = $entryData['time_entry_ids'] ?? [];
            if (is_array($timeEntryIds) && $timeEntryIds !== []) {
                $service->claimTimeEntries($entry, $timeEntryIds, $organization);
            }
        }

        // Entries that are no longer present are removed; their claimed time entries are freed
        // automatically via the invoice_entries -> time_entries nullOnDelete foreign key.
        $invoice->entries()->whereNotIn('id', $keptIds)->delete();
    }
}
