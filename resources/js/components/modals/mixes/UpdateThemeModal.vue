<template>
    <CreateModalDefault
        :is-visible="isVisible"
        @close-modal="emits('closeModal')"
        @submit-from-enter=""
    >
        <template #title>
            <h1 class="text-4xl">Update theme</h1>
        </template>
        <template #body>
            <div class="mb-6">
                <ListboxStrings
                    v-model="selectedTheme"
                    label="Theme"
                    placeholderText="Select a theme"
                    :options="['Birthday', 'Halloween', 'Christmas']"
                />
            </div>
            <div class="mb-6">
                <ToggleSwitchDescription
                    :label="'Background image'"
                    :disabled="true"
                    v-model="hasSelectedBackgroundImage"
                />
            </div>
            <div class="mb-6">
                <ToggleSwitchDescription
                    :label="'Falling confetti'"
                    v-model="hasSelectedConfetti"
                />
            </div>
            <div class="mb-6">
                <ToggleSwitchDescription
                    :label="'Random fireworks'"
                    v-model="hasSelectedFireworks"
                />
            </div>
        </template>
        <template #footer>
            <RegularButton
                color="blue"
                class="w-full"
                @click="updateTheme"
                :loading="isLoading"
                >Save changes
            </RegularButton>
        </template>
    </CreateModalDefault>
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import ListboxStrings from "@/components/forms/ListboxStrings.vue";
import Checkbox from "@/components/forms/Checkbox.vue";
import ToggleSwitchDescription from "@/components/forms/ToggleSwitchDescription.vue";
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
    isVisible: Boolean,
});

const page = usePage();
const mix = computed(() => page.props.mix);

const selectedTheme = ref(null);
const hasSelectedBackgroundImage = ref(true);
const hasSelectedFireworks = ref(false);
const hasSelectedConfetti = ref(false);

//emit to close modal
const emits = defineEmits(["closeModal"]);

const isLoading = ref(false);

function updateTheme() {
    //prevent spam clicks while loading
    if (isLoading.value) {
        return;
    }

    isLoading.value = true;
    //
}
</script>
