<template>
    <div
        class="relative aspect-[9/16] w-full sm:w-[70%] md:w-[320px] h-auto max-h-[80vh] animated-gradient rounded-xl shadow-lg"
    >
        <div class="flex flex-col items-center justify-center h-full w-full">
            <template v-if="!showStats">
                <h1 ref="thanksHeader" class="text-5xl text-center font-medium">
                    Thank you!
                </h1>
                <p
                    ref="thanksText"
                    class="text-center opacity-0 text-md font-medium px-4"
                >
                    We appreciate you using Tunofy. We hope you enjoyed your
                    remix ❣️
                </p>
            </template>
            <template v-if="showStats">
                <button
                    ref="goHomeButton"
                    @click="goHome"
                    class="self-center bg-zinc-100 text-zinc-900 text-sm md:text-base font-semibold px-5 py-2 rounded-full hover:bg-zinc-300"
                >
                    Go to app
                </button>
            </template>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, nextTick } from "vue";
import gsap from "gsap";
import { router } from "@inertiajs/vue3";

const thanksHeader = ref(null);
const thanksText = ref(null);
const goHomeButton = ref(null);
const showStats = ref(false);

function goHome() {
    router.visit(route("app"));
}

onMounted(() => {
    const timeline = gsap.timeline();

    timeline
        //intro text section
        .fromTo(
            thanksHeader.value,
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
            thanksText.value,
            { opacity: 0, y: 20 },
            { opacity: 1, y: 0, duration: 0.5, ease: "power2.out" },
            "+=0.3"
        )
        .to([thanksHeader.value, thanksText.value], {
            opacity: 0,
            scale: 0.8,
            rotation: 5,
            duration: 0.5,
            delay: 5,
            ease: "power1.in",
        })
        .add(async () => {
            showStats.value = true;
            await nextTick();
            gsap.fromTo(
                goHomeButton.value,
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
