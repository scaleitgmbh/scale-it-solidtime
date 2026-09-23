import { useQuery } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { computed } from 'vue';

export function useInvoiceSettingsQuery() {
    const query = useQuery({
        queryKey: computed(() => ['invoice-settings', getCurrentOrganizationId()]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            return (
                await api.getInvoiceSettings({
                    params: { organization: organizationId },
                })
            ).data;
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
    });

    const invoiceSettings = computed(() => query.data.value);

    return {
        ...query,
        invoiceSettings,
    };
}
