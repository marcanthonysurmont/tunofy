<template>
    <div
        class="relative aspect-[9/16] w-full sm:w-[70%] md:w-[320px] h-auto max-h-[80vh] bg-gradient-to-r from-pink-600 to-yellow-600 rounded-xl shadow-lg"
    >
        <div
            class="flex flex-col items-center justify-center h-full w-full p-6"
        >
            <template v-if="!showStats">
                <h1 ref="woahText" class="text-5xl text-center font-medium">
                    Woah!
                </h1>
                <p
                    ref="masterText"
                    class="text-center opacity-0 text-md font-medium"
                >
                    You're a true song master.
                </p>
            </template>

            <template v-else-if="showStats === 'totalSongs'">
                <div ref="statsBox" class="text-center opacity-0">
                    <h1 class="text-5xl font-medium">You added</h1>
                    <p class="text-center text-md font-medium">
                        a total of
                        <NumberFlow :value="totalSongs" :will-change="true" />
                        songs!
                    </p>
                </div>
            </template>

            <template v-else-if="showStats === 'totalMixes'">
                <div ref="mixesBox" class="text-center opacity-0">
                    <h1 class="text-5xl font-medium">You created</h1>
                    <p class="text-center text-md font-medium">
                        a total of
                        <NumberFlow :value="totalMixes" :will-change="true" />
                        mixes!
                    </p>
                </div>
            </template>

            <template v-else-if="showStats === 'mostAdded'">
                <div
                    class="absolute inset-0 z-0 overflow-hidden pointer-events-none"
                    v-if="showStats === 'mostAdded'"
                >
                    <div class="flex flex-col animate-scrollUp">
                        <div
                            v-for="i in 50"
                            :key="i"
                            class="text-white text-3xl font-black text-center opacity-10 leading-[2.5rem] whitespace-nowrap"
                        >
                            {{ highestAddedSong.song_name }}
                        </div>
                    </div>
                </div>
                <div
                    ref="mostBox"
                    class="opacity-0 flex flex-col items-center justify-center"
                >
                    <h2 class="text-3xl text-center mb-2 font-medium">
                        Most added song
                    </h2>
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
                            class="w-9 h-9 sm:w-10 sm:h-10 rounded flex-shrink-0"
                        />
                        <div class="min-w-0 flex-1 overflow-hidden">
                            <div
                                class="font-medium truncate text-neutral-100 text-sm"
                            >
                                {{ highestAddedSong.song_name }}
                            </div>
                            <div
                                class="text-neutral-200 text-xs sm:text-sm truncate"
                            >
                                {{ highestAddedSong.artist }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
    <audio
        ref="mostAddedSongAudioRef"
        :src="audioPreviewUrl"
        preload="auto"
    ></audio>
</template>

<script setup>
import { onMounted, ref, nextTick, computed, onBeforeUnmount } from "vue";
import gsap from "gsap";
import NumberFlow from "@number-flow/vue";
import { usePage } from "@inertiajs/vue3";

const woahText = ref(null);
const masterText = ref(null);
const statsBox = ref(null);
const mixesBox = ref(null);
const mostBox = ref(null);
const mostAddedSongAudioRef = ref(null);

const showStats = ref(false);

const page = usePage();
const globalStats = ref(page.props.globalUserStat);
const highestAddedSong = computed(() => page.props.highestAddedSong);

const totalSongs = ref(0);
const totalMixes = ref(0);

const audioPreviewUrl = ref(null);

async function getSongFile(trackId) {
    try {
        console.log(`Loading preview for track ${trackId}...`);
        const response = await axios.post(route("api.spotify.track-preview"), {
            track_id: trackId,
        });

        //store preview URL in cache
        if (response.data && response.data.preview_url) {
            audioPreviewUrl.value = response.data.preview_url;
        }
    } catch (error) {
        console.error(`Error loading preview for track ${trackId}:`, error);
    }
}
onMounted(async () => {
    await getSongFile(highestAddedSong.value.spotify_id);
});

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
            delay: 1.4,
            ease: "power1.in",
        })

        //stats section
        .add(async () => {
            showStats.value = "totalSongs";
            totalSongs.value = 0;
            setTimeout(() => {
                totalSongs.value = globalStats.value.songs_added;
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
            totalMixes.value = 0;
            setTimeout(() => {
                totalMixes.value = globalStats.value.mixes_created;
            }, 50);
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
            mostAddedSongAudioRef.value.volume = 0;
            mostAddedSongAudioRef.value.play();

            const targetVolume = 0.4;
            const interval = setInterval(() => {
                if (mostAddedSongAudioRef.value.volume < targetVolume) {
                    mostAddedSongAudioRef.value.volume = Math.min(
                        mostAddedSongAudioRef.value.volume + 0.05,
                        targetVolume
                    );
                } else {
                    clearInterval(interval);
                }
            }, 100);

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

onBeforeUnmount(() => {
    if (timeline) {
        timeline.kill();
        timeline = null;
    }
});
</script>

<style scoped>
.animate-scrollUp {
    animation: scrollUp 20s linear infinite;
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
