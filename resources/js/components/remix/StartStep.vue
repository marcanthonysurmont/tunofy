<template>
    <div
        class="relative aspect-[9/16] w-full sm:w-[70%] md:w-[320px] h-auto max-h-[80vh] animated-gradient rounded-xl shadow-lg"
    >
        <div class="flex flex-col items-center justify-center h-full w-full">
            <button
                ref="goHomeButton"
                @click="startRemix"
                class="self-center bg-zinc-100 text-zinc-900 text-sm md:text-base font-semibold px-5 py-2 rounded-full hover:bg-zinc-300"
            >
                Start your remix
            </button>
        </div>
    </div>
</template>

<script setup>
const emits = defineEmits(["start-remix"]);

function startRemix() {
    emits("start-remix");
}
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
</style>
