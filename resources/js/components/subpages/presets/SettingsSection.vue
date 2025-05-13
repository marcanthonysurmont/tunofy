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
            :class="
                isCustomTemplate && authorization.isOwner ? '' : 'opacity-65'
            "
        >
            <h2
                class="text-2xl mb-4 font-body font-semibold pt-0 transition-all duration-200 ease-in-out"
                :class="
                    isCustomTemplate && authorization.isOwner
                        ? 'text-white'
                        : 'opacity-40'
                "
            >
                {{ option.name }}
            </h2>
            <div v-if="option.name === 'Voting'" class="flex flex-col gap-6">
                <InputRangeSlider
                    label="Batch size"
                    v-model="settingsForm.batch_size"
                    :min="1"
                    :max="50"
                    :step="1"
                    :disabled="
                        !isCustomTemplate || authorization.isOwner === false
                    "
                />
                <InputRangeSlider
                    label="Kill percentage"
                    v-model="settingsForm.kill_percentage"
                    :min="0"
                    :max="100"
                    :step="1"
                    :disabled="
                        !isCustomTemplate || authorization.isOwner === false
                    "
                />
                <ToggleSwitchDescription
                    :label="'Voting'"
                    v-model="settingsForm.voting_enabled"
                    :disabled="
                        !isCustomTemplate || authorization.isOwner === false
                    "
                />
                <ToggleSwitchDescription
                    :label="'Requires approval'"
                    v-model="settingsForm.requires_approval"
                    :disabled="
                        !isCustomTemplate || authorization.isOwner === false
                    "
                />
            </div>
            <div v-else-if="option.name === 'Mix'" class="flex flex-col gap-6">
                <!-- <InputFieldAdvanced
                    v-model="settingsForm.max_songs"
                    label="Maximum number of songs"
                    id="max_songs"
                    name="max_songs"
                    type="number"
                    placeholder="6"
                    inputClass="w-max-xs w-full"
                    :error="settingsForm.errors.max_songs"
                    :disabled="
                        !isCustomTemplate || authorization.isOwner === false
                    "
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
                    :disabled="
                        !isCustomTemplate || authorization.isOwner === false
                    "
                /> -->
                <ToggleSwitchDescription
                    :label="'Priority boost'"
                    v-model="settingsForm.priority_boost_new"
                    :disabled="
                        !isCustomTemplate || authorization.isOwner === false
                    "
                />
                <ToggleSwitchDescription
                    :label="'Auto remove'"
                    v-model="settingsForm.auto_remove_negative"
                    :disabled="
                        !isCustomTemplate || authorization.isOwner === false
                    "
                />
            </div>
            <div v-else-if="option.name === 'Chat'" class="flex flex-col gap-6">
                <ToggleSwitchDescription
                    :label="'Emoji chat'"
                    v-model="settingsForm.emoji_chat_enabled"
                    :disabled="
                        !isCustomTemplate || authorization.isOwner === false
                    "
                />
            </div>
        </div>
    </div>
    <RegularButton
        color="blue"
        @click="saveChanges"
        :loading="isLoading"
        v-if="isCustomTemplate && authorization.isOwner === true"
        class="w-full sm:w-auto"
    >
        Save changes
    </RegularButton>
</template>

<script setup>
import ToggleSwitchDescription from "@/components/forms/ToggleSwitchDescription.vue";
import InputFieldAdvanced from "@/components/forms/InputFieldAdvanced.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { computed, onBeforeMount, ref, watch } from "vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import { useTemplatesStore } from "@/stores/StorePresets.js";
import InputRangeSlider from "@/components/forms/InputRangeSlider.vue";

const templatesStore = useTemplatesStore();

const page = usePage();
const mixId = computed(() => page.props.mix.id);
const presetId = computed(() => page.props.mix.preset_id);
const authorization = computed(() => page.props.mix.authorized);

const settingsForm = useForm({
    mix_id: mixId.value,
    batch_size: null,
    requires_approval: null,
    voting_enabled: null,
    kill_percentage: null,
    priority_boost_new: null,
    auto_remove_negative: null,
    emoji_chat_enabled: null,
});

const isLoading = ref(false);

//watch for if user selects another template
watch(
    () => templatesStore.selectedTemplateIndex,
    (newIndex) => {
        //get the selected template
        const selectedTemplate = templatesStore.getSelectedTemplate();

        //if user selects a template, update the form data
        setFormValues(selectedTemplate);
    }
);

const isCustomTemplate = computed(() => {
    return templatesStore.getSelectedTemplate().name === "Custom";
});

function saveChanges() {
    //if user is not owner, then do not allow to save potential changes
    if (authorization.value.isOwner === false || isLoading.value) {
        return;
    }

    isLoading.value = true;

    settingsForm.post(route("mix.presets.update", presetId.value), {
        preserveScroll: true,
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

function setFormValues(selectedTemplate) {
    settingsForm.batch_size = selectedTemplate.batch_size;
    settingsForm.requires_approval = selectedTemplate.requires_approval;
    settingsForm.kill_percentage = selectedTemplate.kill_percentage;
    settingsForm.priority_boost_new = selectedTemplate.priority_boost_new;
    settingsForm.auto_remove_negative = selectedTemplate.auto_remove_negative;
    settingsForm.emoji_chat_enabled = selectedTemplate.emoji_chat_enabled;
    settingsForm.voting_enabled = selectedTemplate.voting_enabled;
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

onBeforeMount(() => {
    const selectedTemplate = templatesStore.getSelectedTemplate();
    setFormValues(selectedTemplate);
});
</script>
