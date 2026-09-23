<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import TextareaInput from '@/packages/ui/src/Input/TextareaInput.vue';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import Checkbox from '@/packages/ui/src/Input/Checkbox.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { Field, FieldGroup, FieldLabel } from '@/packages/ui/src/field';
import InvoiceEntriesTable, {
    type EditableInvoiceEntry,
} from '@/Components/Common/Invoice/InvoiceEntriesTable.vue';
import { useInvoiceRecipientsQuery } from '@/utils/useInvoiceRecipientsQuery';
import { useInvoiceSettingsQuery } from '@/utils/useInvoiceSettingsQuery';
import { useInvoicesStore } from '@/utils/useInvoices';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { formatCents } from '@/packages/ui/src/utils/money';
import type { DetailedInvoice, CreateInvoiceBody, UpdateInvoiceBody } from '@/packages/api/src';

const props = defineProps<{
    invoice?: DetailedInvoice | null;
}>();

const { invoiceRecipients } = useInvoiceRecipientsQuery();
const { invoiceSettings } = useInvoiceSettingsQuery();
const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);
const { createInvoice, updateInvoice, generateInvoiceEntries } = useInvoicesStore();

const isEditing = computed(() => !!props.invoice);
const currency = computed(() => organization.value?.currency ?? 'EUR');

function money(amountCents: number): string {
    return (
        formatCents(
            amountCents,
            currency.value,
            organization.value?.currency_format,
            organization.value?.currency_symbol,
            organization.value?.number_format
        ) ?? ''
    );
}

const invoiceRecipientId = ref(props.invoice?.invoice_recipient_id ?? '');
const reference = ref(props.invoice?.reference ?? '');
const date = ref(props.invoice?.date ?? new Date().toISOString().slice(0, 10));
const dueAt = ref<string | null>(props.invoice?.due_at ?? null);
const billingPeriodStart = ref<string | null>(props.invoice?.billing_period_start ?? null);
const billingPeriodEnd = ref<string | null>(props.invoice?.billing_period_end ?? null);
const paymentIban = ref<string | null>(props.invoice?.payment_iban ?? null);
const paymentTerms = ref<string | null>(props.invoice?.payment_terms ?? null);
const footer = ref<string>(props.invoice?.footer ?? '');
const notes = ref<string>(props.invoice?.notes ?? '');
const isEuReverseCharge = ref(props.invoice?.is_eu_reverse_charge ?? false);

const taxRatePercent = ref(props.invoice?.tax_rate ? props.invoice.tax_rate / 100 : null);
const discountType = ref(props.invoice?.discount_type ?? null);
const discountPercent = ref(
    props.invoice?.discount_type === 'percentage' && props.invoice.discount_amount
        ? props.invoice.discount_amount / 100
        : null
);
const discountFixedCents = ref(
    props.invoice?.discount_type === 'fixed' ? (props.invoice.discount_amount ?? 0) : 0
);

const entries = ref<EditableInvoiceEntry[]>(
    props.invoice?.entries.map((entry) => ({
        id: entry.id,
        name: entry.name,
        description: entry.description,
        unit_price: entry.unit_price,
        quantity: entry.quantity,
    })) ?? []
);

const recipientName = computed(
    () =>
        invoiceRecipients.value.find((recipient) => recipient.id === invoiceRecipientId.value)?.name
);

// "Generate from time entries" panel
const generateStart = ref<string | null>(null);
const generateEnd = ref<string | null>(null);
const generateGroupBy = ref<'project' | 'project_task'>('project');
const generating = ref(false);

const canGenerate = computed(
    () => !!invoiceRecipientId.value && !!generateStart.value && !!generateEnd.value
);

async function generateFromTimeEntries() {
    if (!canGenerate.value) return;
    generating.value = true;
    const generated = await generateInvoiceEntries({
        invoice_recipient_id: invoiceRecipientId.value,
        start: generateStart.value!,
        end: generateEnd.value!,
        group_by: generateGroupBy.value,
    });
    entries.value = [
        ...entries.value,
        ...generated.map((entry) => ({
            name: entry.name,
            description: entry.description,
            unit_price: entry.unit_price,
            quantity: entry.quantity,
            time_entry_ids: entry.time_entry_ids,
        })),
    ];
    generating.value = false;
}

// Live client-side totals preview (server recomputes authoritatively on save).
const totals = computed(() => {
    const subtotal = entries.value.reduce(
        (sum, entry) => sum + Math.round(entry.unit_price * entry.quantity),
        0
    );
    let discountTotal = 0;
    if (discountType.value === 'fixed') {
        discountTotal = Math.min(discountFixedCents.value || 0, subtotal);
    } else if (discountType.value === 'percentage' && discountPercent.value) {
        discountTotal = Math.min(Math.round((subtotal * discountPercent.value) / 100), subtotal);
    }
    const taxableBase = subtotal - discountTotal;
    const taxTotal = taxRatePercent.value
        ? Math.round((taxableBase * taxRatePercent.value) / 100)
        : 0;
    return {
        subtotal,
        discountTotal,
        taxTotal,
        total: taxableBase + taxTotal,
    };
});

