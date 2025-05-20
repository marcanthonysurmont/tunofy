<template>
    <CreateModalDefault
        :is-visible="isVisible"
        @close-modal="emits('closeModal')"
        @submit-from-enter="deleteAccount"
    >
        <template #title>
            <h1 class="text-3xl">Are you sure?</h1>
            <p class="text-muted">
                Once your account is deleted you will not be able to recover it.
                All your data will be wiped. Type <strong>"CONFIRM"</strong> to
                confirm you want to delete your account.
            </p>
        </template>
        <template #body>
            <div class="mb-6">
                <InputField
                    v-model="userInput.deleteConfirmation"
                    :error="userInput.error"
                />
            </div>
        </template>
        <template #footer>
            <div class="flex flex-row justify-end gap-2">
                <RegularButton
                    type="secondary"
                    @click="emits('closeModal')"
                    :disabled="isLoading"
                    >Cancel
                </RegularButton>
                <RegularButton
                    type="danger"
                    @click="deleteAccount"
                    :loading="isLoading"
                    >Delete account
                </RegularButton>
            </div>
        </template>
    </CreateModalDefault>
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import InputField from "@/components/forms/InputField.vue";
import { ref } from "vue";

defineProps({
    isVisible: Boolean,
});

const userInput = ref({
    deleteConfirmation: "",
    error: "",
});

//emit to close modal
const emits = defineEmits(["closeModal"]);

const isLoading = ref(false);

function deleteAccount() {
    userInput.value.error = "";
    if (userInput.value.deleteConfirmation !== "CONFIRM") {
        userInput.value.error = "Please type CONFIRM to confirm";
        return;
    }
    isLoading.value = true;
}
</script>
