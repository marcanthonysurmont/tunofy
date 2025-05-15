<template>
    <canvas
        v-if="themeSettings.fireworks"
        ref="fireworksRef"
        id="fireworks-canvas"
        class="fixed inset-0 -z-9 h-screen w-screen overflow-hidden pointer-events-none opacity-50"
    ></canvas>
</template>

<script setup>
import { onMounted, ref, watch, nextTick } from "vue";
import Fireworks from "fireworks-js";

const props = defineProps({
    themeSettings: {
        type: Object,
        required: true,
    },
});

const fireworksRef = ref(null);
let fireworksInstance = null;

function initializeFireworks() {
    if (!fireworksRef.value) return;

    if (fireworksInstance) {
        fireworksInstance.stop();
        fireworksInstance = null;
    }

    const options = {
        autoresize: true,
        opacity: 0.5,
        acceleration: 1.005,
        friction: 0.97,
        gravity: 1.5,
        particles: 50,
        traceLength: 3,
        traceSpeed: 5,
        explosion: 5,
        intensity: 5,
        flickering: 50,
        lineStyle: "round",
        hue: {
            min: 0,
            max: 360,
        },
        delay: {
            min: 30,
            max: 60,
        },
        rocketsPoint: {
            min: 50,
            max: 50,
        },
        lineWidth: {
            explosion: {
                min: 1,
                max: 3,
            },
            trace: {
                min: 1,
                max: 2,
            },
        },
        brightness: {
            min: 10,
            max: 15,
        },
        decay: {
            min: 0.015,
            max: 0.03,
        },
        mouse: {
            click: true,
            move: false,
            max: 1,
        },
    };

    fireworksInstance = new Fireworks(fireworksRef.value, options);
    fireworksInstance.start();
}

function stopFireworks() {
    if (fireworksInstance) {
        fireworksInstance.stop();
        fireworksInstance = null;
    }
}

watch(
    () => props.themeSettings.fireworks,
    async (newVal) => {
        if (newVal) {
            //wait until canvas is in DOM again
            //needed because of the v-if on the canvas element
            await nextTick();
            //
            initializeFireworks();
        } else {
            stopFireworks();
        }
    }
);

onMounted(() => {
    if (props.themeSettings.fireworks) {
        initializeFireworks();
    }
});
</script>