const saving = ref(false);

function buildPayload(): CreateInvoiceBody | UpdateInvoiceBody {
    const discountAmount =
        discountType.value === 'fixed'
            ? discountFixedCents.value
            : discountType.value === 'percentage' && discountPercent.value
              ? Math.round(discountPercent.value * 100)
              : null;

    return {
        invoice_recipient_id: invoiceRecipientId.value,
        reference: reference.value || null,
        currency: currency.value,
        date: date.value,
        due_at: dueAt.value,
        billing_period_start: billingPeriodStart.value,
        billing_period_end: billingPeriodEnd.value,
        seller_name: invoiceSettings.value?.seller_name || organization.value?.name || '',
        seller_vatin: invoiceSettings.value?.seller_vatin ?? null,
        seller_address_line_1: invoiceSettings.value?.seller_address_line_1 ?? null,
        seller_address_line_2: invoiceSettings.value?.seller_address_line_2 ?? null,
        seller_address_line_3: invoiceSettings.value?.seller_address_line_3 ?? null,
        seller_address_post_code: invoiceSettings.value?.seller_address_post_code ?? null,
        seller_address_city: invoiceSettings.value?.seller_address_city ?? null,
        seller_address_country: invoiceSettings.value?.seller_address_country ?? null,
        seller_phone: invoiceSettings.value?.seller_phone ?? null,
        seller_email: invoiceSettings.value?.seller_email ?? null,
        payment_iban: paymentIban.value,
        payment_terms: paymentTerms.value,
        tax_rate: taxRatePercent.value ? Math.round(taxRatePercent.value * 100) : null,
        discount_type: discountType.value,
        discount_amount: discountAmount,
        is_eu_reverse_charge: isEuReverseCharge.value,
        footer: footer.value || null,
        notes: notes.value || null,
        entries: entries.value.map((entry) => ({
            id: entry.id ?? null,
            name: entry.name,
            description: entry.description,
            unit_price: entry.unit_price,
            quantity: entry.quantity,
            time_entry_ids: entry.time_entry_ids,
        })),
    };
}

async function save(markAsSent = false) {
    if (!invoiceRecipientId.value) {
        return;
    }
    saving.value = true;
    const payload = buildPayload();
    let savedId: string | undefined;
    if (isEditing.value && props.invoice) {
        const updated = await updateInvoice(props.invoice.id, payload as UpdateInvoiceBody);
        savedId = updated?.id;
    } else {
        const created = await createInvoice(payload as CreateInvoiceBody);
        savedId = created?.id;
    }
    if (savedId && markAsSent) {
        await updateInvoice(savedId, { status: 'sent' });
    }
    saving.value = false;
    if (savedId) {
        router.visit(route('invoices.show', savedId));
    }
}
</script>

