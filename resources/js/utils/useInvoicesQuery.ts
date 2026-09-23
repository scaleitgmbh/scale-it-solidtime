import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { InvoiceIndexEntry } from '@/packages/api/src';
import { computed } from 'vue';
import { fetchAllPages } from '@/utils/fetchAllPages';

export async function fetchAllInvoices(organizationId: string): Promise<InvoiceIndexEntry[]> {
    return fetchAllPages((page) =>
        api.getInvoices({
            params: { organization: organizationId },
            queries: { page },
        })
    );
}

export function useInvoicesQuery() {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: computed(() => ['invoices', getCurrentOrganizationId()]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            const data = await fetchAllInvoices(organizationId);
            return { data };
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
    });

    const invoices = computed<InvoiceIndexEntry[]>(() => query.data.value?.data ?? []);

    const invalidateInvoices = () => {
        queryClient.invalidateQueries({ queryKey: ['invoices'] });
    };

    return {
        ...query,
        invoices,
        invalidateInvoices,
    };
}
