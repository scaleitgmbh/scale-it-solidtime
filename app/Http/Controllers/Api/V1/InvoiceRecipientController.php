<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\EntityStillInUseApiException;
use App\Http\Requests\V1\InvoiceRecipient\InvoiceRecipientDuplicateRequest;
use App\Http\Requests\V1\InvoiceRecipient\InvoiceRecipientIndexRequest;
use App\Http\Requests\V1\InvoiceRecipient\InvoiceRecipientStoreRequest;
use App\Http\Requests\V1\InvoiceRecipient\InvoiceRecipientUpdateRequest;
use App\Http\Resources\V1\InvoiceRecipient\InvoiceRecipientCollection;
use App\Http\Resources\V1\InvoiceRecipient\InvoiceRecipientResource;
use App\Models\InvoiceRecipient;
use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class InvoiceRecipientController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    protected function checkPermission(Organization $organization, string $permission, ?InvoiceRecipient $recipient = null): void
    {
        parent::checkPermission($organization, $permission);
        if ($recipient !== null && $recipient->organization_id !== $organization->getKey()) {
            throw new AuthorizationException('Invoice recipient does not belong to organization');
        }
    }

    /**
     * Get invoice recipients
     *
     * @return InvoiceRecipientCollection<InvoiceRecipientResource>
     *
     * @throws AuthorizationException
     *
     * @operationId getInvoiceRecipients
     */
    public function index(Organization $organization, InvoiceRecipientIndexRequest $request): InvoiceRecipientCollection
    {
        $this->checkPermission($organization, 'invoice-recipients:view');

        $recipientsQuery = InvoiceRecipient::query()
            ->whereBelongsTo($organization, 'organization')
            ->withCount('invoices')
            ->orderBy('created_at', 'desc')
            ->orderBy('id');

        $filterArchived = $request->getFilterArchived();
        if ($filterArchived === 'true') {
            $recipientsQuery->whereNotNull('archived_at');
        } elseif ($filterArchived === 'false') {
            $recipientsQuery->whereNull('archived_at');
        }

        $recipients = $recipientsQuery->paginate(config('app.pagination_per_page_default'));

        return new InvoiceRecipientCollection($recipients);
    }

    /**
     * Create invoice recipient
     *
     * @throws AuthorizationException
     *
     * @operationId createInvoiceRecipient
     */
    public function store(Organization $organization, InvoiceRecipientStoreRequest $request): InvoiceRecipientResource
    {
        $this->checkPermission($organization, 'invoice-recipients:create');

        $recipient = new InvoiceRecipient;
        $this->fill($recipient, $request->validated());
        $recipient->organization()->associate($organization);
        $recipient->save();

        return new InvoiceRecipientResource($recipient);
    }

    /**
     * Get invoice recipient
     *
     * @throws AuthorizationException
     *
     * @operationId getInvoiceRecipient
     */
    public function show(Organization $organization, InvoiceRecipient $invoiceRecipient): InvoiceRecipientResource
    {
        $this->checkPermission($organization, 'invoice-recipients:view', $invoiceRecipient);

        return new InvoiceRecipientResource($invoiceRecipient);
    }

    /**
     * Update invoice recipient
     *
     * @throws AuthorizationException
     *
     * @operationId updateInvoiceRecipient
     */
    public function update(Organization $organization, InvoiceRecipient $invoiceRecipient, InvoiceRecipientUpdateRequest $request): InvoiceRecipientResource
    {
        $this->checkPermission($organization, 'invoice-recipients:update', $invoiceRecipient);

        $this->fill($invoiceRecipient, $request->validated());
        if ($request->has('is_archived')) {
            $invoiceRecipient->archived_at = $request->getIsArchived() ? Carbon::now() : null;
        }
        $invoiceRecipient->save();

        return new InvoiceRecipientResource($invoiceRecipient);
    }

    /**
     * Duplicate invoice recipient
     *
     * @throws AuthorizationException
     *
     * @operationId duplicateInvoiceRecipient
     */
    public function duplicate(Organization $organization, InvoiceRecipient $invoiceRecipient, InvoiceRecipientDuplicateRequest $request): InvoiceRecipientResource
    {
        $this->checkPermission($organization, 'invoice-recipients:create', $invoiceRecipient);

        $duplicate = $invoiceRecipient->replicate(['archived_at']);
        $duplicate->name = $request->input('name') ?? $invoiceRecipient->name.' (Copy)';
        $duplicate->save();

        return new InvoiceRecipientResource($duplicate);
    }

    /**
     * Delete invoice recipient
     *
     * @throws AuthorizationException|EntityStillInUseApiException
     *
     * @operationId deleteInvoiceRecipient
     */
    public function destroy(Organization $organization, InvoiceRecipient $invoiceRecipient): JsonResponse
    {
        $this->checkPermission($organization, 'invoice-recipients:delete', $invoiceRecipient);

        if ($invoiceRecipient->invoices()->exists()) {
            throw new EntityStillInUseApiException('invoice_recipient', 'invoice');
        }

        $invoiceRecipient->delete();

        return response()->json(null, 204);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(InvoiceRecipient $recipient, array $data): void
    {
        foreach ([
            'name',
            'client_id',
            'vatin',
            'address_line_1',
            'address_line_2',
            'address_line_3',
            'address_post_code',
            'address_city',
            'address_country',
            'phone',
            'email',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $recipient->{$field} = $data[$field];
            }
        }
    }
}
