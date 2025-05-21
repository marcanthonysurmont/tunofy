<template>
    <div
        class="relative aspect-[9/16] w-full sm:w-[70%] md:w-[320px] h-auto max-h-[80vh] bg-gradient-to-r from-blue-600 to-violet-600 rounded-xl shadow-lg"
    >
        <div class="flex flex-col items-center justify-center h-full w-full">
            <template v-if="!showStats">
                <h2 ref="woahText" class="text-5xl text-center">Woah!</h2>
                <p ref="masterText" class="text-xl text-center opacity-0">
                    You're a true tunofy master.
                </p>
            </template>

            <template v-else-if="showStats === 'totalSongs'">
                <div ref="statsBox" class="text-center opacity-0">
                    <h2 class="text-4xl">You added</h2>
                    <p class="text-lg">
                        a total of
                        <NumberFlow :value="totalSongs" />
                        songs!
                    </p>
                </div>
            </template>

            <template v-else-if="showStats === 'totalMixes'">
                <div ref="mixesBox" class="text-center opacity-0">
                    <h2 class="text-4xl">You created</h2>
                    <p class="text-lg">{{ totalMixes }} mixes!</p>
                </div>
            </template>

            <template v-else-if="showStats === 'mostAdded'">
                <div ref="mostBox" class="opacity-0">
                    <h2 class="text-3xl text-center mb-2">Most added song</h2>
                    <div
                        class="flex items-center gap-2 sm:gap-3 max-w-[200px] mx-auto truncate"
                    >
                        <img
                            v-lazy="{
                                src: 'https://i.scdn.co/image/ab67616d00001e0281e8dbcc784d8dbc7243ee0e',
                                error: '/images/default-song.png',
                                loading: '/images/default-song.png',
                            }"
                            alt="cover"
                            class="w-8 h-8 sm:w-10 sm:h-10 rounded flex-shrink-0"
                        />
                        <div class="min-w-0 flex-1 overflow-hidden">
                            <div
                                class="font-medium truncate text-neutral-100 text-sm"
                            >
                                OFF THE WALl!
                            </div>
                            <div
                                class="text-neutral-200 text-xs sm:text-sm truncate"
                            >
                                XXXTentacion & Ski Mask The Slump God
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, nextTick } from "vue";
import gsap from "gsap";
import NumberFlow from "@number-flow/vue";

const totalSongs = ref(0);
const totalMixes = ref(12);

const woahText = ref(null);
const masterText = ref(null);
const statsBox = ref(null);
const mixesBox = ref(null);
const mostBox = ref(null);

const showStats = ref(false);

onMounted(() => {
    const timeline = gsap.timeline();

    timeline
        //intro text section
        .fromTo(
            woahText.value,
            { opacity: 0, y: 30, scale: 0.8, rotation: -5 },
            {
                opacity: 1,
                y: 0,
                scale: 1,
                rotation: 0,
                duration: 0.7,
                ease: "elastic.out(1, 0.6)",
            }
        )
        .fromTo(
            masterText.value,
            { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" },
            "+=0.3"
        )
        .to([woahText.value, masterText.value], {
            opacity: 0,
            scale: 0.8,
            rotation: 5,
            duration: 0.5,
            delay: 1.4,
            ease: "power1.in",
        })

        //stats section
        .add(async () => {
            showStats.value = "totalSongs";
            totalSongs.value = 0;
            setTimeout(() => {
                totalSongs.value = 406;
            }, 50);
            await nextTick();
            gsap.fromTo(
                statsBox.value,
                { opacity: 0, y: 30, scale: 0.85 },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.6,
                    ease: "back.out(1.7)",
                }
            );
        })
        .to(statsBox.value, {
            opacity: 0,
            scale: 0.8,
            rotation: -3,
            duration: 0.4,
            delay: 3,
            ease: "power1.in",
        })

        //mixes section
        .add(async () => {
            showStats.value = "totalMixes";
            await nextTick();
            gsap.fromTo(
                mixesBox.value,
                { opacity: 0, y: 30, scale: 0.85 },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.6,
                    ease: "back.out(1.7)",
                    onComplete: () => {
                        gsap.to(mixesBox.value, {
                            scale: 1.05,
                            duration: 0.4,
                            yoyo: true,
                            repeat: 1,
                            ease: "sine.inOut",
                        });
                    },
                }
            );
        })
        .to(mixesBox.value, {
            opacity: 0,
            scale: 0.8,
            rotation: 3,
            duration: 0.4,
            delay: 3,
            ease: "power1.in",
        })

        //most added section
        .add(async () => {
            showStats.value = "mostAdded";
            await nextTick();
            gsap.fromTo(
                mostBox.value,
                { opacity: 0, y: 30, scale: 0.85 },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.6,
                    ease: "back.out(1.7)",
                    onComplete: () => {
                        gsap.timeline({ repeat: 1, yoyo: true })
                            .to(mostBox.value, {
                                scale: 1.07,
                                duration: 0.3,
                                ease: "sine.inOut",
                            })
                            .to(mostBox.value, { rotation: 3, duration: 0.1 })
                            .to(mostBox.value, { rotation: -3, duration: 0.1 })
                            .to(mostBox.value, { rotation: 0, duration: 0.1 });
                    },
                }
            );
        });
});
</script>

<style scoped>
@keyframes pulseBg {
    0%,
    100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}
</style>
