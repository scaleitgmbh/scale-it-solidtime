<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import type { InvoiceRecipientBody } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useInvoiceRecipientsStore } from '@/utils/useInvoiceRecipients';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useClientsStore } from '@/utils/useClients';
import ClientDropdown from '@/packages/ui/src/Client/ClientDropdown.vue';
import { Button } from '@/packages/ui/src/Buttons';
import { ChevronDown } from '@lucide/vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import { computed } from 'vue';

const { createInvoiceRecipient } = useInvoiceRecipientsStore();
const { clients } = useClientsQuery();
const { createClient } = useClientsStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const recipient = ref<InvoiceRecipientBody>({
    name: '',
    client_id: null,
});

const currentClientName = computed(
    () =>
        clients.value.find((client) => client.id === recipient.value.client_id)?.name ?? 'No Client'
);

async function submit() {
    saving.value = true;
    await createInvoiceRecipient(recipient.value);
    saving.value = false;
    recipient.value = { name: '', client_id: null };
    show.value = false;
}

const nameInput = ref<HTMLInputElement | null>(null);
useFocus(nameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title> Create Invoice Recipient </template>

        <template #content>
            <div class="space-y-4">
                <Field>
                    <FieldLabel for="recipientName">Name</FieldLabel>
                    <TextInput
                        id="recipientName"
                        ref="nameInput"
                        v-model="recipient.name"
                        type="text"
                        placeholder="Recipient Name"
                        class="block w-full"
                        required
                        @keydown.enter="submit" />
                </Field>
                <Field>
                    <FieldLabel for="recipientClient">Linked Client (optional)</FieldLabel>
                    <ClientDropdown
                        v-model="recipient.client_id"
                        :create-client="createClient"
                        :clients="clients">
                        <template #trigger>
                            <Button variant="input" class="w-full justify-between">
                                <span class="truncate">{{ currentClientName }}</span>
                                <ChevronDown class="w-4 h-4 text-icon-default" />
                            </Button>
                        </template>
                    </ClientDropdown>
                </Field>
                <div class="grid grid-cols-2 gap-4">
                    <Field>
                        <FieldLabel for="recipientVatin">VAT ID</FieldLabel>
                        <TextInput
                            id="recipientVatin"
                            v-model="recipient.vatin"
                            type="text"
                            class="block w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="recipientEmail">Email</FieldLabel>
                        <TextInput
                            id="recipientEmail"
                            v-model="recipient.email"
                            type="email"
                            class="block w-full" />
                    </Field>
                </div>
                <Field>
                    <FieldLabel for="recipientAddress1">Address Line 1</FieldLabel>
                    <TextInput
                        id="recipientAddress1"
                        v-model="recipient.address_line_1"
                        type="text"
                        class="block w-full" />
                </Field>
                <div class="grid grid-cols-2 gap-4">
                    <Field>
                        <FieldLabel for="recipientPostCode">Post Code</FieldLabel>
                        <TextInput
                            id="recipientPostCode"
                            v-model="recipient.address_post_code"
                            type="text"
                            class="block w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="recipientCity">City</FieldLabel>
                        <TextInput
                            id="recipientCity"
                            v-model="recipient.address_city"
                            type="text"
                            class="block w-full" />
                    </Field>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <Field>
                        <FieldLabel for="recipientCountry">Country</FieldLabel>
                        <TextInput
                            id="recipientCountry"
                            v-model="recipient.address_country"
                            type="text"
                            class="block w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="recipientPhone">Phone</FieldLabel>
                        <TextInput
                            id="recipientPhone"
                            v-model="recipient.phone"
                            type="text"
                            class="block w-full" />
                    </Field>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Create Invoice Recipient
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
