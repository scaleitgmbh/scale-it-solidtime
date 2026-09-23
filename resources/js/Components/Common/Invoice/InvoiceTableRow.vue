<script setup lang="ts">
import type { InvoiceIndexEntry } from '@/packages/api/src';
import { computed, ref } from 'vue';
import { PencilSquareIcon, EyeIcon, ArrowDownTrayIcon, TrashIcon } from '@heroicons/vue/20/solid';
import { useInvoicesStore } from '@/utils/useInvoices';
import InvoiceMoreOptionsDropdown from '@/Components/Common/Invoice/InvoiceMoreOptionsDropdown.vue';
import InvoiceStatusBadge from '@/Components/Common/Invoice/InvoiceStatusBadge.vue';
import InvoiceCopyModal from '@/Components/Common/Invoice/InvoiceCopyModal.vue';
import TableRow from '@/Components/TableRow.vue';
import { canDeleteInvoices, canDownloadInvoices } from '@/utils/permissions';
import { formatCents } from '@/packages/ui/src/utils/money';
import { getOrganizationCurrencyString } from '@/utils/money';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { router } from '@inertiajs/vue3';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/packages/ui/src';

const { deleteInvoice, downloadInvoice } = useInvoicesStore();
const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);

const props = defineProps<{
    invoice: InvoiceIndexEntry;
}>();

const showCopyModal = ref(false);

function view() {
    router.visit(route('invoices.show', props.invoice.id));
}

function remove() {
    deleteInvoice(props.invoice.id);
}

const formattedTotal = computed(() =>
    formatCents(
        props.invoice.total,
        getOrganizationCurrencyString(),
        organization.value?.currency_format,
        organization.value?.currency_symbol,
        organization.value?.number_format
    )
);
</script>

<template>
    <ContextMenu>
        <ContextMenuTrigger as-child>
            <TableRow class="cursor-pointer" @click="view">
                <InvoiceCopyModal
                    v-model:show="showCopyModal"
                    :invoice-id="invoice.id"
                    :suggested-reference="invoice.reference + '-COPY'"></InvoiceCopyModal>
                <div
                    class="whitespace-nowrap flex items-center space-x-5 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
                    <span>{{ invoice.reference }}</span>
                </div>
                <div
                    class="whitespace-nowrap flex items-center px-3 py-4 text-sm text-text-primary">
                    <span>{{ invoice.recipient }}</span>
                </div>
                <div
                    class="whitespace-nowrap flex items-center px-3 py-4 text-sm text-text-primary">
                    <span>{{ invoice.date }}</span>
                </div>
                <div
                    class="whitespace-nowrap flex items-center px-3 py-4 text-sm text-text-primary">
                    <span>{{ formattedTotal }}</span>
                </div>
                <div class="whitespace-nowrap flex items-center px-3 py-4 text-sm">
                    <InvoiceStatusBadge :status="invoice.status"></InvoiceStatusBadge>
                </div>
                <div
                    class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12"
                    @click.stop>
                    <InvoiceMoreOptionsDropdown
                        :invoice="invoice"
                        @view="view"
                        @download="downloadInvoice(invoice.id)"
                        @copy="showCopyModal = true"
                        @delete="remove"></InvoiceMoreOptionsDropdown>
                </div>
            </TableRow>
        </ContextMenuTrigger>
        <ContextMenuContent class="min-w-[160px]">
            <ContextMenuItem class="space-x-3" @select="view">
                <EyeIcon class="w-4 h-4 text-icon-default" />
                <span>View</span>
            </ContextMenuItem>
            <ContextMenuItem
                v-if="canDownloadInvoices()"
                class="space-x-3"
                @select="downloadInvoice(invoice.id)">
                <ArrowDownTrayIcon class="w-4 h-4 text-icon-default" />
                <span>Download PDF</span>
            </ContextMenuItem>
            <ContextMenuItem class="space-x-3" @select="showCopyModal = true">
                <PencilSquareIcon class="w-4 h-4 text-icon-default" />
                <span>Copy as draft</span>
            </ContextMenuItem>
            <ContextMenuSeparator v-if="canDeleteInvoices() && invoice.status === 'draft'" />
            <ContextMenuItem
                v-if="canDeleteInvoices() && invoice.status === 'draft'"
                class="space-x-3 text-destructive"
                @select="remove()">
                <TrashIcon class="w-4 h-4 text-icon-default" />
                <span>Delete</span>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<style scoped></style>
