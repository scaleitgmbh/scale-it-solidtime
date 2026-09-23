import { useQuery } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { computed, type MaybeRefOrGetter, toValue } from 'vue';

export function useInvoiceQuery(invoiceId: MaybeRefOrGetter<string | undefined>) {
    const query = useQuery({
        queryKey: computed(() => ['invoice', getCurrentOrganizationId(), toValue(invoiceId)]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            const id = toValue(invoiceId);
            if (!organizationId || !id) throw new Error('No organization or invoice');
            return (
                await api.getInvoice({
                    params: { organization: organizationId, invoice: id },
                })
            ).data;
        },
        enabled: () => !!getCurrentOrganizationId() && !!toValue(invoiceId),
        staleTime: 1000 * 30, // 30 seconds
    });

    const invoice = computed(() => query.data.value);

    return {
        ...query,
        invoice,
    };
}
