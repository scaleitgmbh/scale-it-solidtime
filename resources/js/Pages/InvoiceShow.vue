<script setup lang="ts">
import { computed, ref } from 'vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { DocumentTextIcon } from '@heroicons/vue/20/solid';
import PageTitle from '@/Components/Common/PageTitle.vue';
import InvoiceEditor from '@/Components/Common/Invoice/InvoiceEditor.vue';
import InvoiceStatusBadge from '@/Components/Common/Invoice/InvoiceStatusBadge.vue';
import InvoiceCopyModal from '@/Components/Common/Invoice/InvoiceCopyModal.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { useInvoiceQuery } from '@/utils/useInvoiceQuery';
import { useInvoicesStore } from '@/utils/useInvoices';
import { formatCents } from '@/packages/ui/src/utils/money';
import { canDeleteInvoices, canDownloadInvoices, canUpdateInvoices } from '@/utils/permissions';
import { router } from '@inertiajs/vue3';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';

const invoiceId = route()?.params?.invoice as string;
const { invoice, isLoading } = useInvoiceQuery(invoiceId);
const { updateInvoice, deleteInvoice, downloadInvoice } = useInvoicesStore();
const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);

const showCopyModal = ref(false);
const updating = ref(false);

async function setStatus(status: 'paid' | 'cancelled') {
    updating.value = true;
    await updateInvoice(invoiceId, { status });
    updating.value = false;
}

function remove() {
    deleteInvoice(invoiceId).then(() => router.visit(route('invoices')));
}

const currency = computed(() => invoice.value?.currency ?? 'EUR');

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
</script>

<template>
    <AppLayout title="Invoice" data-testid="invoice_show_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <PageTitle :icon="DocumentTextIcon" :title="invoice?.reference ?? 'Invoice'">
                </PageTitle>
                <InvoiceStatusBadge v-if="invoice" :status="invoice.status"></InvoiceStatusBadge>
            </div>
            <div v-if="invoice && invoice.status !== 'draft'" class="flex items-center space-x-3">
                <SecondaryButton v-if="canDownloadInvoices()" @click="downloadInvoice(invoiceId)">
                    Download PDF
                </SecondaryButton>
                <SecondaryButton @click="showCopyModal = true">Copy as draft</SecondaryButton>
                <SecondaryButton
                    v-if="canUpdateInvoices() && invoice.status === 'sent'"
                    :disabled="updating"
                    @click="setStatus('cancelled')">
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    v-if="canUpdateInvoices() && invoice.status === 'sent'"
                    :disabled="updating"
                    @click="setStatus('paid')">
                    Mark as Paid
                </PrimaryButton>
            </div>
            <InvoiceCopyModal
                v-if="invoice"
                v-model:show="showCopyModal"
                :invoice-id="invoice.id"
                :suggested-reference="invoice.reference + '-COPY'"></InvoiceCopyModal>
        </MainContainer>

        <MainContainer v-if="isLoading" class="py-10 text-center text-text-secondary">
            Loading invoice...
        </MainContainer>

        <MainContainer v-else-if="invoice && invoice.status === 'draft'" class="py-6">
            <InvoiceEditor :invoice="invoice"></InvoiceEditor>
        </MainContainer>

        <MainContainer v-else-if="invoice" class="py-6 max-w-4xl space-y-6">
            <div class="bg-card-background border border-card-border rounded-lg p-5">
                <div class="flex justify-between">
                    <div>
                        <p class="font-semibold text-text-primary">{{ invoice.seller_name }}</p>
                        <p class="text-sm text-text-secondary">{{ invoice.seller_email }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-text-primary">Bill to</p>
                        <p class="text-sm text-text-secondary">{{ invoice.recipient.name }}</p>
                    </div>
                </div>
            </div>

            <div class="border border-card-border rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-card-background">
                        <tr>
                            <th class="text-left font-medium py-2 px-3 text-text-secondary">
                                Item
                            </th>
                            <th class="text-right font-medium py-2 px-3 text-text-secondary">
                                Quantity
                            </th>
                            <th class="text-right font-medium py-2 px-3 text-text-secondary">
                                Unit Price
                            </th>
                            <th class="text-right font-medium py-2 px-3 text-text-secondary">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="entry in invoice.entries"
                            :key="entry.id"
                            class="border-t border-card-border">
                            <td class="py-2 px-3">
                                {{ entry.name }}
                                <span v-if="entry.description" class="block text-text-secondary">{{
                                    entry.description
                                }}</span>
                            </td>
                            <td class="py-2 px-3 text-right">{{ entry.quantity }}</td>
                            <td class="py-2 px-3 text-right">
                                {{ money(entry.unit_price) }}
                            </td>
                            <td class="py-2 px-3 text-right">
                                {{ money(entry.line_total) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <div class="max-w-xs w-full space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-text-secondary">Subtotal</span>
                        <span>{{ money(invoice.totals.subtotal) }}</span>
                    </div>
                    <div v-if="invoice.totals.discount_total > 0" class="flex justify-between">
                        <span class="text-text-secondary">Discount</span>
                        <span>-{{ money(invoice.totals.discount_total) }}</span>
                    </div>
                    <div v-if="invoice.totals.tax_total > 0" class="flex justify-between">
                        <span class="text-text-secondary">Tax</span>
                        <span>{{ money(invoice.totals.tax_total) }}</span>
                    </div>
                    <div
                        class="flex justify-between font-semibold border-t border-card-border pt-1">
                        <span>Total</span>
                        <span>{{ money(invoice.totals.total) }}</span>
                    </div>
                </div>
            </div>

            <p v-if="invoice.notes" class="text-sm text-text-secondary">{{ invoice.notes }}</p>
            <p v-if="invoice.footer" class="text-sm text-text-secondary">{{ invoice.footer }}</p>

            <div v-if="canDeleteInvoices() && invoice.status === 'draft'" class="flex justify-end">
                <SecondaryButton class="text-destructive" @click="remove"> Delete </SecondaryButton>
            </div>
        </MainContainer>
    </AppLayout>
</template>
