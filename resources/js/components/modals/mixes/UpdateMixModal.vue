<template>
    <CreateModalDefault
        :is-visible="isVisible"
        @close-modal="
            emits('closeModal');
            form.reset();
            form.clearErrors();
        "
        @submit-from-enter="updateMix"
    >
        <template #title>
            <h1 class="text-4xl">Update mix</h1>
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
            <!-- <div class="mb-6">
                <Checkbox
                    v-model="form.is_public"
                    label="Public Mix"
                    id="public-mix"
                    :error="form.errors.is_public"
                />
            </div> -->
            <div class="mb-6">
                <ImageUpload
                    v-model="form.avatar"
                    label="Cover Photo (optional)"
                    id="cover-photo"
                    :error="form.errors.avatar"
                    helperText="PNG, JPG up to 5MB"
                    :maxSizeInMB="2"
                />
            </div>
        </template>
        <template #footer>
            <RegularButton
                color="blue"
                class="w-full"
                @click="updateMix"
                :loading="isLoading"
                >Save changes
            </RegularButton>
        </template>
    </CreateModalDefault>
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import InputField from "@/components/forms/InputField.vue";
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Checkbox from "@/components/forms/Checkbox.vue";
import ImageUpload from "@/components/forms/ImageUpload.vue";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
    isVisible: Boolean,
});

const page = usePage();
const mix = computed(() => page.props.mix);

const form = useForm({
    name: null,
    is_public: null,
    avatar: null,
});

watch(
    () => props.isVisible,
    (visible) => {
        if (visible) {
            form.name = mix.value.name;
            form.is_public = mix.value.is_public;
            form.avatar = null;
        }
    }
);

//emit to close modal
const emits = defineEmits(["closeModal"]);

const isLoading = ref(false);

function updateMix() {
    //prevent spam clicks while loading
    if (isLoading.value) {
        return;
    }

    isLoading.value = true;

    form.post(route("mix.update", mix.value.id), {
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
