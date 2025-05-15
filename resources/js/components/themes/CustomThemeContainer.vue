<template>
</template>

<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import BirthdayTheme from "@/components/themes/BirthdayTheme.vue";

const page = usePage();
const currentTheme = computed(() => {
    return page.props.themes.find(theme => theme.is_active === true);
});
</script>
