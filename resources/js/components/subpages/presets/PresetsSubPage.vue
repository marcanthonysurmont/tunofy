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

watch(
    presets,
    (newPresets) => {
        templatesStore.templates = newPresets;
    },
    { immediate: true }
);
</script>
