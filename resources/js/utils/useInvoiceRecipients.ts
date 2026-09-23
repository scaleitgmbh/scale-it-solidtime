import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import type {
    InvoiceRecipientBody,
    InvoiceRecipient,
    InvoiceRecipientUpdateBody,
    InvoiceRecipientDuplicateBody,
} from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export const useInvoiceRecipientsStore = defineStore('invoiceRecipients', () => {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    async function createInvoiceRecipient(
        body: InvoiceRecipientBody
    ): Promise<InvoiceRecipient | undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.createInvoiceRecipient(body, {
                        params: { organization },
                    }),
                'Invoice recipient created successfully',
                'Failed to create invoice recipient'
            );
            queryClient.invalidateQueries({ queryKey: ['invoice-recipients'] });
            return response?.data;
        }
    }

    async function updateInvoiceRecipient(
        invoiceRecipientId: string,
        body: InvoiceRecipientUpdateBody
    ) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.updateInvoiceRecipient(body, {
                        params: { organization, invoiceRecipient: invoiceRecipientId },
                    }),
                'Invoice recipient updated successfully',
                'Failed to update invoice recipient'
            );
            queryClient.invalidateQueries({ queryKey: ['invoice-recipients'] });
        }
    }

    async function duplicateInvoiceRecipient(
        invoiceRecipientId: string,
        body: InvoiceRecipientDuplicateBody = {}
    ) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.duplicateInvoiceRecipient(body, {
                        params: { organization, invoiceRecipient: invoiceRecipientId },
                    }),
                'Invoice recipient duplicated successfully',
                'Failed to duplicate invoice recipient'
            );
            queryClient.invalidateQueries({ queryKey: ['invoice-recipients'] });
            return response?.data;
        }
    }

    async function deleteInvoiceRecipient(invoiceRecipientId: string) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteInvoiceRecipient(undefined, {
                        params: { organization, invoiceRecipient: invoiceRecipientId },
                    }),
                'Invoice recipient deleted successfully',
                'Failed to delete invoice recipient'
            );
            queryClient.invalidateQueries({ queryKey: ['invoice-recipients'] });
        }
    }

    return {
        createInvoiceRecipient,
        updateInvoiceRecipient,
        duplicateInvoiceRecipient,
        deleteInvoiceRecipient,
    };
});
