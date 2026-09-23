<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { type Component, computed, ref, watch } from 'vue';
import { type InvoiceRecipient } from '@/packages/api/src';
import InvoiceRecipientTableRow from '@/Components/Common/InvoiceRecipient/InvoiceRecipientTableRow.vue';
import InvoiceRecipientCreateModal from '@/Components/Common/InvoiceRecipient/InvoiceRecipientCreateModal.vue';
import InvoiceRecipientTableHeading from '@/Components/Common/InvoiceRecipient/InvoiceRecipientTableHeading.vue';
import Pagination from '@/packages/ui/src/Pagination.vue';
import { canCreateInvoiceRecipients } from '@/utils/permissions';
import {
    useSortableTable,
    type SortableColumnDef,
    type SortDirection,
} from '@/utils/useSortableTable';

export type SortColumn = 'name' | 'email' | 'invoices_count' | 'status';
export type { SortDirection } from '@/utils/useSortableTable';

const props = defineProps<{
    invoiceRecipients: InvoiceRecipient[];
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}>();

const emit = defineEmits<{
    sort: [column: SortColumn, direction: SortDirection];
}>();

const createInvoiceRecipient = ref(false);

const columns: SortableColumnDef<InvoiceRecipient, SortColumn>[] = [
    {
        id: 'name',
        accessorFn: (row: InvoiceRecipient) => row.name.toLowerCase(),
    },
    {
        id: 'email',
        accessorFn: (row: InvoiceRecipient) => row.email?.toLowerCase(),
    },
    {
        id: 'invoices_count',
        sortDescFirst: true,
        accessorFn: (row: InvoiceRecipient) => row.invoices_count,
    },
    {
        id: 'status',
        accessorFn: (row: InvoiceRecipient) => (row.is_archived ? 1 : 0),
    },
];

const {
    sortedRows: sortedInvoiceRecipients,
    descFirstColumns,
    nextDirection,
} = useSortableTable({
    data: () => props.invoiceRecipients,
    columns: () => columns,
    sortColumn: () => props.sortColumn,
    sortDirection: () => props.sortDirection,
    tieBreakColumn: 'name',
});

function handleSort(column: SortColumn) {
    emit('sort', column, nextDirection(column));
}

const PAGE_SIZE = 15;
const currentPage = ref(1);

watch([() => props.sortColumn, () => props.sortDirection, () => props.invoiceRecipients], () => {
    currentPage.value = 1;
});

const paginatedInvoiceRecipients = computed(() => {
    const start = (currentPage.value - 1) * PAGE_SIZE;
    return sortedInvoiceRecipients.value.slice(start, start + PAGE_SIZE);
});
</script>

<template>
    <InvoiceRecipientCreateModal
        v-model:show="createInvoiceRecipient"></InvoiceRecipientCreateModal>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="invoice_recipient_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 1fr 120px 150px 80px">
                <InvoiceRecipientTableHeading
                    :sort-column="props.sortColumn"
                    :sort-direction="props.sortDirection"
                    :desc-first-columns="descFirstColumns"
                    @sort="handleSort"></InvoiceRecipientTableHeading>
                <div
                    v-if="sortedInvoiceRecipients.length === 0"
                    class="col-span-5 py-24 text-center">
                    <UserCircleIcon class="w-8 text-icon-default inline pb-2"></UserCircleIcon>
                    <h3 class="text-text-primary font-semibold">No invoice recipients found</h3>
                    <p v-if="canCreateInvoiceRecipients()" class="pb-5">
                        Create your first invoice recipient now!
                    </p>
                    <SecondaryButton
                        v-if="canCreateInvoiceRecipients()"
                        :icon="PlusIcon as Component"
                        @click="createInvoiceRecipient = true"
                        >Create your First Invoice Recipient
                    </SecondaryButton>
                </div>
                <template
                    v-for="invoiceRecipient in paginatedInvoiceRecipients"
                    :key="invoiceRecipient.id">
                    <InvoiceRecipientTableRow
                        :invoice-recipient="invoiceRecipient"></InvoiceRecipientTableRow>
                </template>
            </div>
        </div>
    </div>
    <Pagination
        v-model:page="currentPage"
        :total="sortedInvoiceRecipients.length"
        :items-per-page="PAGE_SIZE"></Pagination>
</template>
