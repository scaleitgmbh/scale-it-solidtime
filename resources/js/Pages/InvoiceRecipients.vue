<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import { computed, ref } from 'vue';
import { useInvoiceRecipientsQuery } from '@/utils/useInvoiceRecipientsQuery';
import InvoiceRecipientTable from '@/Components/Common/InvoiceRecipient/InvoiceRecipientTable.vue';
import InvoiceRecipientCreateModal from '@/Components/Common/InvoiceRecipient/InvoiceRecipientCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateInvoiceRecipients } from '@/utils/permissions';
import { TabBar, TabBarItem } from '@/packages/ui/src';
import { useTableSortState } from '@/utils/useTableSortState';
import type { SortColumn } from '@/Components/Common/InvoiceRecipient/InvoiceRecipientTable.vue';

const { invoiceRecipients } = useInvoiceRecipientsQuery();

const activeTab = ref<'active' | 'archived'>('active');

const createInvoiceRecipient = ref(false);

const { tableState, handleSort } = useTableSortState<SortColumn>('invoice-recipient-table-state', {
    sortColumn: 'name',
    sortDirection: 'asc',
});

const shownInvoiceRecipients = computed(() => {
    return invoiceRecipients.value.filter((invoiceRecipient) => {
        if (activeTab.value === 'active') {
            return !invoiceRecipient.is_archived;
        }
        return invoiceRecipient.is_archived;
    });
});
</script>

<template>
    <AppLayout title="Invoice Recipients" data-testid="invoice_recipients_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="UserCircleIcon" title="Invoice Recipients"> </PageTitle>
                <TabBar v-model="activeTab">
                    <TabBarItem value="active">Active</TabBarItem>
                    <TabBarItem value="archived"> Archived </TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton
                v-if="canCreateInvoiceRecipients()"
                :icon="PlusIcon"
                @click="createInvoiceRecipient = true"
                >Create Invoice Recipient</SecondaryButton
            >
            <InvoiceRecipientCreateModal
                v-model:show="createInvoiceRecipient"></InvoiceRecipientCreateModal>
        </MainContainer>
        <InvoiceRecipientTable
            :invoice-recipients="shownInvoiceRecipients"
            :sort-column="tableState.sortColumn"
            :sort-direction="tableState.sortDirection"
            @sort="handleSort"></InvoiceRecipientTable>
    </AppLayout>
</template>
