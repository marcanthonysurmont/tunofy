<template>
    <CreateModalDefault
        :is-visible="isVisible"
        @close-modal="
            emits('closeModal');
            resetState();
        "
        @submit-from-enter="generateCode"
    >
        <template #title>
            <h1 class="text-4xl" v-if="!codeGenerated">Generate Code</h1>
        </template>
        <template #body>
            <div v-if="!codeGenerated">
                <div class="mb-6">
                    <RadioGroup
                        v-model="selectedMethod"
                        name="permissions-method"
                        legend="Permissions"
                        description="What permissions do users that join this mix get?"
                        :options="notificationMethods"
                    />
                </div>
            </div>
            <div v-else class="flex flex-col items-center">
                <div class="mb-4 text-center">
                    <h1 class="text-4xl mb-8">Your session code is ready!</h1>
                    <div
                        class="border-2 border-card-stroke rounded-lg p-6 text-center relative"
                    >
                        <p class="text-2xl font-semibold">{{ sessionCode }}</p>
                        <ClipboardDocumentIcon
                            v-if="!copied"
                            @click="copyToClipboard"
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 size-9 text-dark-white cursor-pointer p-1"
                        />

                        <CheckIcon
                            v-if="copied"
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 size-9 text-green-600 p-1"
                        />
                    </div>
                </div>
                <p class="text-sm text-zinc-200 mb-4 text-center">
                    Share this code with others to give them
                    <span class="font-bold">{{ selectedMethod }}</span> access
                    to your mix.
                </p>
            </div>
        </template>
        <template #footer>
            <RegularButton
                v-if="!codeGenerated"
                color="blue"
                class="w-full"
                @click="generateCode"
                :loading="isLoading"
            >
                Generate Code
            </RegularButton>
            <div v-else class="flex gap-4 w-full">
                <RegularButton
                    color="blue"
                    class="w-1/2"
                    type="primary"
                    @click="resetState"
                >
                    New Code
                </RegularButton>
                <RegularButton
                    class="w-1/2"
                    type="secondary"
                    @click="
                        emits('closeModal');
                        resetState();
                    "
                >
                    Close
                </RegularButton>
            </div>
        </template>
    </CreateModalDefault>
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import { ref, computed } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import RadioGroup from "@/components/forms/RadioGroup.vue";
import { ClipboardDocumentIcon, CheckIcon } from "@heroicons/vue/24/outline";

const notificationMethods = [
    { id: "view", title: "View" },
    { id: "contribute", title: "Contribute" },
    { id: "edit", title: "Edit" },
];

const selectedMethod = ref("view");
const page = usePage();
const mix = computed(() => page.props.mix);
const codeGenerated = ref(false);
const sessionCode = ref(mix.value.session_code);
const copied = ref(false);

defineProps({
    isVisible: Boolean,
});

const form = useForm({
    session_code_permission: selectedMethod.value,
});

const emits = defineEmits(["closeModal"]);

const isLoading = ref(false);

function resetState() {
    sessionCode.value = "";
    form.reset();
    form.clearErrors();
    codeGenerated.value = false;
    selectedMethod.value = "view";
}

function generateCode() {
    //prevent spam clicking while loading
    if (isLoading.value) {
        return;
    }

    copied.value = false;

    isLoading.value = true;
    form.session_code_permission = selectedMethod.value;

    form.post(route("mix.generate-code", mix.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onSuccess: (response) => {
            codeGenerated.value = true;
            sessionCode.value =
                response?.props?.mix?.session_code || "Code not found";
        },
        onError: (errors) => {
            console.log(errors);
        },
    });
}
function copyToClipboard() {
    navigator.clipboard.writeText(sessionCode.value).then(() => {
        //set copied to true to show the checkmark
        copied.value = true;

        //after 5s enable copying again by going back to false
        setTimeout(() => {
            copied.value = false;
        }, 5000);
    });
}
</script>
