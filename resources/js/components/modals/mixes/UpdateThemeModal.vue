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
                    :options="themeNames"
                />
            </div>

            <!-- Dynamic settings based on selected theme -->
            <template
                v-if="
                    currentThemeSettings &&
                    Object.keys(currentThemeSettings).length > 0
                "
            >
                <div
                    v-for="(value, key) in currentThemeSettings"
                    :key="key"
                    class="mb-6"
                >
                    <ToggleSwitchDescription
                        :label="formatSettingName(key)"
                        v-model="themeFormSettings[key]"
                        :disabled="key === 'background_enabled'"
                    />
                </div>
            </template>
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
import ToggleSwitchDescription from "@/components/forms/ToggleSwitchDescription.vue";
import { ref, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";

const props = defineProps({
    isVisible: Boolean,
});

const page = usePage();
const mix = computed(() => page.props.mix);
const themes = computed(() => page.props.themes || []);

// Extract just the theme names for the dropdown
const themeNames = computed(() => {
    return themes.value.map((theme) => theme.name);
});

// Form state
const selectedTheme = ref(null);
const themeFormSettings = ref({});
const isLoading = ref(false);

// Get settings for currently selected theme
const currentThemeSettings = computed(() => {
    if (!selectedTheme.value) return {};

    const theme = themes.value.find(
        (theme) => theme.name === selectedTheme.value
    );
    if (!theme) return {};

    // Handle case where settings is an array or null
    if (!theme.settings || Array.isArray(theme.settings)) {
        return {};
    }

    return theme.settings;
});

// Format setting names for display (e.g., "background_enabled" -> "Background enabled")
function formatSettingName(key) {
    return key
        .split("_")
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(" ");
}

// Initialize the selected theme and settings when the modal opens
watch(
    () => props.isVisible,
    (isVisible) => {
        if (isVisible && themes.value.length > 0) {
            // Find active theme or use first theme
            const activeTheme =
                themes.value.find((theme) => theme.is_active) ||
                themes.value[0];

            if (activeTheme) {
                // Set the selected theme name
                selectedTheme.value = activeTheme.name;

                // Initialize form settings
                if (
                    activeTheme.settings &&
                    typeof activeTheme.settings === "object" &&
                    !Array.isArray(activeTheme.settings)
                ) {
                    themeFormSettings.value = { ...activeTheme.settings };
                } else {
                    themeFormSettings.value = {};
                }
            }
        }
    },
    { immediate: true }
);

// Update form settings when selected theme changes
watch(
    () => selectedTheme.value,
    (newTheme) => {
        if (newTheme) {
            const theme = themes.value.find((theme) => theme.name === newTheme);
            if (
                theme &&
                theme.settings &&
                typeof theme.settings === "object" &&
                !Array.isArray(theme.settings)
            ) {
                themeFormSettings.value = { ...theme.settings };
            } else {
                themeFormSettings.value = {};
            }
        }
    }
);

// Emit event to close modal
const emits = defineEmits(["closeModal"]);

// Update your function to handle both boolean and 0/1 values
function updateTheme() {
    if (isLoading.value) return;
    isLoading.value = true;

    // Find the theme ID based on the selected name
    const selectedThemeObj = themes.value.find(
        (theme) => theme.name === selectedTheme.value
    );

    if (!selectedThemeObj) {
        isLoading.value = false;
        return;
    }

    // Convert form settings to ensure boolean values are sent as true/false
    const normalizedSettings = {};

    for (const key in themeFormSettings.value) {
        // Convert numeric 0/1 to boolean true/false
        const value = themeFormSettings.value[key];
        normalizedSettings[key] =
            typeof value === "number" ? Boolean(value) : value;
    }

    console.log("Sending settings:", normalizedSettings);

    // Send update request with normalized settings
    router.post(
        route("mix.update-theme", mix.value.id),
        {
            theme_setting_definition_id: selectedThemeObj.id,
            settings: normalizedSettings,
        },
        {
            onSuccess: () => {
                isLoading.value = false;
                emits("closeModal");
            },
            onError: (error) => {
                console.error("Error updating theme:", error);
                isLoading.value = false;
            },
        }
    );
}

const currentTheme = computed(() => {
    return page.props.themes.find((theme) => theme.is_active === true);
});
</script>
