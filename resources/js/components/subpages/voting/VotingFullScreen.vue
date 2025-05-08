<template>
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <div
                v-if="isVisible"
                class="fixed left-0 top-0 z-[9999] flex h-full w-full items-center justify-center gap-8 bg-black/75 backdrop-blur-md shadow-2xl transition-all duration-150 ease-in-out"
            >
                <span
                    class="absolute right-0 top-0 cursor-pointer p-4 text-xl z-[10000]"
                    @click="isVisible = false"
                >
                    <XMarkIcon class="size-10 text-white" />
                </span>
                <Transition name="fade-with-slide" mode="out-in">
                    <div v-if="finishedVoting" key="finished">
                        <FinishedScreen
                            @close-window="handleCloseWindowEvent"
                        />
                    </div>
                    <div v-else-if="hasClickedContinue" key="voting">
                        <VotingScreen @end-voting="handleEndVotingEvent" />
                    </div>
                    <div v-else key="intro">
                        <IntroductionScreen
                            @start-voting="handleStartVotingEvent"
                        />
                    </div>
                </Transition>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import { XMarkIcon } from "@heroicons/vue/24/outline";
import { onMounted, ref } from "vue";
import IntroductionScreen from "./IntroductionScreen.vue";
import VotingScreen from "./VotingScreen.vue";
import FinishedScreen from "./FinishedScreen.vue";

const isVisible = ref(false);

onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 1);
});

const hasClickedContinue = ref(false);
function handleStartVotingEvent() {
    hasClickedContinue.value = true;
}

const finishedVoting = ref(false);
function handleEndVotingEvent() {
    console.log("Finished voting");
    finishedVoting.value = true;
}

function handleCloseWindowEvent() {
    isVisible.value = false;
    finishedVoting.value = false;
    hasClickedContinue.value = false;
}
</script>
