<template>
    <div class="flex flex-col h-screen px-4 max-w-6xl mx-auto">
        <div class="w-full flex gap-2 py-4">
            <div
                v-for="(step, index) in totalSteps"
                :key="index"
                class="h-1 flex-1 bg-zinc-500 overflow-hidden rounded-full"
            >
                <div
                    class="h-full bg-white"
                    :class="{
                        'transition-all duration-700': transitionEnabled,
                    }"
                    :style="{
                        width:
                            index < currentStep
                                ? '100%'
                                : index === currentStep
                                ? progress + '%'
                                : '0%',
                    }"
                />
            </div>
        </div>

        <div class="flex-grow flex justify-center items-center overflow-hidden">
            <div
                class="w-full max-w-full md:max-w-none flex items-center justify-center"
            >
                <div
                    class="flex items-center justify-center gap-2 md:gap-6 w-full"
                >
                    <button
                        class="hidden sm:block flex-shrink-0"
                        @click="goPrev"
                    >
                        <ChevronLeftIcon
                            class="w-6 h-6 md:w-8 md:h-8 text-white"
                        />
                    </button>

                    <div
                        class="flex-grow flex justify-center items-center overflow-hidden"
                    >
                        <component
                            :is="steps[currentStep]"
                            :key="steps[currentStep]"
                        />
                    </div>

                    <button
                        class="hidden sm:block flex-shrink-0"
                        @click="goNext"
                    >
                        <ChevronRightIcon
                            class="w-6 h-6 md:w-8 md:h-8 text-white"
                        />
                    </button>
                </div>
            </div>
        </div>

        <div class="sm:hidden flex justify-center gap-8 py-4">
            <button class="flex-shrink-0" @click="goPrev">
                <ChevronLeftIcon class="w-8 h-8 text-white" />
            </button>
            <button class="flex-shrink-0" @click="goNext">
                <ChevronRightIcon class="w-8 h-8 text-white" />
            </button>
        </div>
    </div>
</template>

<script setup>
import { ChevronLeftIcon, ChevronRightIcon } from "@heroicons/vue/24/solid";
import StepOne from "@/components/remix/StepOne.vue";
import StepTwo from "@/components/remix/StepTwo.vue";
import StepThree from "@/components/remix/StepThree.vue";
import StepFour from "@/components/remix/StepFour.vue";
import StepFive from "@/components/remix/StepFive.vue";
import { ref, onMounted, onBeforeUnmount } from "vue";

const totalSteps = 5;
const currentStep = ref(0);
const progress = ref(0);

//reactive flag to toggle CSS transition
const transitionEnabled = ref(false);

const steps = [StepOne, StepTwo, StepThree, StepFour, StepFive];

//custom durations per step in ms
const stepDurations = [15000, 7000, 4000, 6000, 5000];

let timer = null;
let fillCompleteTimeout = null;

function startProgress() {
    clearInterval(timer);
    clearTimeout(fillCompleteTimeout);
    progress.value = 0;
    transitionEnabled.value = false;

    const duration = stepDurations[currentStep.value] || 5000;
    const intervalMs = 50;
    const increments = duration / intervalMs;
    const incrementValue = 100 / increments;

    timer = setInterval(() => {
        //disable transition while incrementing
        transitionEnabled.value = false;
        progress.value += incrementValue;

        if (progress.value >= 100) {
            progress.value = 100;
            clearInterval(timer);
            //enable transition for final fill animation
            transitionEnabled.value = true;

            fillCompleteTimeout = setTimeout(() => {
                goNext();
            }, 700);
        }
    }, intervalMs);
}

function goNext() {
    clearTimeout(fillCompleteTimeout);
    if (currentStep.value < totalSteps - 1) {
        currentStep.value++;
        startProgress();
    } else {
        clearInterval(timer);
    }
}

function goPrev() {
    clearTimeout(fillCompleteTimeout);
    if (currentStep.value > 0) {
        currentStep.value--;
        startProgress();
    }
}

onMounted(() => {
    startProgress();
});

onBeforeUnmount(() => {
    clearInterval(timer);
    clearTimeout(fillCompleteTimeout);
});
</script>
