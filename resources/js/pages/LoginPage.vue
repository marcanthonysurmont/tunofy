<template>
    <div class="flex flex-col justify-center items-center min-h-dvh min-w-dvw p-4">
        <div class="mb-16">
            <img :src="'images/logos/tunofy-logo-white.png'" alt="Tunofy Logo White" class="">
        </div>
        <h1 class="text-5xl md:text-6xl text-center">Sign in to your account</h1>
        <Link href="/login" class="mt-16">
            <button class="bg-primary custom-item-hover text-white cursor-pointer px-8 py-3 rounded-xl hover:bg-tunofy-blue-dark flex flex-row items-center justify-center gap-6">
                <img :src="'images/logos/spotify-logo-white.png'" alt="Spotify Logo White" class="size-8 md:size-10">
                <p class="font-nunito font-medium text-lg md:text-xl">Log in with Spotify</p>
            </button>
        </Link>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount } from 'vue';
import { Link } from '@inertiajs/vue3';

onMounted(() => {
    document.body.classList.add('bg-login');
});

onBeforeUnmount(() => {
    document.body.classList.remove('bg-login');
});
</script>
