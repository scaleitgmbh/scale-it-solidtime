<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref, watch } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useInvoicesStore } from '@/utils/useInvoices';
import { router } from '@inertiajs/vue3';
import { Field, FieldLabel } from '@/packages/ui/src/field';

const { copyInvoice } = useInvoicesStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    invoiceId: string;
    suggestedReference: string;
}>();

const reference = ref(props.suggestedReference);

watch(
    () => props.suggestedReference,
    (value) => (reference.value = value)
);

async function submit() {
    saving.value = true;
    const invoice = await copyInvoice(props.invoiceId, { reference: reference.value });
    saving.value = false;
    show.value = false;
    if (invoice) {
        router.visit(route('invoices.show', invoice.id));
    }
}

const referenceInput = ref<HTMLInputElement | null>(null);
useFocus(referenceInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title> Copy Invoice </template>

        <template #content>
            <Field>
                <FieldLabel for="copyReference">New Reference</FieldLabel>
                <TextInput
                    id="copyReference"
                    ref="referenceInput"
                    v-model="reference"
                    type="text"
                    class="block w-full"
                    required
                    @keydown.enter="submit" />
            </Field>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Copy Invoice
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
