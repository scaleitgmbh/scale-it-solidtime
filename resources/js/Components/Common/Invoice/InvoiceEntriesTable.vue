<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import BillableRateInput from '@/packages/ui/src/Input/BillableRateInput.vue';
import { TrashIcon, PlusIcon } from '@heroicons/vue/20/solid';
import { Button } from '@/packages/ui/src/Buttons';
import { formatCents } from '@/packages/ui/src/utils/money';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { computed } from 'vue';

const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);

export interface EditableInvoiceEntry {
    id?: string | null;
    name: string;
    description: string | null;
    unit_price: number;
    quantity: number;
    time_entry_ids?: string[];
}

const entries = defineModel<EditableInvoiceEntry[]>({ required: true });

const props = defineProps<{
    currency: string;
}>();

function addRow() {
    entries.value = [...entries.value, { name: '', description: null, unit_price: 0, quantity: 1 }];
}

function removeRow(index: number) {
    entries.value = entries.value.filter((_, i) => i !== index);
}

function lineTotal(entry: EditableInvoiceEntry) {
    return Math.round(entry.unit_price * entry.quantity);
}

function money(amountCents: number): string {
    return (
        formatCents(
            amountCents,
            props.currency,
            organization.value?.currency_format,
            organization.value?.currency_symbol,
            organization.value?.number_format
        ) ?? ''
    );
}

const hasEntries = computed(() => entries.value.length > 0);
</script>

<template>
    <div class="border border-card-border rounded-lg overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-card-background">
                <tr>
                    <th class="text-left font-medium py-2 px-3 text-text-secondary">Item</th>
                    <th class="text-right font-medium py-2 px-3 text-text-secondary w-28">
                        Quantity
                    </th>
                    <th class="text-right font-medium py-2 px-3 text-text-secondary w-40">
                        Unit Price
                    </th>
                    <th class="text-right font-medium py-2 px-3 text-text-secondary w-32">Total</th>
                    <th class="w-10"></th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(entry, index) in entries"
                    :key="index"
                    class="border-t border-card-border">
                    <td class="py-2 px-3 align-top">
                        <TextInput
                            v-model="entry.name"
                            type="text"
                            placeholder="Item name"
                            class="w-full mb-1" />
                        <TextInput
                            v-model="entry.description"
                            type="text"
                            placeholder="Description (optional)"
                            size="sm"
                            class="w-full" />
                    </td>
                    <td class="py-2 px-3 align-top">
                        <TextInput
                            v-model.number="entry.quantity"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full text-right" />
                    </td>
                    <td class="py-2 px-3 align-top">
                        <BillableRateInput
                            v-model="entry.unit_price"
                            :name="'entry-unit-price-' + index"
                            :currency="currency"
                            class="w-full" />
                    </td>
                    <td class="py-2 px-3 align-top text-right whitespace-nowrap">
                        {{ money(lineTotal(entry)) }}
                    </td>
                    <td class="py-2 px-3 align-top text-right">
                        <Button variant="ghost" size="icon" @click="removeRow(index)">
                            <TrashIcon class="w-4 h-4 text-icon-default" />
                        </Button>
                    </td>
                </tr>
                <tr v-if="!hasEntries">
                    <td colspan="5" class="py-6 px-3 text-center text-text-secondary">
                        No line items yet.
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="p-2 bg-card-background border-t border-card-border">
            <Button variant="ghost" size="sm" @click="addRow">
                <PlusIcon class="w-4 h-4 mr-1" />
                Add line
            </Button>
        </div>
    </div>
</template>
