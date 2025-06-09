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
import axios from "axios";

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

async function joinMix() {
    //if loading = skip
    if (isLoading.value) {
        return;
    }

    //standard frontend checking if input is empty
    if (inputCode.value === "") {
        errorMessage.value = "Please enter a code";
        return;
    }

    //set loading flag
    isLoading.value = true;

    try {
        //make api call to backend to join mix
        const response = await axios.get(route("mix.join", inputCode.value));

        //if response is successful and has a redirect, navigate to that route
        if (response.data.success && response.data.redirect) {
            router.visit(response.data.redirect);
            return;
        }
        //else
        resetAndCloseModal();
    } catch (error) {
        if (error.response) {
            const status = error.response.status;
            const data = error.response.data;

            // console.log("Error status:", status);
            // console.log("Error data:", data);

            //validation errors handling in frontend
            if (status === 422 && data.errors) {
                errorMessage.value = data.errors.code || "Validation error";
            } else if (status === 409) {
                errorMessage.value = data.message || "Conflict error";
            } else {
                errorMessage.value =
                    "An unexpected error occurred. Please try again.";
            }
        } else {
            errorMessage.value = "Network error. Please check your connection.";
        }
    } finally {
        isLoading.value = false;
    }
}

function resetAndCloseModal() {
    inputCode.value = "";
    errorMessage.value = "";
    emits("closeModal");
}
</script>
