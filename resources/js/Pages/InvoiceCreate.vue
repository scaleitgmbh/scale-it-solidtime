<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { DocumentTextIcon } from '@heroicons/vue/20/solid';
import PageTitle from '@/Components/Common/PageTitle.vue';
import InvoiceEditor from '@/Components/Common/Invoice/InvoiceEditor.vue';
</script>

<template>
    <AppLayout title="Create Invoice" data-testid="invoice_create_view">
        <MainContainer class="py-5 border-b border-default-background-separator">
            <PageTitle :icon="DocumentTextIcon" title="Create Invoice"> </PageTitle>
        </MainContainer>
        <MainContainer class="py-6">
            <InvoiceEditor></InvoiceEditor>
        </MainContainer>
    </AppLayout>
</template>
