<script setup lang="ts">
import {
    EyeIcon,
    ArrowDownTrayIcon,
    DocumentDuplicateIcon,
    TrashIcon,
} from '@heroicons/vue/20/solid';
import type { InvoiceIndexEntry } from '@/packages/api/src';
import { canDeleteInvoices, canDownloadInvoices } from '@/utils/permissions';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/packages/ui/src';

const emit = defineEmits<{
    view: [];
    download: [];
    copy: [];
    delete: [];
}>();
const props = defineProps<{
    invoice: InvoiceIndexEntry;
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                class="focus-visible:outline-none focus-visible:bg-card-background rounded-full focus-visible:ring-2 focus-visible:ring-ring focus-visible:opacity-100 hover:bg-card-background group-hover:opacity-100 opacity-20 transition-opacity text-text-secondary"
                :aria-label="'Actions for invoice ' + props.invoice.reference">
                <svg
                    class="h-8 w-8 p-1 rounded-full"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        fill="none"
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M12 5.92A.96.96 0 1 0 12 4a.96.96 0 0 0 0 1.92m0 7.04a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92M12 20a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92" />
                </svg>
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="min-w-[150px]" align="end">
            <DropdownMenuItem
                class="flex items-center space-x-3 cursor-pointer"
                @click="emit('view')">
                <EyeIcon class="w-5 text-icon-active" />
                <span>View</span>
            </DropdownMenuItem>
            <DropdownMenuItem
                v-if="canDownloadInvoices()"
                class="flex items-center space-x-3 cursor-pointer"
                @click="emit('download')">
                <ArrowDownTrayIcon class="w-5 text-icon-active" />
                <span>Download PDF</span>
            </DropdownMenuItem>
            <DropdownMenuItem
                class="flex items-center space-x-3 cursor-pointer"
                @click="emit('copy')">
                <DocumentDuplicateIcon class="w-5 text-icon-active" />
                <span>Copy as draft</span>
            </DropdownMenuItem>
            <DropdownMenuItem
                v-if="canDeleteInvoices() && invoice.status === 'draft'"
                class="flex items-center space-x-3 cursor-pointer text-destructive focus:text-destructive"
                @click="emit('delete')">
                <TrashIcon class="w-5" />
                <span>Delete</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped></style>
