<script setup lang="ts">
import FormSection from '@/Components/FormSection.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import TextareaInput from '@/packages/ui/src/Input/TextareaInput.vue';
import { computed, ref, watch } from 'vue';
import { Field, FieldGroup, FieldLabel } from '@/packages/ui/src/field';
import { useInvoiceSettingsQuery } from '@/utils/useInvoiceSettingsQuery';
import { useInvoiceSettingsStore } from '@/utils/useInvoiceSettings';
import type { UpdateInvoiceSettingsBody } from '@/packages/api/src';

const { invoiceSettings } = useInvoiceSettingsQuery();
const { updateInvoiceSettings } = useInvoiceSettingsStore();

const form = ref<UpdateInvoiceSettingsBody>({});
const saving = ref(false);

watch(
    invoiceSettings,
    (settings) => {
        if (settings) {
            form.value = {
                seller_name: settings.seller_name,
                seller_vatin: settings.seller_vatin,
                seller_address_line_1: settings.seller_address_line_1,
                seller_address_line_2: settings.seller_address_line_2,
                seller_address_post_code: settings.seller_address_post_code,
                seller_address_city: settings.seller_address_city,
                seller_address_country: settings.seller_address_country,
                seller_phone: settings.seller_phone,
                seller_email: settings.seller_email,
                footer_default: settings.footer_default,
                notes_default: settings.notes_default,
                invoice_number_prefix: settings.invoice_number_prefix,
            };
        }
    },
    { immediate: true }
);

const notesDefault = computed({
    get: () => form.value.notes_default ?? '',
    set: (value: string) => (form.value.notes_default = value || null),
});
const footerDefault = computed({
    get: () => form.value.footer_default ?? '',
    set: (value: string) => (form.value.footer_default = value || null),
});

async function submit() {
    saving.value = true;
    await updateInvoiceSettings(form.value);
    saving.value = false;
}
</script>

<template>
    <FormSection>
        <template #title>Invoicing</template>
        <template #description>
            The seller details and defaults used on new invoices. Existing invoices keep the values
            they were created with.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4 space-y-4">
                <FieldGroup class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Field>
                        <FieldLabel for="sellerName">Seller Name</FieldLabel>
                        <TextInput
                            id="sellerName"
                            v-model="form.seller_name"
                            type="text"
                            class="w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="sellerVatin">VAT ID</FieldLabel>
                        <TextInput
                            id="sellerVatin"
                            v-model="form.seller_vatin"
                            type="text"
                            class="w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="sellerAddress1">Address Line 1</FieldLabel>
                        <TextInput
                            id="sellerAddress1"
                            v-model="form.seller_address_line_1"
                            type="text"
                            class="w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="sellerAddress2">Address Line 2</FieldLabel>
                        <TextInput
                            id="sellerAddress2"
                            v-model="form.seller_address_line_2"
                            type="text"
                            class="w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="sellerPostCode">Post Code</FieldLabel>
                        <TextInput
                            id="sellerPostCode"
                            v-model="form.seller_address_post_code"
                            type="text"
                            class="w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="sellerCity">City</FieldLabel>
                        <TextInput
                            id="sellerCity"
                            v-model="form.seller_address_city"
                            type="text"
                            class="w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="sellerCountry">Country</FieldLabel>
                        <TextInput
                            id="sellerCountry"
                            v-model="form.seller_address_country"
                            type="text"
                            class="w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="sellerPhone">Phone</FieldLabel>
                        <TextInput
                            id="sellerPhone"
                            v-model="form.seller_phone"
                            type="text"
                            class="w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="sellerEmail">Email</FieldLabel>
                        <TextInput
                            id="sellerEmail"
                            v-model="form.seller_email"
                            type="email"
                            class="w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="invoiceNumberPrefix">Invoice Number Prefix</FieldLabel>
                        <TextInput
                            id="invoiceNumberPrefix"
                            v-model="form.invoice_number_prefix"
                            type="text"
                            placeholder="INV-"
                            class="w-full" />
                    </Field>
                </FieldGroup>
                <Field>
                    <FieldLabel for="notesDefault">Default Notes</FieldLabel>
                    <TextareaInput
                        id="notesDefault"
                        v-model="notesDefault"
                        :rows="2"
                        class="w-full" />
                </Field>
                <Field>
                    <FieldLabel for="footerDefault">Default Footer</FieldLabel>
                    <TextareaInput
                        id="footerDefault"
                        v-model="footerDefault"
                        :rows="2"
                        class="w-full" />
                </Field>
            </div>
        </template>

        <template #actions>
            <PrimaryButton :disabled="saving" @click="submit">Save</PrimaryButton>
        </template>
    </FormSection>
</template>