<template>
    <div class="space-y-6 max-w-4xl">
        <div class="bg-card-background border border-card-border rounded-lg p-5 space-y-4">
            <FieldGroup class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Field>
                    <FieldLabel for="invoiceRecipient">Recipient</FieldLabel>
                    <select
                        id="invoiceRecipient"
                        v-model="invoiceRecipientId"
                        class="border-input-border border bg-input-background text-text-primary rounded-md shadow-sm h-9 px-3 text-sm w-full"
                        required>
                        <option value="" disabled>Select a recipient</option>
                        <option
                            v-for="recipient in invoiceRecipients"
                            :key="recipient.id"
                            :value="recipient.id">
                            {{ recipient.name }}
                        </option>
                    </select>
                </Field>
                <Field>
                    <FieldLabel for="invoiceReference">Reference</FieldLabel>
                    <TextInput
                        id="invoiceReference"
                        v-model="reference"
                        type="text"
                        :placeholder="
                            'Auto (' + (invoiceSettings?.invoice_number_prefix ?? 'INV-') + '...)'
                        "
                        class="w-full" />
                </Field>
                <Field>
                    <FieldLabel for="invoiceDate">Date</FieldLabel>
                    <TextInput
                        id="invoiceDate"
                        v-model="date"
                        type="date"
                        class="w-full"
                        required />
                </Field>
                <Field>
                    <FieldLabel for="invoiceDueAt">Due Date</FieldLabel>
                    <TextInput id="invoiceDueAt" v-model="dueAt" type="date" class="w-full" />
                </Field>
                <Field>
                    <FieldLabel for="billingPeriodStart">Billing Period Start</FieldLabel>
                    <TextInput
                        id="billingPeriodStart"
                        v-model="billingPeriodStart"
                        type="date"
                        class="w-full" />
                </Field>
                <Field>
                    <FieldLabel for="billingPeriodEnd">Billing Period End</FieldLabel>
                    <TextInput
                        id="billingPeriodEnd"
                        v-model="billingPeriodEnd"
                        type="date"
                        class="w-full" />
                </Field>
            </FieldGroup>
        </div>

        <div class="bg-card-background border border-card-border rounded-lg p-5 space-y-4">
            <h3 class="font-semibold text-text-primary">Generate from tracked time</h3>
            <p class="text-sm text-text-secondary">
                Pick a date range to propose line items from billable time entries tracked against
                the recipient's linked client. You can still edit or remove rows afterwards.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <Field>
                    <FieldLabel for="generateStart">From</FieldLabel>
                    <TextInput
                        id="generateStart"
                        v-model="generateStart"
                        type="date"
                        class="w-full" />
                </Field>
                <Field>
                    <FieldLabel for="generateEnd">To</FieldLabel>
                    <TextInput id="generateEnd" v-model="generateEnd" type="date" class="w-full" />
                </Field>
                <Field>
                    <FieldLabel for="generateGroupBy">Group by</FieldLabel>
                    <select
                        id="generateGroupBy"
                        v-model="generateGroupBy"
                        class="border-input-border border bg-input-background text-text-primary rounded-md shadow-sm h-9 px-3 text-sm w-full">
                        <option value="project">Project</option>
                        <option value="project_task">Project + Task</option>
                    </select>
                </Field>
                <SecondaryButton :loading="generating" @click="generateFromTimeEntries">
                    Generate
                </SecondaryButton>
            </div>
            <p v-if="!invoiceRecipientId" class="text-xs text-text-secondary">
                Select a recipient above first.
            </p>
        </div>

        <div class="space-y-2">
            <h3 class="font-semibold text-text-primary">Line Items</h3>
            <InvoiceEntriesTable v-model="entries" :currency="currency"></InvoiceEntriesTable>
        </div>

        <div class="bg-card-background border border-card-border rounded-lg p-5 space-y-4">
            <FieldGroup class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Field>
                    <FieldLabel for="discountType">Discount</FieldLabel>
                    <select
                        id="discountType"
                        v-model="discountType"
                        class="border-input-border border bg-input-background text-text-primary rounded-md shadow-sm h-9 px-3 text-sm w-full">
                        <option :value="null">None</option>
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </Field>
                <Field v-if="discountType === 'percentage'">
                    <FieldLabel for="discountPercent">Discount %</FieldLabel>
                    <TextInput
                        id="discountPercent"
                        v-model.number="discountPercent"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        class="w-full" />
                </Field>
                <Field v-if="discountType === 'fixed'">
                    <FieldLabel for="discountFixed">Discount Amount</FieldLabel>
                    <BillableRateInput
                        v-model="discountFixedCents"
                        name="discountFixed"
                        :currency="currency"
                        class="w-full" />
                </Field>
                <Field>
                    <FieldLabel for="taxRate">Tax Rate %</FieldLabel>
                    <TextInput
                        id="taxRate"
                        v-model.number="taxRatePercent"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-full" />
                </Field>
            </FieldGroup>
            <Field orientation="horizontal">
                <Checkbox id="euReverseCharge" v-model:checked="isEuReverseCharge"></Checkbox>
                <FieldLabel for="euReverseCharge"
                    >EU reverse charge (VAT to be accounted for by the recipient)</FieldLabel
                >
            </Field>
        </div>

        <div class="bg-card-background border border-card-border rounded-lg p-5 space-y-4">
            <FieldGroup class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <Field>
                    <FieldLabel for="paymentIban">Payment IBAN</FieldLabel>
                    <TextInput id="paymentIban" v-model="paymentIban" type="text" class="w-full" />
                </Field>
                <Field>
                    <FieldLabel for="paymentTerms">Payment Terms</FieldLabel>
                    <TextInput
                        id="paymentTerms"
                        v-model="paymentTerms"
                        type="text"
                        class="w-full" />
                </Field>
            </FieldGroup>
            <Field>
                <FieldLabel for="notes">Notes</FieldLabel>
                <TextareaInput id="notes" v-model="notes" :rows="2" class="w-full" />
            </Field>
            <Field>
                <FieldLabel for="footer">Footer</FieldLabel>
                <TextareaInput id="footer" v-model="footer" :rows="2" class="w-full" />
            </Field>
        </div>

        <div class="bg-card-background border border-card-border rounded-lg p-5">
            <div class="ml-auto max-w-xs space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-text-secondary">Subtotal</span>
                    <span>{{ money(totals.subtotal) }}</span>
                </div>
                <div v-if="totals.discountTotal > 0" class="flex justify-between">
                    <span class="text-text-secondary">Discount</span>
                    <span>-{{ money(totals.discountTotal) }}</span>
                </div>
                <div v-if="totals.taxTotal > 0" class="flex justify-between">
                    <span class="text-text-secondary">Tax</span>
                    <span>{{ money(totals.taxTotal) }}</span>
                </div>
                <div class="flex justify-between font-semibold border-t border-card-border pt-1">
                    <span>Total</span>
                    <span>{{ money(totals.total) }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <SecondaryButton :loading="saving" @click="save(false)">
                Save as Draft
            </SecondaryButton>
            <PrimaryButton :loading="saving" @click="save(true)">
                Save &amp; Mark as Sent
            </PrimaryButton>
        </div>
        <p v-if="recipientName" class="sr-only">Billing {{ recipientName }}</p>
    </div>
</template>
