<template>
    <CreateModalDefault
        :is-visible="isVisible"
        @close-modal="resetAndCloseModal"
        @submit-from-enter="joinMix"
    >
        <template #title>
            <h1 class="text-4xl">Join a mix</h1>
        </template>
        <template #body>
            <div class="mb-6">
                <InputField
                    v-model="inputCode"
                    label="Code of mix"
                    id="code"
                    name="code"
                    type="text"
                    :error="errorMessage"
                    placeholder="XXXXX"
                />
            </div>
        </template>
        <template #footer>
            <RegularButton
                color="blue"
                class="w-full"
                @click="joinMix"
                :loading="isLoading"
                >Join mix
            </RegularButton>
        </template>
    </CreateModalDefault>
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import InputField from "@/components/forms/InputField.vue";
import { computed, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from 'axios';

defineProps({
    isVisible: Boolean,
});

//emit to close modal
const emits = defineEmits(["closeModal"]);

const page = usePage();
const props = computed(() => page.props);

const isLoading = ref(false);
const inputCode = ref("");
const errorMessage = ref("");
watch(
    () => props.value.non_toast_danger,
    (newVal) => {
        if (newVal) {
            errorMessage.value = newVal;
        }
    }
);

function joinMix() {
    //prevent spam clicks while loading
    if (isLoading.value) {
        return;
    }

    if (inputCode.value === "") {
        errorMessage.value = "Please enter a code";
        return;
    }
    isLoading.value = true;
    
    // Use Axios directly instead of Inertia router for better error handling
    axios.get(route("mix.join", inputCode.value))
        .then(response => {
            // For success responses with clean Inertia navigation
            if (response.data.success && response.data.redirect) {
                // Use Inertia router instead of direct page navigation
                router.visit(response.data.redirect);
            } else {
                resetAndCloseModal();
            }
        })
        .catch(error => {
            if (error.response) {
                console.log("Error status:", error.response.status);
                console.log("Error data:", error.response.data);
                
                // Handle 422 validation errors
                if (error.response.status === 422 && error.response.data.errors) {
                    errorMessage.value = error.response.data.errors.code || "Validation error";
                } 
                // Handle 409 conflict errors
                else if (error.response.status === 409) {
                    errorMessage.value = error.response.data.message || "Conflict error";
                } 
                // Handle other errors
                else {
                    errorMessage.value = "An unexpected error occurred. Please try again.";
                }
            } else {
                errorMessage.value = "Network error. Please check your connection.";
            }
        })
        .finally(() => {
            isLoading.value = false;
        });
}

function resetAndCloseModal() {
    inputCode.value = "";
    errorMessage.value = "";
    emits("closeModal");
}
</script>
