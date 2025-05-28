<template>
    <Head title="Tunofy | App" />
    <AppLayout>
        <Transition name="fade-with-slide" appear mode="out-in">
            <div class="flex flex-col gap-8">
                <WrappedSection
                    imgUrl="/images/decorations/wrapped_art2.jpg"
                    title="See how you mixed it up in 2025"
                    description="Wonder what you listened to the most in 2025? Check out your remix and see your top songs, artists, and genres of the year."
                    ctaText="See your remix"
                    @onClick="() => console.log('Wrapped clicked')"
                />
                <NewsSection />
            </div>
        </Transition>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";
import NewsSection from "@/components/main-page/NewsSection.vue";
import WrappedSection from "@/components/main-page/WrappedSection.vue";
</script>
