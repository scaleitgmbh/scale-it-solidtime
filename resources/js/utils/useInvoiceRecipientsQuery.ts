import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { InvoiceRecipient } from '@/packages/api/src';
import { computed } from 'vue';
import { fetchAllPages } from '@/utils/fetchAllPages';

export async function fetchAllInvoiceRecipients(
    organizationId: string
): Promise<InvoiceRecipient[]> {
    return fetchAllPages((page) =>
        api.getInvoiceRecipients({
            params: { organization: organizationId },
            queries: { archived: 'all', page },
        })
    );
}

export function useInvoiceRecipientsQuery() {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: computed(() => ['invoice-recipients', getCurrentOrganizationId()]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            const data = await fetchAllInvoiceRecipients(organizationId);
            return { data };
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
    });

    const invoiceRecipients = computed<InvoiceRecipient[]>(() => query.data.value?.data ?? []);

    const invalidateInvoiceRecipients = () => {
        queryClient.invalidateQueries({ queryKey: ['invoice-recipients'] });
    };

    return {
        ...query,
        invoiceRecipients,
        invalidateInvoiceRecipients,
    };
}
