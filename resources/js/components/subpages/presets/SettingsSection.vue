<template>
    <h1 class="text-4xl sm:text-5xl font-medium mb-2">Settings</h1>
    <p class="mb-8">Tailor the settings of your mix to your needs.</p>
    <div
        class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-3 gap-4 mb-8 justify-between"
    >
        <div v-for="option in options" class="mb-8 sm:mb-0">
            <h2 class="text-2xl mb-4">
                {{ option.name }}
            </h2>
            <div v-if="option.name === 'Voting'">
                <ToggleSwitchDescription :label="'Enable Voting'" />
            </div>
            <div v-else-if="option.name === 'Mix'">
                <ToggleSwitchDescription :label="'Enable Mix'" />
            </div>
        </div>
    </div>
</template>

<script setup>
import ToggleSwitchDescription from "@/components/forms/ToggleSwitchDescription.vue";

const options = [
    {
        name: "Voting",
    },
    {
        name: "Mix",
    },
    {
        name: "Chat",
    },
];
</script>
