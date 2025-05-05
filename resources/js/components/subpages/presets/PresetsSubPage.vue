<template>
    <TemplatesSection />
    <SettingsSection />
</template>

<script setup>
import TemplatesSection from "@/components/subpages/presets/TemplatesSection.vue";
import SettingsSection from "@/components/subpages/presets/SettingsSection.vue";
import { onBeforeMount, computed, watch } from "vue";
import { useTemplatesStore } from "@/stores/StorePresets.js";
import { usePage } from "@inertiajs/vue3";

const templatesStore = useTemplatesStore();

const page = usePage();
const presets = computed(() => page.props.presets);
const mix = computed(() => page.props.mix);
const activePresetId = computed(() => mix.value?.preset_id);

watch(
    presets,
    (newPresets) => {
        templatesStore.templates = newPresets;
    },
    { immediate: true }
);

watch(
    activePresetId,
    (newActivePresetId) => {
        if (newActivePresetId && templatesStore.templates?.length) {
            const activeIndex = templatesStore.templates.findIndex(
                (template) => template.id === newActivePresetId
            );

            templatesStore.selectedTemplateIndex =
                activeIndex >= 0 ? activeIndex : 3;
        }
    },
    { immediate: true }
);
</script>
