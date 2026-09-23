import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import type {
    CreateInvoiceBody,
    DetailedInvoice,
    UpdateInvoiceBody,
    CopyInvoiceBody,
    GenerateInvoiceEntriesBody,
    GeneratedInvoiceEntry,
} from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export const useInvoicesStore = defineStore('invoices', () => {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    async function createInvoice(body: CreateInvoiceBody): Promise<DetailedInvoice | undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleApiRequestNotifications(
                () => api.createInvoice(body, { params: { organization } }),
                'Invoice created successfully',
                'Failed to create invoice'
            );
            queryClient.invalidateQueries({ queryKey: ['invoices'] });
            return response?.data;
        }
    }

    async function updateInvoice(
        invoiceId: string,
        body: UpdateInvoiceBody
    ): Promise<DetailedInvoice | undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.updateInvoice(body, {
                        params: { organization, invoice: invoiceId },
                    }),
                'Invoice updated successfully',
                'Failed to update invoice'
            );
            queryClient.invalidateQueries({ queryKey: ['invoices'] });
            queryClient.invalidateQueries({ queryKey: ['invoice', organization, invoiceId] });
            return response?.data;
        }
    }

    async function copyInvoice(
        invoiceId: string,
        body: CopyInvoiceBody
    ): Promise<DetailedInvoice | undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.copyInvoice(body, {
                        params: { organization, invoice: invoiceId },
                    }),
                'Invoice copied successfully',
                'Failed to copy invoice'
            );
            queryClient.invalidateQueries({ queryKey: ['invoices'] });
            return response?.data;
        }
    }

    async function deleteInvoice(invoiceId: string) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteInvoice(undefined, {
                        params: { organization, invoice: invoiceId },
                    }),
                'Invoice deleted successfully',
                'Failed to delete invoice'
            );
            queryClient.invalidateQueries({ queryKey: ['invoices'] });
        }
    }

    async function downloadInvoice(invoiceId: string) {
        const organization = getCurrentOrganizationId();
        if (!organization) {
            return;
        }
        // Open the tab synchronously (within the click's user gesture) so the browser doesn't
        // block it as a popup once the PDF has finished generating on the (async) request below.
        // The signed URL responds with Content-Disposition: attachment, so the browser downloads
        // the file rather than navigating the tab away from about:blank - that's expected.
        const pendingTab = window.open('', '_blank');
        const response = await handleApiRequestNotifications(
            () =>
                api.downloadInvoice(undefined, {
                    params: { organization, invoice: invoiceId },
                }),
            undefined,
            'Failed to download invoice'
        );
        if (response?.download_link && pendingTab) {
            pendingTab.location.href = response.download_link;
        } else {
            pendingTab?.close();
        }
    }

    async function generateInvoiceEntries(
        body: GenerateInvoiceEntriesBody
    ): Promise<GeneratedInvoiceEntry[]> {
        const organization = getCurrentOrganizationId();
        if (!organization) {
            return [];
        }
        const response = await handleApiRequestNotifications(
            () => api.generateInvoiceEntries(body, { params: { organization } }),
            undefined,
            'Failed to generate invoice entries from time entries'
        );
        return response?.data ?? [];
    }

    return {
        createInvoice,
        updateInvoice,
        copyInvoice,
        deleteInvoice,
        downloadInvoice,
        generateInvoiceEntries,
    };
});
