import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import type { UpdateInvoiceSettingsBody } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export const useInvoiceSettingsStore = defineStore('invoiceSettings', () => {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    async function updateInvoiceSettings(body: UpdateInvoiceSettingsBody) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.updateInvoiceSettings(body, {
                        params: { organization },
                    }),
                'Invoice settings updated successfully',
                'Failed to update invoice settings'
            );
            queryClient.invalidateQueries({ queryKey: ['invoice-settings'] });
        }
    }

    return { updateInvoiceSettings };
});
