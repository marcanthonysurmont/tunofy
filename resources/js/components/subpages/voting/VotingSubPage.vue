<template>
    <!-- <h1 class="text-4xl sm:text-5xl font-medium mb-2">Voting</h1>
    <p class="mb-8">Vote to decide what plays next.</p> -->

    <div class="flex flex-row items-center gap-6">
        <h1 class="text-4xl sm:text-5xl font-medium mb-2">Live leaderboard</h1>
        <RegularButton
            color="blue"
            @click="isVisible = true"
            external-class="hidden lg:inline-flex mb-2"
        >
            Vote now!
        </RegularButton>
    </div>
    <teleport to="body">
        <RegularButton
            color="blue"
            @click="isVisible = true"
            :class="[
                'fixed-vote-button bottom-32 right-4 !fixed lg:hidden transition-all duration-300 ease-in-out',
                { 'animate-pulse': !isVisible },
            ]"
        >
            Vote now!
        </RegularButton>
    </teleport>

    <VotingFullScreen
        :is-visible="isVisible"
        @close-fullscreen="isVisible = false"
    />
</template>

<script setup>
import { ref } from "vue";
import RegularButton from "@/components/buttons/RegularButton.vue";
import VotingFullScreen from "@/components/subpages/voting/VotingFullScreen.vue";

const isVisible = ref(false);
</script>

<style scoped>
/* .fixed-vote-button {
    position: fixed;
    right: max(calc((100vw - 1955px) / 2 + 1rem), 1rem);
    z-index: 50;
} */
</style>
