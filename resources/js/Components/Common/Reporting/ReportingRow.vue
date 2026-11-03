<script setup lang="ts">
import { formatReportingDuration } from '@/packages/ui/src/utils/time';
import { formatCents } from '@/packages/ui/src/utils/money';
import GroupedItemsCountButton from '@/packages/ui/src/GroupedItemsCountButton.vue';
import { ref, inject, computed, type ComputedRef } from 'vue';
import { twMerge } from 'tailwind-merge';
import type { Organization } from '@/packages/api/src';

type AggregatedGroupedData = GroupedData & {
    grouped_data?: GroupedData[] | null;
};

type GroupedData = {
    key: string | null;
    seconds: number;
    cost: number | null;
    description: string | null | undefined;
    time_entry_descriptions?: string[];
};

const props = defineProps<{
    entry: AggregatedGroupedData;
    indent?: boolean;
    currency: string;
    showCost?: boolean;
    showTimeEntryDescriptions?: boolean;
}>();

const expanded = ref(false);

const columnCount = computed(
    () => 2 + (props.showCost ? 1 : 0) + (props.showTimeEntryDescriptions ? 1 : 0)
);

const subGridTemplateColumns = computed(() => {
    if (props.showTimeEntryDescriptions) {
        return `minmax(0, 1fr) minmax(0, 1.5fr) 100px ${props.showCost ? '150px' : ''}`;
    }
    return `1fr 150px ${props.showCost ? '150px' : ''}`;
});

const organization = inject<ComputedRef<Organization>>('organization');
</script>

<template>
    <div
        class="contents text-text-primary [&>*]:transition [&>*]:border-card-background-separator [&>*]:border-b [&>*]:min-h-[50px]">
        <div :class="twMerge('pl-6 flex items-center space-x-3', props.indent ? 'pl-16' : '')">
            <GroupedItemsCountButton
                v-if="entry.grouped_data && entry.grouped_data?.length > 0"
                :expanded="expanded"
                @click="expanded = !expanded">
                {{ entry.grouped_data?.length }}
            </GroupedItemsCountButton>
            <span>
                {{ entry.description }}
            </span>
        </div>
        <div
            v-if="showTimeEntryDescriptions"
            class="pl-3 pr-3 py-2 flex items-center text-sm text-text-secondary"
            :title="entry.time_entry_descriptions?.join('\n')">
            <span class="line-clamp-2 break-words">
                {{ entry.time_entry_descriptions?.join('; ') || '--' }}
            </span>
        </div>
        <div class="justify-end flex items-center" :class="!showCost ? 'pr-6' : ''">
            {{
                formatReportingDuration(
                    entry.seconds,
                    organization?.interval_format,
                    organization?.number_format
                )
            }}
        </div>
        <div v-if="showCost" class="justify-end pr-6 flex items-center">
            {{
                entry.cost
                    ? formatCents(
                          entry.cost,
                          props.currency,
                          organization?.currency_format,
                          organization?.currency_symbol,
                          organization?.number_format
                      )
                    : '--'
            }}
        </div>
    </div>
    <div
        v-if="expanded && entry.grouped_data"
        class="grid bg-tertiary"
        :style="`grid-column: span ${columnCount} / span ${columnCount}; grid-template-columns: ${subGridTemplateColumns}`">
        <ReportingRow
            v-for="subEntry in entry.grouped_data"
            :key="subEntry.key ?? 'none'"
            :currency="props.currency"
            :show-cost="showCost"
            :show-time-entry-descriptions="showTimeEntryDescriptions"
            indent
            :entry="subEntry"></ReportingRow>
    </div>
</template>

<style scoped></style>
