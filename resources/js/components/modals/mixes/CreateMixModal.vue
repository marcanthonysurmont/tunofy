<template>
    <CreateModalDefault
        :is-visible="isVisible"
        @close-modal="
            emits('closeModal');
            form.reset();
            form.clearErrors();
        "
        @submit-from-enter="storeMix"
    >
        <template #title>
            <h1 class="text-4xl">Add an Admin</h1>
        </template>
        <template #body>
            <div class="mb-6">
                <InputField
                    v-model="form.name"
                    label="Name"
                    id="name"
                    name="name"
                    type="name"
                    placeholder="Houseparty Mix 2025"
                    :error="form.errors.name"
                />
            </div>
            <div class="mb-6">
                <Checkbox
                    v-model="form.is_public"
                    label="Public Mix"
                    id="public-mix"
                    :error="form.errors.is_public"
                />
            </div>
            <div class="mb-6">
                <ImageUpload
                    v-model="form.image"
                    label="Cover Photo"
                    id="cover-photo"
                    :error="form.errors.image"
                    helperText="PNG, JPG up to 5MB"
                    :maxSizeInMB="5"
                />
            </div>
        </template>
        <template #footer>
            <RegularButton
                color="blue"
                class="w-full"
                @click="storeMix"
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
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Checkbox from "@/components/forms/Checkbox.vue";
import ImageUpload from "@/components/forms/ImageUpload.vue";

defineProps({
    isVisible: Boolean,
});

const form = useForm({
    name: null,
    is_public: false,
    image: null,
});

//emit to close modal
const emits = defineEmits(["closeModal"]);

const isLoading = ref(false);

function storeMix() {
    //prevent spam clicks while loading
    if (isLoading.value) {
        return;
    }

    isLoading.value = true;
    form.post(route("mix.store"), {
        onFinish: () => {
            isLoading.value = false;
        },
        onSuccess: () => {
            emits("closeModal");
            form.reset();
            form.clearErrors();
        },
        onError: (errors) => {
            console.log(errors);
        },
    });
}
</script>
