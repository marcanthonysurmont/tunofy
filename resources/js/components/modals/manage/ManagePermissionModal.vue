<template>
    <CreateModalDefault
        :is-visible="isVisible"
        @close-modal="
            emits('closeModal');
            resetState();
        "
    >
        <template #title>
            <h1 class="text-4xl">Manage permission</h1>
        </template>
        <template #body>
            <div class="mb-6">
                <RadioGroup
                    v-model="selectedMethod"
                    name="permissions-method"
                    legend="Permissions"
                    :description="`Select the permission level for ${user.name}.`"
                    :options="notificationMethods"
                />
            </div>
        </template>
        <template #footer>
            <RegularButton
                color="blue"
                class="w-full"
                @click="changePermission"
                :loading="form.processing"
            >
                Save Changes
            </RegularButton>
        </template>
    </CreateModalDefault>
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import RadioGroup from "@/components/forms/RadioGroup.vue";

const props = defineProps({
    isVisible: {
        type: Boolean,
        required: true,
        default: false,
    },
    user: {
        type: Object,
        default: () => ({}),
    },
    mix: {
        type: Object,
        default: () => ({}),
    },
});

const notificationMethods = [
    { id: "viewer", title: "Viewer" },
    { id: "contributor", title: "Contributor" },
    { id: "editor", title: "Editor" },
];

const selectedMethod = ref("viewer");
watch(
    () => props.user,
    (newVal) => {
        if (newVal) {
            console.log(newVal);
            selectedMethod.value = props.user.role.toLowerCase();
        }
    }
);

const form = useForm({
    user_id: null,
    role: null,
});

function changePermission() {
    form.user_id = props.user.id;
    form.role = selectedMethod.value;
    form.post(route("mix.change-permission", props.mix.id), {
        onSuccess: () => {
            emits("closeModal");
            resetState();
        },
        onError: (errors) => {
            console.error(errors);
        },
    });
}

const emits = defineEmits(["closeModal"]);

function resetState() {
    form.reset();
    form.clearErrors();
    selectedMethod.value = "viewer";
}
</script>
