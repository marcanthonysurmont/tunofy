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
            <template v-if="currentThemeSettings && Object.keys(currentThemeSettings).length > 0">
                <div v-for="(value, key) in currentThemeSettings" :key="key" class="mb-6">
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

    <BirthdayTheme
        v-if="currentTheme.name === 'Party'"
        :theme-settings="currentTheme.settings"
    />
</template>

<script setup>
import CreateModalDefault from "@/components/modals/CreateModalDefault.vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import ListboxStrings from "@/components/forms/ListboxStrings.vue";
import Checkbox from "@/components/forms/Checkbox.vue";
import ToggleSwitchDescription from "@/components/forms/ToggleSwitchDescription.vue";
import { ref, computed, watch, onMounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import BirthdayTheme from "@/components/themes/BirthdayTheme.vue";

const props = defineProps({
    isVisible: Boolean,
});

const page = usePage();
const mix = computed(() => page.props.mix);
const themes = computed(() => page.props.themes || []);

// Extract just the theme names for the dropdown
const themeNames = computed(() => {
    return themes.value.map(theme => theme.name);
});

// Form state
const selectedTheme = ref(null);
const themeFormSettings = ref({});
const isLoading = ref(false);

// Get settings for currently selected theme
const currentThemeSettings = computed(() => {
    if (!selectedTheme.value) return {};
    
    const theme = themes.value.find(theme => theme.name === selectedTheme.value);
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
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

// Initialize the selected theme and settings when the modal opens
watch(() => props.isVisible, (isVisible) => {
    if (isVisible && themes.value.length > 0) {
        // Find active theme or use first theme
        const activeTheme = themes.value.find(theme => theme.is_active) || themes.value[0];
        
        if (activeTheme) {
            // Set the selected theme name
            selectedTheme.value = activeTheme.name;
            
            // Initialize form settings
            if (activeTheme.settings && typeof activeTheme.settings === 'object' && !Array.isArray(activeTheme.settings)) {
                themeFormSettings.value = { ...activeTheme.settings };
            } else {
                themeFormSettings.value = {};
            }
        }
    }
}, { immediate: true });

// Update form settings when selected theme changes
watch(() => selectedTheme.value, (newTheme) => {
    if (newTheme) {
        const theme = themes.value.find(theme => theme.name === newTheme);
        if (theme && theme.settings && typeof theme.settings === 'object' && !Array.isArray(theme.settings)) {
            themeFormSettings.value = { ...theme.settings };
        } else {
            themeFormSettings.value = {};
        }
    }
});

// Emit event to close modal
const emits = defineEmits(["closeModal"]);

function updateTheme() {
    if (isLoading.value) return;
    isLoading.value = true;
    
    // Find the theme ID based on the selected name
    const selectedThemeObj = themes.value.find(theme => theme.name === selectedTheme.value);
    
    if (!selectedThemeObj) {
        isLoading.value = false;
        return;
    }
    
    // Send update request with dynamic settings
    router.post(route('mix.updateTheme', mix.value.id), {
        theme_setting_definition_id: selectedThemeObj.id,
        settings: themeFormSettings.value
    }, {
        onSuccess: () => {
            isLoading.value = false;
            emits('closeModal');
        },
        onError: () => {
            isLoading.value = false;
        }
    });
}

const currentTheme = computed(() => {
    return page.props.themes.find(theme => theme.is_active === true);
});
</script>
