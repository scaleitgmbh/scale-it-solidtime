<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { DocumentTextIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { type Component, computed, ref, watch } from 'vue';
import { type InvoiceIndexEntry } from '@/packages/api/src';
import { router } from '@inertiajs/vue3';
import InvoiceTableRow from '@/Components/Common/Invoice/InvoiceTableRow.vue';
import InvoiceTableHeading from '@/Components/Common/Invoice/InvoiceTableHeading.vue';
import Pagination from '@/packages/ui/src/Pagination.vue';
import { canCreateInvoices } from '@/utils/permissions';
import {
    useSortableTable,
    type SortableColumnDef,
    type SortDirection,
} from '@/utils/useSortableTable';

export type SortColumn = 'reference' | 'recipient' | 'date' | 'total' | 'status';
export type { SortDirection } from '@/utils/useSortableTable';

const props = defineProps<{
    invoices: InvoiceIndexEntry[];
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}>();

const emit = defineEmits<{
    sort: [column: SortColumn, direction: SortDirection];
}>();

const columns: SortableColumnDef<InvoiceIndexEntry, SortColumn>[] = [
    {
        id: 'reference',
        accessorFn: (row: InvoiceIndexEntry) => row.reference.toLowerCase(),
    },
    {
        id: 'recipient',
        accessorFn: (row: InvoiceIndexEntry) => row.recipient.toLowerCase(),
    },
    {
        id: 'date',
        sortDescFirst: true,
        accessorFn: (row: InvoiceIndexEntry) => row.date,
    },
    {
        id: 'total',
        sortDescFirst: true,
        accessorFn: (row: InvoiceIndexEntry) => row.total,
    },
    {
        id: 'status',
        accessorFn: (row: InvoiceIndexEntry) => row.status,
    },
];

const {
    sortedRows: sortedInvoices,
    descFirstColumns,
    nextDirection,
} = useSortableTable({
    data: () => props.invoices,
    columns: () => columns,
    sortColumn: () => props.sortColumn,
    sortDirection: () => props.sortDirection,
    tieBreakColumn: 'date',
});

function handleSort(column: SortColumn) {
    emit('sort', column, nextDirection(column));
}

const PAGE_SIZE = 15;
const currentPage = ref(1);

watch([() => props.sortColumn, () => props.sortDirection, () => props.invoices], () => {
    currentPage.value = 1;
});

const paginatedInvoices = computed(() => {
    const start = (currentPage.value - 1) * PAGE_SIZE;
    return sortedInvoices.value.slice(start, start + PAGE_SIZE);
});
</script>

<template>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="invoice_table"
                class="grid min-w-full"
                style="grid-template-columns: 150px 1fr 120px 120px 100px 80px">
                <InvoiceTableHeading
                    :sort-column="props.sortColumn"
                    :sort-direction="props.sortDirection"
                    :desc-first-columns="descFirstColumns"
                    @sort="handleSort"></InvoiceTableHeading>
                <div v-if="sortedInvoices.length === 0" class="col-span-6 py-24 text-center">
                    <DocumentTextIcon class="w-8 text-icon-default inline pb-2"></DocumentTextIcon>
                    <h3 class="text-text-primary font-semibold">No invoices found</h3>
                    <p v-if="canCreateInvoices()" class="pb-5">Create your first invoice now!</p>
                    <SecondaryButton
                        v-if="canCreateInvoices()"
                        :icon="PlusIcon as Component"
                        @click="router.visit(route('invoices.create'))"
                        >Create your First Invoice
                    </SecondaryButton>
                </div>
                <template v-for="invoice in paginatedInvoices" :key="invoice.id">
                    <InvoiceTableRow :invoice="invoice"></InvoiceTableRow>
                </template>
            </div>
        </div>
    </div>
    <Pagination
        v-model:page="currentPage"
        :total="sortedInvoices.length"
        :items-per-page="PAGE_SIZE"></Pagination>
</template>
