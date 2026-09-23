<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { DocumentTextIcon } from '@heroicons/vue/20/solid';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useInvoicesQuery } from '@/utils/useInvoicesQuery';
import InvoiceTable from '@/Components/Common/Invoice/InvoiceTable.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateInvoices } from '@/utils/permissions';
import { TabBar, TabBarItem } from '@/packages/ui/src';
import { useTableSortState } from '@/utils/useTableSortState';
import type { SortColumn } from '@/Components/Common/Invoice/InvoiceTable.vue';
import type { InvoiceStatus } from '@/packages/api/src';

const { invoices } = useInvoicesQuery();

const activeTab = ref<InvoiceStatus | 'all'>('all');

const { tableState, handleSort } = useTableSortState<SortColumn>('invoice-table-state', {
    sortColumn: 'date',
    sortDirection: 'desc',
});

const shownInvoices = computed(() => {
    if (activeTab.value === 'all') {
        return invoices.value;
    }
    return invoices.value.filter((invoice) => invoice.status === activeTab.value);
});
</script>

<template>
    <AppLayout title="Invoices" data-testid="invoices_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="DocumentTextIcon" title="Invoices"> </PageTitle>
                <TabBar v-model="activeTab">
                    <TabBarItem value="all">All</TabBarItem>
                    <TabBarItem value="draft">Draft</TabBarItem>
                    <TabBarItem value="sent">Sent</TabBarItem>
                    <TabBarItem value="paid">Paid</TabBarItem>
                    <TabBarItem value="cancelled">Cancelled</TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton
                v-if="canCreateInvoices()"
                :icon="PlusIcon"
                @click="router.visit(route('invoices.create'))"
                >Create Invoice</SecondaryButton
            >
        </MainContainer>
        <InvoiceTable
            :invoices="shownInvoices"
            :sort-column="tableState.sortColumn"
            :sort-direction="tableState.sortDirection"
            @sort="handleSort"></InvoiceTable>
    </AppLayout>
</template>
