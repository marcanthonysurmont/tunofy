<template>
    <div
        class="relative aspect-[9/16] w-full sm:w-[70%] md:w-[320px] h-auto max-h-[80vh] bg-gradient-to-r from-blue-600 to-violet-600 rounded-xl shadow-lg"
    >
        <div class="flex flex-col items-center justify-center h-full w-full">
            <template v-if="!showStats">
                <h2 ref="woahText" class="text-5xl text-center">Woah!</h2>
                <p ref="masterText" class="text-xl text-center opacity-0">
                    You're a true song master.
                </p>
            </template>

            <template v-else-if="showStats === 'totalSongs'">
                <div ref="statsBox" class="text-center opacity-0">
                    <h2 class="text-3xl">You added</h2>
                    <p class="text-lg">a total of {{ totalSongs }} songs!</p>
                </div>
            </template>

            <template v-else-if="showStats === 'totalMixes'">
                <div ref="mixesBox" class="text-center opacity-0">
                    <h2 class="text-3xl">You created</h2>
                    <p class="text-lg">{{ totalMixes }} mixes!</p>
                </div>
            </template>

            <template v-else-if="showStats === 'mostAdded'">
                <div ref="mostBox" class="text-center opacity-0">
                    <h2 class="text-3xl">Most added song</h2>
                    <p class="text-lg">"{{ mostAddedSong }}"</p>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, nextTick } from "vue";
import gsap from "gsap";

const totalSongs = ref(654);
const totalMixes = ref(12);
const mostAddedSong = ref("Let It Happen");

const woahText = ref(null);
const masterText = ref(null);
const statsBox = ref(null);
const mixesBox = ref(null);
const mostBox = ref(null);

const showStats = ref(false);

onMounted(() => {
    const timeline = gsap.timeline();

    timeline
        .fromTo(
            woahText.value,
            { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: 0.4 }
        )
        .fromTo(
            masterText.value,
            { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: 0.4 },
            "+=0.2"
        )
        .to([woahText.value, masterText.value], {
            opacity: 0,
            duration: 0.3,
            delay: 1.2,
        })

        //section total songs
        .add(async () => {
            showStats.value = "totalSongs";
            await nextTick();
            gsap.fromTo(
                statsBox.value,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.4 }
            );
        })
        .to(statsBox.value, { opacity: 0, duration: 0.3, delay: 3 })

        //section total mixes
        .add(async () => {
            showStats.value = "totalMixes";
            await nextTick();
            gsap.fromTo(
                mixesBox.value,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.4 }
            );
        })
        .to(mixesBox.value, { opacity: 0, duration: 0.3, delay: 3 })

        //section most added
        .add(async () => {
            showStats.value = "mostAdded";
            await nextTick();
            gsap.fromTo(
                mostBox.value,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.4 }
            );
        });
});
</script>
