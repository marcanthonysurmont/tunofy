<template>
    <PartyTheme
        v-if="currentTheme.name === 'Party'"
        :theme-settings="currentTheme.settings"
    />
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import PartyTheme from "@/components/themes/party/PartyTheme.vue";

const page = usePage();
const currentTheme = computed(() => {
    return page.props.themes.find((theme) => theme.is_active === true);
});
</script>
