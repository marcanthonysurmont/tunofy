<template>
    <h1 class="text-4xl sm:text-5xl font-medium mb-2">Settings</h1>
    <p class="mb-8">Tailor the settings of your mix to your needs.</p>
    <div
        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 mb-8 md:max-w-7xl"
    >
        <div
            v-for="option in options"
            class="mb-8 sm:mb-0 px-4 py-6 bg-card-background border-2 border-card-stroke rounded-lg"
        >
            <h2 class="text-2xl mb-4 font-body font-semibold pt-0">
                {{ option.name }}
            </h2>
            <div v-if="option.name === 'Voting'" class="flex flex-col gap-6">
                <p class="text-zinc-400">Batch size input range slider here</p>
                <ToggleSwitchDescription :label="'Voting disabled'" />
                <p class="text-zinc-400">
                    Kill percentage input range slider here
                </p>
                <ToggleSwitchDescription
                    :label="'Requires approval disabled'"
                />
            </div>
            <div v-else-if="option.name === 'Mix'" class="flex flex-col gap-6">
                <InputFieldAdvanced
                    v-model="selectedNumberOfRoundsForm.number_of_rounds"
                    label="Maximum number of songs"
                    id="max_songs"
                    name="max_songs"
                    type="number"
                    placeholder="6"
                    inputClass="w-max-xs w-full"
                    :error="selectedNumberOfRoundsForm.errors.number_of_rounds"
                />
                <InputFieldAdvanced
                    v-model="selectedNumberOfRoundsForm.number_of_rounds"
                    label="Number of rounds"
                    id="number_of_rounds"
                    name="number_of_rounds"
                    type="number"
                    placeholder="12"
                    inputClass="w-max-xs w-full"
                    :error="selectedNumberOfRoundsForm.errors.number_of_rounds"
                />
                <ToggleSwitchDescription :label="'Priority boost disabled'" />
                <ToggleSwitchDescription :label="'Auto remove disabled'" />
            </div>
            <div v-else-if="option.name === 'Chat'" class="flex flex-col gap-6">
                <ToggleSwitchDescription :label="'Emoji chat disabled'" />
            </div>
        </div>
    </div>
</template>

<script setup>
import ToggleSwitchDescription from "@/components/forms/ToggleSwitchDescription.vue";
import InputFieldAdvanced from "@/components/forms/InputFieldAdvanced.vue";
import { useForm } from "@inertiajs/vue3";

const selectedNumberOfRoundsForm = useForm({
    number_of_rounds: 1,
});

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
