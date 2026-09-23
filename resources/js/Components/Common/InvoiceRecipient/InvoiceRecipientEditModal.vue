<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, ref } from 'vue';
import type { InvoiceRecipient, InvoiceRecipientUpdateBody } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useInvoiceRecipientsStore } from '@/utils/useInvoiceRecipients';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useClientsStore } from '@/utils/useClients';
import ClientDropdown from '@/packages/ui/src/Client/ClientDropdown.vue';
import { Button } from '@/packages/ui/src/Buttons';
import { ChevronDown } from '@lucide/vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';

const { updateInvoiceRecipient } = useInvoiceRecipientsStore();
const { clients } = useClientsQuery();
const { createClient } = useClientsStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    invoiceRecipient: InvoiceRecipient;
}>();

const recipient = ref<InvoiceRecipientUpdateBody>({
    name: props.invoiceRecipient.name,
    client_id: props.invoiceRecipient.client_id,
    vatin: props.invoiceRecipient.vatin,
    address_line_1: props.invoiceRecipient.address_line_1,
    address_post_code: props.invoiceRecipient.address_post_code,
    address_city: props.invoiceRecipient.address_city,
    address_country: props.invoiceRecipient.address_country,
    phone: props.invoiceRecipient.phone,
    email: props.invoiceRecipient.email,
});

const currentClientName = computed(
    () =>
        clients.value.find((client) => client.id === recipient.value.client_id)?.name ?? 'No Client'
);

async function submit() {
    saving.value = true;
    await updateInvoiceRecipient(props.invoiceRecipient.id, recipient.value);
    saving.value = false;
    show.value = false;
}

const nameInput = ref<HTMLInputElement | null>(null);
useFocus(nameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title> Update Invoice Recipient </template>

        <template #content>
            <div class="space-y-4">
                <Field>
                    <FieldLabel for="editRecipientName">Name</FieldLabel>
                    <TextInput
                        id="editRecipientName"
                        ref="nameInput"
                        v-model="recipient.name"
                        type="text"
                        class="block w-full"
                        required
                        @keydown.enter="submit" />
                </Field>
                <Field>
                    <FieldLabel for="editRecipientClient">Linked Client (optional)</FieldLabel>
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
                        <FieldLabel for="editRecipientVatin">VAT ID</FieldLabel>
                        <TextInput
                            id="editRecipientVatin"
                            v-model="recipient.vatin"
                            type="text"
                            class="block w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="editRecipientEmail">Email</FieldLabel>
                        <TextInput
                            id="editRecipientEmail"
                            v-model="recipient.email"
                            type="email"
                            class="block w-full" />
                    </Field>
                </div>
                <Field>
                    <FieldLabel for="editRecipientAddress1">Address Line 1</FieldLabel>
                    <TextInput
                        id="editRecipientAddress1"
                        v-model="recipient.address_line_1"
                        type="text"
                        class="block w-full" />
                </Field>
                <div class="grid grid-cols-2 gap-4">
                    <Field>
                        <FieldLabel for="editRecipientPostCode">Post Code</FieldLabel>
                        <TextInput
                            id="editRecipientPostCode"
                            v-model="recipient.address_post_code"
                            type="text"
                            class="block w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="editRecipientCity">City</FieldLabel>
                        <TextInput
                            id="editRecipientCity"
                            v-model="recipient.address_city"
                            type="text"
                            class="block w-full" />
                    </Field>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <Field>
                        <FieldLabel for="editRecipientCountry">Country</FieldLabel>
                        <TextInput
                            id="editRecipientCountry"
                            v-model="recipient.address_country"
                            type="text"
                            class="block w-full" />
                    </Field>
                    <Field>
                        <FieldLabel for="editRecipientPhone">Phone</FieldLabel>
                        <TextInput
                            id="editRecipientPhone"
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
                Update Invoice Recipient
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
