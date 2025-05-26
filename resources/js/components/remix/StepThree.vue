<template>
    <div
        class="relative aspect-[9/16] w-full sm:w-[70%] md:w-[320px] h-auto max-h-[80vh] bg-gradient-to-r from-blue-200 to-cyan-200 rounded-xl shadow-lg pulse-bg"
    >
        <div
            class="flex flex-col items-center justify-center h-full w-full text-black"
        >
            <template v-if="!showStats">
                <h1 ref="smoothText" class="text-5xl text-center font-medium">
                    Smooth like butter
                </h1>
                <p
                    ref="smoothSubText"
                    class="text-center opacity-0 text-md font-medium px-4"
                >
                    R&B is your top genre.. so smooth, even the biggest stars
                    are jealous of you.
                </p>
            </template>
            <template v-if="showStats === 'mostKilled'">
                <div
                    class="opacity-0 text-center flex justify-center items-center flex-col"
                    ref="killBox"
                >
                    <h1 ref="killText" class="text-5xl font-medium">
                        You killed it!
                    </h1>
                    <p
                        ref="killSubText"
                        class="text-md font-medium px-6"
                        v-if="amountOfSongsKilled > 0"
                    >
                        Not literally, but you did kill
                        <NumberFlow
                            :value="amountOfSongsKilled"
                            :will-change="true"
                        />
                        {{ amountOfSongsKilled === 1 ? "song" : "songs" }}.
                    </p>

                    <p
                        ref="killSubText"
                        class="text-md font-medium px-6"
                        v-else
                    >
                        Not literally, but no songs were harmed this year.
                    </p>
                    <img
                        class="size-24 object-cover rounded-lg mt-8"
                        src="https://media2.giphy.com/media/v1.Y2lkPTc5MGI3NjExdjUyb3RqZGlnd3ZraWc5dDlkcDFyNG1rZ3Z5Nzhpb3k1Y3BydXZpdSZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/XpbBHJS7fNVHG/giphy.gif"
                    />
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, nextTick } from "vue";
import gsap from "gsap";
import NumberFlow from "@number-flow/vue";
import { usePage } from "@inertiajs/vue3";

const smoothText = ref(null);
const smoothSubText = ref(null);
const killText = ref(null);
const killSubText = ref(null);
const amountOfSongsKilled = ref(0);
const showStats = ref(false);
const killBox = ref(null);

const page = usePage();
const globalStats = ref(page.props.globalUserStat);

onMounted(() => {
    const timeline = gsap.timeline();

    timeline
        //intro text section
        .fromTo(
            smoothText.value,
            { opacity: 0, y: 30, scale: 0.8, rotation: 5 },
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
            smoothSubText.value,
            { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" },
            "+=0.3"
        )
        .to([smoothText.value, smoothSubText.value], {
            opacity: 0,
            scale: 0.8,
            rotation: -5,
            duration: 0.5,
            delay: 4,
            ease: "power1.in",
        })
        .add(async () => {
            showStats.value = "mostKilled";
            amountOfSongsKilled.value = 0;
            setTimeout(() => {
                amountOfSongsKilled.value = globalStats.value.kill_count;
            }, 50);
            await nextTick();
            gsap.fromTo(
                killBox.value,
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
        .to(killBox.value, {
            opacity: 0,
            scale: 0.8,
            rotation: -3,
            duration: 0.4,
            delay: 3,
            ease: "power1.in",
        });
});
</script>

<style scoped>
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
