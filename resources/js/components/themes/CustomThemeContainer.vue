<template>
    <BirthdayTheme v-if="enableBirthdayTheme" />
</template>

<script setup>
import { ref } from "vue";
import BirthdayTheme from "@/components/themes/BirthdayTheme.vue";
const enableBirthdayTheme = ref(false);
</script>
