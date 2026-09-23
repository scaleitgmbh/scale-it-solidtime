<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\InvoiceSetting\InvoiceSettingUpdateRequest;
use App\Http\Resources\V1\InvoiceSetting\InvoiceSettingResource;
use App\Models\InvoiceSetting;
use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;

class InvoiceSettingController extends Controller
{
    /**
     * Get invoice settings
     *
     * @throws AuthorizationException
     *
     * @operationId getInvoiceSettings
     */
    public function show(Organization $organization): InvoiceSettingResource
    {
        $this->checkPermission($organization, 'invoice-settings:view');

        $settings = $this->settingsForOrganization($organization);

        return new InvoiceSettingResource($settings);
    }

    /**
     * Update invoice settings
     *
     * @throws AuthorizationException
     *
     * @operationId updateInvoiceSettings
     */
    public function update(Organization $organization, InvoiceSettingUpdateRequest $request): InvoiceSettingResource
    {
        $this->checkPermission($organization, 'invoice-settings:update');

        $settings = $this->settingsForOrganization($organization);

        foreach ([
            'seller_name',
            'seller_vatin',
            'seller_address_line_1',
            'seller_address_line_2',
            'seller_address_line_3',
            'seller_address_post_code',
            'seller_address_city',
            'seller_address_country',
            'seller_phone',
            'seller_email',
            'footer_default',
            'notes_default',
            'tax_rate_default',
            'invoice_number_prefix',
        ] as $field) {
            if ($request->has($field)) {
                $settings->{$field} = $request->input($field);
            }
        }
        $settings->save();

        return new InvoiceSettingResource($settings);
    }

    private function settingsForOrganization(Organization $organization): InvoiceSetting
    {
        $settings = InvoiceSetting::query()->firstOrCreate(
            ['organization_id' => $organization->getKey()],
            ['seller_name' => $organization->name]
        );
        if ($settings->wasRecentlyCreated) {
            // A freshly inserted row only has the explicitly-set columns in memory; reload it so
            // every column (defaults included) is populated before it's serialized or read from.
            $settings->refresh();
        }
        // Lazily creating the settings row on first access is an implementation detail and should
        // never surface as an HTTP 201 on what is conceptually a GET/PUT on a singleton resource.
        $settings->wasRecentlyCreated = false;

        return $settings;
    }
}
