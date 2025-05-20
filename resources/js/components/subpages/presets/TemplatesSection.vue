<template>
    <h1 class="text-3xl sm:text-4xl font-medium mb-6">Quick Templates</h1>
    <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4 mb-12"
    >
        <div
            class="relative bg-card-background border-2 p-4 rounded-lg cursor-pointer transition-all duration-200"
            :class="
                templatesStore.selectedTemplateIndex === index
                    ? 'border-blue-500 shadow-lg'
                    : 'border-card-stroke'
            "
            v-for="(template, index) in templatesStore.templates"
            :key="index"
            @click="selectTemplate(index, template.id)"
        >
            <CheckCircleIcon
                v-if="templatesStore.selectedTemplateIndex === index"
                class="absolute top-2 right-2 w-6 h-6 text-blue-500"
            />
            <h2 class="text-2xl font-semibold font-body">
                {{ template.name }}
            </h2>
            <p class="text-muted">{{ template.description }}</p>
        </div>
    </div>
</template>

<script setup>
import { CheckCircleIcon } from "@heroicons/vue/24/solid";
import { useTemplatesStore } from "@/stores/StorePresets.js";
import { useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const mix = computed(() => page.props.mix);
const authorization = computed(() => page.props.mix.authorized);

const templatesStore = useTemplatesStore();

const selectPresetForm = useForm({
    preset_id: templatesStore.selectedTemplateID,
});

function selectTemplate(index, id) {
    //if user is not owner, then do not allow to select template
    if (authorization.value.isOwner === false) {
        return;
    }

    //save in store
    templatesStore.selectTemplate(index, id);

    //save in form
    selectPresetForm.preset_id = id;

    //submit form
    selectPresetForm.post(route("mix.presets.update-selected", mix.value.id), {
        onError: (error) => {
            console.error("Error updating selected preset:", error);
        },
    });
}
</script>
