<template>
    <div
        class="relative aspect-[9/16] w-full sm:w-[70%] md:w-[320px] h-auto max-h-[80vh] animated-gradient rounded-xl shadow-lg"
    >
        <div class="flex flex-col items-center justify-center h-full w-full">
            <template v-if="!showStats">
                <h1 ref="woahText" class="text-5xl text-center font-medium">
                    Hi, Gilles
                </h1>
                <p
                    ref="masterText"
                    class="text-center opacity-0 text-md font-medium"
                >
                    It's the end of the year, and your yearly remix is ready.
                    Are you too?
                </p>
            </template>
            <template v-if="showStats === 'funFact'">
                <div ref="funFact">
                    <h1 class="text-4xl text-center font-medium">
                        King of parties!
                    </h1>
                    <p class="text-center text-md font-medium">
                        You did so much partying even our servers couldn't keep
                        up!
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
                        alt="dancing gif"
                        src="https://media0.giphy.com/media/v1.Y2lkPTc5MGI3NjExZTRrcHJ5cG04MXJ0bjBkMHBpd24wNzF4cW4zaWxhNDRveXF0cnh3cCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/cklPOHnHepdwBLRnQp/giphy.gif"
                    />
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, nextTick } from "vue";
import gsap from "gsap";

const woahText = ref(null);
const masterText = ref(null);
const funFact = ref(null);
const dancingGif = ref(null);

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
            delay: 3,
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
