<template>
    <div class="flex flex-col items-center max-w-xl mx-6">
        <h1 class="text-5xl text-center mb-2">Voting time!</h1>
        <p class="text-center mb-6">Simply swipe <strong>left</strong> to like a
            song
            and swipe <strong>right</strong> to
            dislike a song. We've also added a "kill" option, which you can click.</p>
        <RegularButton color="blue" class="w-full max-w-sm" @click="confirm">Start voting!
        </RegularButton>
    </div>
</template>

<script setup>
import RegularButton from "@/components/buttons/RegularButton.vue";
</script>