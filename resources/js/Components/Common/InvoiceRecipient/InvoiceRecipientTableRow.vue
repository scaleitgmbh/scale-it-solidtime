<script setup lang="ts">
import type { InvoiceRecipient } from '@/packages/api/src';
import { ref } from 'vue';
import { CheckCircleIcon, ArchiveBoxIcon } from '@heroicons/vue/24/outline';
import {
    PencilSquareIcon,
    ArchiveBoxIcon as ArchiveBoxIconSolid,
    TrashIcon,
    DocumentDuplicateIcon,
} from '@heroicons/vue/20/solid';
import { useInvoiceRecipientsStore } from '@/utils/useInvoiceRecipients';
import InvoiceRecipientMoreOptionsDropdown from '@/Components/Common/InvoiceRecipient/InvoiceRecipientMoreOptionsDropdown.vue';
import TableRow from '@/Components/TableRow.vue';
import InvoiceRecipientEditModal from '@/Components/Common/InvoiceRecipient/InvoiceRecipientEditModal.vue';
import { canUpdateInvoiceRecipients, canDeleteInvoiceRecipients } from '@/utils/permissions';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/packages/ui/src';

const { updateInvoiceRecipient, deleteInvoiceRecipient, duplicateInvoiceRecipient } =
    useInvoiceRecipientsStore();

const props = defineProps<{
    invoiceRecipient: InvoiceRecipient;
}>();

function remove() {
    deleteInvoiceRecipient(props.invoiceRecipient.id);
}

function duplicate() {
    duplicateInvoiceRecipient(props.invoiceRecipient.id);
}

function archive() {
    updateInvoiceRecipient(props.invoiceRecipient.id, {
        name: props.invoiceRecipient.name,
        is_archived: !props.invoiceRecipient.is_archived,
    });
}

const showEditModal = ref(false);
</script>

<template>
    <ContextMenu>
        <ContextMenuTrigger as-child>
            <TableRow>
                <InvoiceRecipientEditModal
                    v-model:show="showEditModal"
                    :invoice-recipient="invoiceRecipient"></InvoiceRecipientEditModal>
                <div
                    class="whitespace-nowrap flex items-center space-x-5 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
                    <span>
                        {{ invoiceRecipient.name }}
                    </span>
                </div>
                <div
                    class="whitespace-nowrap flex items-center px-3 py-4 text-sm text-text-primary">
                    <span>{{ invoiceRecipient.email || '-' }}</span>
                </div>
                <div
                    class="whitespace-nowrap flex items-center px-3 py-4 text-sm text-text-primary">
                    <span> {{ invoiceRecipient.invoices_count }} Invoices </span>
                </div>
                <div
                    class="whitespace-nowrap px-3 py-4 text-sm text-text-primary flex space-x-1.5 items-center">
                    <template v-if="invoiceRecipient.is_archived">
                        <ArchiveBoxIcon class="w-4 text-icon-default"></ArchiveBoxIcon>
                        <span>Archived</span>
                    </template>
                    <template v-else>
                        <CheckCircleIcon class="w-4 text-icon-default"></CheckCircleIcon>
                        <span>Active</span>
                    </template>
                </div>
                <div
                    class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
                    <InvoiceRecipientMoreOptionsDropdown
                        :invoice-recipient="invoiceRecipient"
                        @edit="showEditModal = true"
                        @archive="archive"
                        @duplicate="duplicate"
                        @delete="remove"></InvoiceRecipientMoreOptionsDropdown>
                </div>
            </TableRow>
        </ContextMenuTrigger>
        <ContextMenuContent class="min-w-[160px]">
            <ContextMenuItem
                v-if="canUpdateInvoiceRecipients()"
                class="space-x-3"
                @select="showEditModal = true">
                <PencilSquareIcon class="w-4 h-4 text-icon-default" />
                <span>Edit</span>
            </ContextMenuItem>
            <ContextMenuItem class="space-x-3" @select="duplicate()">
                <DocumentDuplicateIcon class="w-4 h-4 text-icon-default" />
                <span>Duplicate</span>
            </ContextMenuItem>
            <ContextMenuItem
                v-if="canUpdateInvoiceRecipients()"
                class="space-x-3"
                @select="archive()">
                <ArchiveBoxIconSolid class="w-4 h-4 text-icon-default" />
                <span>{{ invoiceRecipient.is_archived ? 'Unarchive' : 'Archive' }}</span>
            </ContextMenuItem>
            <ContextMenuSeparator v-if="canDeleteInvoiceRecipients()" />
            <ContextMenuItem
                v-if="canDeleteInvoiceRecipients()"
                class="space-x-3 text-destructive"
                :disabled="
                    invoiceRecipient.has_non_draft_invoices || invoiceRecipient.invoices_count > 0
                "
                @select="remove()">
                <TrashIcon class="w-4 h-4 text-icon-default" />
                <span>Delete</span>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<style scoped></style>
