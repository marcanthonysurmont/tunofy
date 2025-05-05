<template>
    <h1 class="text-4xl sm:text-5xl font-medium mb-2">Settings</h1>
    <p class="mb-8" v-if="isCustomTemplate">
        Tailor the settings of your mix to your needs.
    </p>
    <p class="mb-8" v-else-if="!isCustomTemplate">
        The settings below are of
        <strong>{{ templatesStore.getSelectedTemplate().name }}</strong>
        template. These are read-only and cannot be changed.
    </p>
    <div
        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-4 mb-8 md:max-w-7xl"
    >
        <div
            v-for="option in options"
            class="mb-8 sm:mb-0 px-4 py-6 bg-card-background border-2 border-card-stroke rounded-lg transition-all duration-200 ease-in-out"
            :class="isCustomTemplate ? '' : 'opacity-65'"
        >
            <h2
                class="text-2xl mb-4 font-body font-semibold pt-0 transition-all duration-200 ease-in-out"
                :class="isCustomTemplate ? 'text-white' : 'opacity-40'"
            >
                {{ option.name }}
            </h2>
            <div v-if="option.name === 'Voting'" class="flex flex-col gap-6">
                <p class="text-zinc-400">Batch size input range slider here</p>
                <ToggleSwitchDescription
                    :label="'Voting'"
                    v-model="settingsForm.voting_enabled"
                    :disabled="!isCustomTemplate"
                />
                <p class="text-zinc-400">
                    Kill percentage input range slider here
                </p>
                <ToggleSwitchDescription
                    :label="'Requires approval'"
                    v-model="settingsForm.requires_approval"
                    :disabled="!isCustomTemplate"
                />
            </div>
            <div v-else-if="option.name === 'Mix'" class="flex flex-col gap-6">
                <InputFieldAdvanced
                    v-model="settingsForm.max_songs"
                    label="Maximum number of songs"
                    id="max_songs"
                    name="max_songs"
                    type="number"
                    placeholder="6"
                    inputClass="w-max-xs w-full"
                    :error="settingsForm.errors.max_songs"
                    :disabled="!isCustomTemplate"
                />
                <InputFieldAdvanced
                    v-model="settingsForm.num_rounds"
                    label="Number of rounds"
                    id="number_of_rounds"
                    name="number_of_rounds"
                    type="number"
                    placeholder="12"
                    inputClass="w-max-xs w-full"
                    :error="settingsForm.errors.num_rounds"
                    :disabled="!isCustomTemplate"
                />
                <ToggleSwitchDescription
                    :label="'Priority boost'"
                    v-model="settingsForm.priority_boost_new"
                    :disabled="!isCustomTemplate"
                />
                <ToggleSwitchDescription
                    :label="'Auto remove'"
                    v-model="settingsForm.auto_remove_negative"
                    :disabled="!isCustomTemplate"
                />
            </div>
            <div v-else-if="option.name === 'Chat'" class="flex flex-col gap-6">
                <ToggleSwitchDescription
                    :label="'Emoji chat'"
                    v-model="settingsForm.emoji_chat_enabled"
                    :disabled="!isCustomTemplate"
                />
            </div>
        </div>
    </div>
    <RegularButton
        color="blue"
        @click="saveChanges"
        :loading="isLoading"
        v-if="isCustomTemplate"
    >
        Save changes
    </RegularButton>
</template>

<script setup>
import ToggleSwitchDescription from "@/components/forms/ToggleSwitchDescription.vue";
import InputFieldAdvanced from "@/components/forms/InputFieldAdvanced.vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import { useTemplatesStore } from "@/stores/StorePresets.js";

const templatesStore = useTemplatesStore();

const page = usePage();
const mixId = computed(() => page.props.mix.id);
const presetId = computed(() => page.props.mix.presets[0].id);

const settingsForm = useForm({
    mix_id: mixId.value,
    batch_size: templatesStore.batch_size,
    max_songs: templatesStore.max_songs,
    num_rounds: templatesStore.num_rounds,
    requires_approval: templatesStore.requires_approval,
    voting_enabled: templatesStore.voting_enabled,
    kill_percentage_percent: templatesStore.kill_percentage_percent,
    priority_boost_new: templatesStore.priority_boost_new,
    auto_remove_negative: templatesStore.auto_remove_negative,
    emoji_chat_enabled: templatesStore.emoji_chat_enabled,
});

const isCustomTemplate = computed(() => {
    return templatesStore.getSelectedTemplate().name === "Custom";
});
const isLoading = ref(false);

//watch for template changes and update form data
watch(
    () => templatesStore.selectedTemplateIndex,
    (newIndex) => {
        const selectedTemplate = templatesStore.getSelectedTemplate();

        //bind form data based on selected template.
        settingsForm.batch_size = selectedTemplate.batch_size;
        settingsForm.max_songs = selectedTemplate.max_songs;
        settingsForm.num_rounds = selectedTemplate.num_rounds;
        settingsForm.requires_approval = selectedTemplate.requires_approval;
        settingsForm.kill_percentage_percent =
            selectedTemplate.kill_percentage_percent;
        settingsForm.priority_boost_new = selectedTemplate.priority_boost_new;
        settingsForm.auto_remove_negative =
            selectedTemplate.auto_remove_negative;
        settingsForm.emoji_chat_enabled = selectedTemplate.emoji_chat_enabled;
        settingsForm.voting_enabled = selectedTemplate.voting_enabled;
    },
    { immediate: true }
);
watch(
    () => settingsForm.voting_enabled,
    (newValue) => {
        console.log(newValue);
    }
);
watch(
    () => settingsForm.auto_remove_negative,
    (newValue) => {
        console.log(newValue);
    }
);

function saveChanges() {
    if (isLoading.value) {
        return;
    }
    isLoading.value = true;

    console.log(settingsForm);
    settingsForm.post(route("mix.presets.update", presetId.value), {
        onSuccess: () => {
            isLoading.value = false;
        },
        onFinish: () => {
            isLoading.value = false;
        },
        onError: (error) => {
            console.log(error);
            isLoading.value = false;
        },
    });
}

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
