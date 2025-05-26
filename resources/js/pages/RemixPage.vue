<template>
    <Head title="Tunofy | Remix" />
    <div class="flex flex-col h-screen px-4 max-w-6xl mx-auto">
        <div class="w-full flex gap-2 py-4 mb-4 md:mb-8">
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
        <div class="flex justify-between flex-row mb-4 md:mb-0">
            <Link :href="route('app')" class="flex items-center gap-2">
                <ChevronLeftIcon
                    class="w-6 h-6 md:w-8 md:h-8 text-white cursor-pointer"
                />
                <p class="m-0 font-medium text-lg md:text-xl">Go back</p>
            </Link>

            <SpeakerWaveIcon
                @click="handleAudio"
                class="size-6"
                v-if="!storeRemixAudio.isMuted"
            />
            <SpeakerXMarkIcon @click="handleAudio" class="size-6" v-else />
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
                            :class="
                                !started || currentStep === 0
                                    ? 'text-zinc-500'
                                    : 'text-white'
                            "
                            class="w-6 h-6 md:w-8 md:h-8 cursor-pointer"
                        />
                    </button>

                    <div
                        class="flex-grow flex justify-center items-center overflow-hidden"
                    >
                        <template v-if="started">
                            <component
                                :is="steps[currentStep]"
                                :key="steps[currentStep]"
                            />
                        </template>
                        <template v-else>
                            <StartStep
                                @start-remix="
                                    started = true;
                                    startProgress();
                                "
                            />
                        </template>
                    </div>

                    <button
                        class="hidden sm:block flex-shrink-0"
                        @click="goNext"
                    >
                        <ChevronRightIcon
                            class="w-6 h-6 md:w-8 md:h-8 cursor-pointer"
                            :class="
                                !started || currentStep === totalSteps - 1
                                    ? 'text-zinc-500'
                                    : 'text-white'
                            "
                        />
                    </button>
                </div>
            </div>
        </div>

        <div class="sm:hidden flex justify-center gap-8 py-4">
            <button class="flex-shrink-0" @click="goPrev">
                <ChevronLeftIcon
                    class="w-8 h-8"
                    :class="
                        !started || currentStep === 0
                            ? 'text-zinc-500'
                            : 'text-white'
                    "
                />
            </button>
            <button class="flex-shrink-0" @click="goNext">
                <ChevronRightIcon
                    class="w-8 h-8"
                    :class="
                        !started || currentStep === totalSteps - 1
                            ? 'text-zinc-500'
                            : 'text-white'
                    "
                />
            </button>
        </div>
    </div>
</template>

<script setup>
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    SpeakerWaveIcon,
    SpeakerXMarkIcon,
} from "@heroicons/vue/24/solid";
import StepOne from "@/components/remix/StepOne.vue";
import StepTwo from "@/components/remix/StepTwo.vue";
import StepThree from "@/components/remix/StepThree.vue";
import StepFour from "@/components/remix/StepFour.vue";
import { ref, onBeforeUnmount } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import StartStep from "@/components/remix/StartStep.vue";
import { StoreRemixAudio } from "@/stores/StoreRemixAudio";

const storeRemixAudio = StoreRemixAudio();

function handleAudio() {
    storeRemixAudio.toggleMute();
}

const steps = [StepOne, StepTwo, StepThree, StepFour];
const totalSteps = steps.length;
const currentStep = ref(0);
const progress = ref(0);
const started = ref(false);

//reactive flag to toggle CSS transition
const transitionEnabled = ref(false);

//custom durations per step in ms
const stepDurations = [15000, 15000, 15000, 10000];

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
    if (!started.value || currentStep.value >= totalSteps - 1) {
        return;
    }
    clearTimeout(fillCompleteTimeout);
    if (currentStep.value < totalSteps - 1) {
        currentStep.value++;
        startProgress();
    } else {
        clearInterval(timer);
    }
}

function goPrev() {
    if (!started.value || currentStep.value === 0) {
        return;
    }
    clearTimeout(fillCompleteTimeout);
    if (currentStep.value > 0) {
        currentStep.value--;
        startProgress();
    }
}

onBeforeUnmount(() => {
    clearInterval(timer);
    clearTimeout(fillCompleteTimeout);
});
</script>
