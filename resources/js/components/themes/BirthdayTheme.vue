<template>
    <div
        class="fixed inset-0 -z-10 h-screen w-screen overflow-hidden pointer-events-none"
        style="
            background-image: url('/images/decorations/confetti3.png');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            margin: 0;
            padding: 0;
        "
    ></div>
    <div
        class="fixed inset-0 -z-9 h-screen w-screen bg-background-page opacity-95 pointer-events-none"
    ></div>
    <canvas
        v-if="themeSettings.falling_confetti"
        id="confetti-canvas"
        class="fixed inset-0 z-[9999999] h-screen w-screen overflow-hidden pointer-events-none opacity-25"
    ></canvas>
    <canvas
        v-if="themeSettings.fireworks"
        ref="fireworksRef"
        id="fireworks-canvas"
        class="fixed inset-0 -z-9 h-screen w-screen overflow-hidden pointer-events-none opacity-50"
    ></canvas>
</template>

<script setup>
import { onMounted, ref } from "vue";
import Fireworks from "fireworks-js";

const fireworksRef = ref(null);

const props = defineProps({
    themeSettings: {
        type: Object,
    },
});

function initializeFireworks() {
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
        intensity: 4,
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

    const fireworks = new Fireworks(fireworksRef.value, options);
    fireworks.start();
}

onMounted(() => {
    if (props.themeSettings.fireworks) {
        initializeFireworks();
    }
    if (props.themeSettings.falling_confetti) {
        const canvas = document.getElementById("confetti-canvas");
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
            requestAnimationFrame(drawConfetti);
        }

        function updateConfetti() {
            confetti.forEach((c, i) => {
                c.tiltAngle += c.tiltAngleIncrement * 0.2; // slower tilt change
                c.y += (Math.cos(c.d) + 0.3 + c.r / 10) * 0.2; // slower fall
                c.x += Math.sin(c.d) * 0.2; // slower sway
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
    }
});
</script>
