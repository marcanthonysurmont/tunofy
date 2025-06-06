<template>
    <div
        class="relative aspect-[9/16] w-full sm:w-[70%] md:w-[320px] h-auto max-h-[80vh] animated-gradient rounded-xl shadow-lg"
    >
        <div
            class="flex flex-col items-center justify-center h-full w-full p-6"
        >
            <template v-if="!showStats">
                <h1 ref="woahText" class="text-5xl text-center font-medium">
                    Hi, {{ user.name.split(" ")[0] }}
                </h1>
                <p
                    ref="masterText"
                    class="text-center opacity-0 text-md font-medium px-4"
                >
                    It's the end of the year, and your yearly remix is ready.
                    Are you too?
                </p>
            </template>
            <template v-if="showStats === 'funFact'">
                <div ref="funFact" v-if="globalStats.mixes_played > 20">
                    <h1 class="text-4xl text-center font-medium">
                        King of parties!
                    </h1>
                    <p class="text-center text-md font-medium">
                        You did so much partying even our servers couldn't keep
                        up. You played {{ globalStats.mixes_played }} mixes!
                    </p>
                </div>
                <div ref="funFact" v-else-if="globalStats.mixes_played > 10">
                    <h1 class="text-4xl text-center font-medium">
                        Your playlists are 🔥!
                    </h1>
                    <p class="text-center text-md font-medium">
                        You kept the dance floor moving all year long with
                        {{ globalStats.mixes_played }} mixes.
                    </p>
                </div>
                <div ref="funFact" v-else-if="globalStats.mixes_played > 5">
                    <h1 class="text-4xl text-center font-medium">
                        Solid party streak
                    </h1>
                    <p class="text-center text-md font-medium">
                        You kept the beats rolling and the vibes high. A total
                        of {{ globalStats.mixes_played }} mixes played.
                    </p>
                </div>
                <div ref="funFact" v-else-if="globalStats.mixes_played >= 1">
                    <h1 class="text-4xl text-center font-medium">
                        Less is more
                    </h1>
                    <p class="text-center text-md font-medium">
                        You played
                        {{
                            globalStats.mixes_played === 1
                                ? "1 mix, and it hit just right."
                                : globalStats.mixes_played +
                                  " mixes, and each one hit just right."
                        }}
                    </p>
                </div>
                <div ref="funFact" v-else>
                    <h1 class="text-4xl text-center font-medium">
                        No parties 😔
                    </h1>
                    <p class="text-center text-md font-medium">
                        Don't worry! The future is bright just like the party
                        room.
                    </p>
                </div>
            </template>

            <template v-if="showStats === 'dancingGif'">
                <div
                    ref="dancingGif"
                    class="text-center flex justify-center flex-col items-center"
                >
                    <img
                        class="size-32 rounded-lg"
                        alt="Gif of a dancing black male"
                        src="https://media0.giphy.com/media/v1.Y2lkPTc5MGI3NjExZTRrcHJ5cG04MXJ0bjBkMHBpd24wNzF4cW4zaWxhNDRveXF0cnh3cCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/cklPOHnHepdwBLRnQp/giphy.gif"
                    />
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, nextTick, onBeforeUnmount } from "vue";
import gsap from "gsap";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const user = ref(page.props.user);
const globalStats = ref(page.props.globalUserStat);

const woahText = ref(null);
const masterText = ref(null);
const funFact = ref(null);
const dancingGif = ref(null);

const showStats = ref(false);

let timeline;

function pause() {
    if (timeline) {
        timeline.pause();
    }
}

function resume() {
    if (timeline) {
        timeline.resume();
    }
}

defineExpose({ pause, resume });

onMounted(() => {
    timeline = gsap.timeline();

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
            delay: 5,
            ease: "power1.in",
        })
        .add(async () => {
            showStats.value = "funFact";
            await nextTick();
            gsap.fromTo(
                funFact.value,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" }
            );
        })
        .to(funFact.value, {
            opacity: 0,
            scale: 0.8,
            rotation: -3,
            duration: 0.4,
            delay: 5,
            ease: "power1.in",
        })
        .add(async () => {
            showStats.value = "dancingGif";
            await nextTick();
            gsap.fromTo(
                dancingGif.value,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" }
            );
        });
});

onBeforeUnmount(() => {
    if (timeline) {
        timeline.kill();
        timeline = null;
    }
});
</script>

<style scoped>
.animated-gradient {
    background: linear-gradient(-45deg, #2563eb, #7c3aed, #2563eb);
    background-size: 200% 200%;
    animation: pulseBg 6s ease infinite;
}

@keyframes pulseBg {
    0%,
    100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    25% {
        background-position: 250% 75%;
    }
}

.animate-scrollUp {
    animation: scrollUp 9s linear infinite;
}
@keyframes scrollUp {
    0% {
        transform: translateY(0%);
    }
    100% {
        transform: translateY(-50%);
    }
}
</style>
