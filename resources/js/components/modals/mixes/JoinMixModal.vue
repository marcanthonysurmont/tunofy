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
                >Add new mix
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
    router.post(
        route("mix.join", inputCode.value),
        {},
        {
            onFinish: () => {
                isLoading.value = false;
            },
            onSuccess: () => {
                resetAndCloseModal();
            },
            onError: (errors) => {
                errorMessage.value = errors.code;
            },
        }
    );
}

function resetAndCloseModal() {
    inputCode.value = "";
    errorMessage.value = "";
    emits("closeModal");
}
</script>
