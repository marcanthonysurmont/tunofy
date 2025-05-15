<template>
    <canvas
        v-if="themeSettings.falling_confetti"
        id="confetti-canvas"
        class="fixed inset-0 z-[9999999] h-screen w-screen overflow-hidden pointer-events-none opacity-25"
    ></canvas>
</template>

<script setup>
import { onMounted, ref, watch, nextTick } from "vue";

const props = defineProps({
    themeSettings: {
        type: Object,
        required: true,
    },
});

let confettiAnimationId = null;
function initializeConfetti() {
    nextTick(() => {
        const canvas = document.getElementById("confetti-canvas");
        if (!canvas) return;
        const ctx = canvas.getContext("2d");
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const confettiCount = 40;
        const confetti = [];

        for (let i = 0; i < confettiCount; i++) {
            confetti.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 6 + 4,
                d: Math.random() * confettiCount,
                color: `hsl(${Math.random() * 360}, 100%, 70%)`,
                tilt: Math.floor(Math.random() * 10) - 10,
                tiltAngleIncrement: Math.random() * 0.1 + 0.05,
                tiltAngle: 0,
            });
        }

        function drawConfetti() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            confetti.forEach((c) => {
                ctx.beginPath();
                ctx.lineWidth = c.r / 2;
                ctx.strokeStyle = c.color;
                ctx.moveTo(c.x + c.tilt + c.r / 4, c.y);
                ctx.lineTo(c.x + c.tilt, c.y + c.tilt + c.r / 4);
                ctx.stroke();
            });

            updateConfetti();
            confettiAnimationId = requestAnimationFrame(drawConfetti);
        }

        function updateConfetti() {
            confetti.forEach((c, i) => {
                c.tiltAngle += c.tiltAngleIncrement * 0.2;
                c.y += (Math.cos(c.d) + 0.3 + c.r / 10) * 0.2;
                c.x += Math.sin(c.d) * 0.2;
                c.tilt = Math.sin(c.tiltAngle) * 10;

                if (c.y > canvas.height) {
                    confetti[i] = {
                        x: Math.random() * canvas.width,
                        y: -10,
                        r: c.r,
                        d: c.d,
                        color: c.color,
                        tilt: 0,
                        tiltAngleIncrement: c.tiltAngleIncrement,
                        tiltAngle: 0,
                    };
                }
            });
        }
        drawConfetti();
    });
}

function stopConfetti() {
    if (confettiAnimationId) {
        cancelAnimationFrame(confettiAnimationId);
        confettiAnimationId = null;
    }
    const canvas = document.getElementById("confetti-canvas");
    if (canvas) {
        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
}

watch(
    () => props.themeSettings.falling_confetti,
    (newVal) => {
        if (newVal) {
            initializeConfetti();
        } else {
            stopConfetti();
        }
    }
);

onMounted(() => {
    if (props.themeSettings.falling_confetti) {
        initializeConfetti();
    }
});
</script>
