<template>
    <div
        class="fixed inset-0 -z-10 h-screen w-screen overflow-hidden pointer-events-none"
        style="
            background-image: url('/images/decorations/confetti3.png');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            margin: 0;
            padding: 0;
        "
    ></div>
    <div
        class="fixed inset-0 -z-9 h-screen w-screen bg-background-page opacity-85 pointer-events-none"
    ></div>
    <FallingConfetti :theme-settings="themeSettings" />
    <Fireworks :theme-settings="themeSettings" />
</template>

<script setup>
import FallingConfetti from "@/components/themes/party/FallingConfetti.vue";
import Fireworks from "@/components/themes/party/Fireworks.vue";

const props = defineProps({
    themeSettings: {
        type: Object,
        required: true,
    },
});
</script>
