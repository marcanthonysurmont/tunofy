<template>
    <Head title="Tunofy | App" />
    <AppLayout>
        <Transition name="fade-with-slide" appear mode="out-in">
            <div class="flex flex-col gap-8 max-w-3xl">
                <section class="mb-8">
                    <div class="mb-8 py-6 border-b-2 border-card-stroke">
                        <h1 class="text-3xl sm:text-4xl font-medium mb-2">
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
                        <RegularButton type="danger" external-class="mt-2">
                            Delete account
                        </RegularButton>
                    </div>
                </section>
                <section class="mb-8">
                    <div class="mb-4">
                        <h2 class="text-xl sm:text-2xl">
                            Accessibility (coming soon)
                        </h2>
                        <p class="text-muted">
                            Manage accessibility settings for your account
                        </p>
                    </div>
                </section>
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import { ref } from "vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";
import RegularButton from "@/components/buttons/RegularButton.vue";
</script>
