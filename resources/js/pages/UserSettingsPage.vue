<template>
    <Head title="Tunofy | App" />
    <AppLayout>
        <Transition name="fade-with-slide" appear mode="out-in">
            <div class="flex flex-col gap-8 max-w-3xl">
                <section class="mb-8">
                    <div class="mb-8 py-6 border-b-2 border-card-stroke">
                        <h1 class="text-3xl sm:text-4xl font-medium">
                            Settings
                        </h1>
                        <p class="text-muted">
                            Manage your profile and account settings
                        </p>
                    </div>
                    <div class="mb-4">
                        <h2 class="text-xl sm:text-2xl">Delete account</h2>
                        <p class="text-muted">
                            Delete your account and all of its resources
                        </p>
                    </div>
                    <div
                        class="space-y-2 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
                    >
                        <div
                            class="relative space-y-1.5 text-red-600 dark:text-red-100"
                        >
                            <p class="font-medium text-lg">Warning</p>
                            <p class="text-sm">
                                Please proceed with caution, this cannot be
                                undone.
                            </p>
                        </div>
                        <RegularButton
                            type="danger"
                            external-class="mt-2"
                            @click="isDeleteAccountModalVisible = true"
                        >
                            Delete account
                        </RegularButton>
                    </div>
                </section>
                <section class="mb-8">
                    <div class="mb-4">
                        <h2 class="text-xl sm:text-2xl">Privacy</h2>
                        <p class="text-muted">Manage your privacy settings</p>
                    </div>
                    <div class="flex flex-col gap-4">
                        <ToggleSwitchDescription
                            tooltip="We collect data to create your yearly Tunofy Remix - our version of Spotify Wrapped. This also helps us improve our services and enhance your overall experience."
                            label="Data collection"
                            v-model="dataCollectionEnabled"
                        />
                        <ToggleSwitchDescription
                            tooltip="We use analytics to understand how our users interact with the app. This helps us improve our services and enhance your overall experience."
                            label="Analytics tracking"
                            v-model="analyticsTrackingEnabled"
                        />
                    </div>
                </section>
                <section class="mb-8">
                    <div class="mb-4">
                        <h2 class="text-xl sm:text-2xl">Accessibility</h2>
                        <p class="text-muted">
                            Manage accessibility settings for your account
                        </p>
                    </div>
                    <div class="flex flex-col gap-4">
                        <ToggleSwitchDescription
                            tooltip="For users with dyslexia, this font is designed to make reading easier. It has unique letter shapes that help prevent letter confusion."
                            label="Dyslexia font"
                            v-model="dyslexiaFontEnabled"
                        />
                    </div>
                </section>
                <section class="mb-8">
                    <div class="mb-4">
                        <h2 class="text-xl sm:text-2xl">Troubleshooting</h2>
                    </div>
                    <div class="flex flex-col gap-4">
                        <Accordion
                            v-for="(troubleshoot, index) in troubleshooting"
                            :key="index"
                            :question="troubleshoot.issue"
                            :answer="troubleshoot.solution"
                        />
                    </div>
                </section>
            </div>
        </Transition>
        <DeleteAccountModal
            :isVisible="isDeleteAccountModalVisible"
            @closeModal="isDeleteAccountModalVisible = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, watch } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";
import RegularButton from "@/components/buttons/RegularButton.vue";
import DeleteAccountModal from "@/components/modals/settings/DeleteAccountModal.vue";
import ToggleSwitchDescription from "@/components/forms/ToggleSwitchDescription.vue";
import Accordion from "@/components/forms/Accordion.vue";

const troubleshooting = ref([
    {
        issue: "Spotify web player is buggy when using Tunofy.",
        solution:
            "To make everything work smoothly, make sure to play one random song before activating the queue. This helps the web player to work as expected.",
    },
    {
        issue: "Guests cannot join the session",
        solution: "Guests need a Tunofy account to join a session.",
    },
    {
        issue: "Playback is lagging or buffering",
        solution:
            "Close any unnecessary apps or tabs that might be using bandwidth. If the issue continues, restart your device or router.",
    },
    {
        issue: "Track info not updating in real-time",
        solution:
            "Refresh the web app to re-sync the playback data. Check for any browser extensions that might block real-time updates and disable them temporarily.",
    },
]);

const isDeleteAccountModalVisible = ref(false);

const dataCollectionEnabled = ref(true);
const analyticsTrackingEnabled = ref(true);

//get dyslexia font preference from localstorage
const dyslexiaFontEnabled = ref(
    localStorage.getItem("dyslexiaFontEnabled") === "true"
);
watch(
    dyslexiaFontEnabled,
    (newValue) => {
        //save to localstorage
        localStorage.setItem("dyslexiaFontEnabled", Boolean(newValue));

        //toggle
        if (newValue) {
            document.documentElement.classList.add("font-lexend");
        } else {
            document.documentElement.classList.remove("font-lexend");
        }
    },
    { immediate: true }
);
</script>
