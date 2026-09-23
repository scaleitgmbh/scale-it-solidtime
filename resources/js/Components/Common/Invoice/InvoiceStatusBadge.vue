<script setup lang="ts">
import { computed } from 'vue';
import type { InvoiceStatus } from '@/packages/api/src';

const props = defineProps<{
    status: InvoiceStatus;
}>();

const label = computed(() => {
    return {
        draft: 'Draft',
        sent: 'Sent',
        paid: 'Paid',
        cancelled: 'Cancelled',
    }[props.status];
});

const classes = computed(() => {
    return {
        draft: 'bg-card-background text-text-secondary border-card-border',
        sent: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        paid: 'bg-green-500/10 text-green-400 border-green-500/20',
        cancelled: 'bg-red-500/10 text-red-400 border-red-500/20',
    }[props.status];
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium"
        :class="classes">
        {{ label }}
    </span>
</template>
