<template>
    <div class="flex flex-col items-center">
        <SongCard
            v-for="(song, index) in songs"
            :key="song.id"
            :song="song"
            :is-active="index === currentIndex"
            :swipe-length-x="swipeLengthX"
            :like-opacity="likeOpacity"
            :dislike-opacity="dislikeOpacity"
            :skull-animation="skullAnimation"
            :skull-animation-fading="skullAnimationFading"
            :is-new-card-animating="
                isNewCardAnimating && index === currentIndex
            "
            :reset-swipe="resetSwipe"
            :transition-to-next="transitionToNext"
            :is-dragging="isDragging"
            :is-disabled="
                isFakeSwipeAnimating ||
                transitionToNext ||
                skullAnimation ||
                isNewCardAnimating
            "
            :current-playing-id="currentPlayingId"
            :is-playing="isPlaying"
            :card-transform="cardTransform(index)"
            :card-opacity="cardOpacity(index)"
            @toggle-audio="toggleAudio"
            @start-drag="startDrag"
            @on-drag="onDrag"
            @end-drag="endDrag"
        />

        <VotingActions
            :bounce-state="bounceState"
            :is-disabled="
                isFakeSwipeAnimating || isNewCardAnimating || skullAnimation
            "
            @click-left="clickLeft"
            @click-right="clickRight"
            @kill="kill"
        />

        <AudioElements
            :audio-preview-cache="audioPreviewCache"
            :audio-elements="audioElements"
            @handle-play="handlePlay"
            @pause="
                () => {
                    isPlaying = false;
                }
            "
            @ended="
                () => {
                    isPlaying = false;
                }
            "
        />
    </div>
</template>

<script setup>
import SongCard from "@/components/subpages/voting/SongCard.vue";
import VotingActions from "@/components/subpages/voting/VotingActions.vue";
import AudioElements from "@/components/subpages/voting/AudioElements.vue";
import { usePage, router } from "@inertiajs/vue3";
import axios from "axios";
import { ref, computed, onMounted } from "vue";
import { usePointerX } from "@/composables/usePointerX.js";
const { calculateClientX } = usePointerX();

const likeOpacity = computed(() => {
    return swipeLengthX.value > 0 ? Math.min(swipeLengthX.value / 100, 1) : 0;
});
const dislikeOpacity = computed(() => {
    return swipeLengthX.value < 0
        ? Math.min(Math.abs(swipeLengthX.value) / 100, 1)
        : 0;
});

const bounceState = ref({
    left: false,
    right: false,
    kill: false,
});
function bounceButton(button) {
    bounceState.value[button] = true;
    setTimeout(() => {
        bounceState.value[button] = false;
    }, 300);
}

const currentIndex = ref(0);
const resetSwipe = ref(false);
const transitionToNext = ref(false);
const swipeLengthX = ref(0);
const isDragging = ref(false);
const startX = ref(0);
const dragDirection = ref(null);
const skullAnimation = ref(false);
const skullAnimationFading = ref(false);
const isNewCardAnimating = ref(false);
const isFakeSwipeAnimating = ref(false);

const page = usePage();
const props = computed(() => page.props);
const songs = ref(
    Object.values(props.value.votableSongs).map((item) => item.song)
);
const votableSongs = ref(Object.values(props.value.votableSongs));

//audio related state
const audioPreviewCache = ref({});
const audioElements = ref({});
const isPlaying = ref(false);
const currentPlayingId = ref(null);
const loadingQueue = ref([]);
const isLoading = ref(false);

const emit = defineEmits(["endVoting"]);

const SWIPE_THRESHOLD = 100;

function startDrag(event) {
    if (
        isFakeSwipeAnimating.value ||
        transitionToNext.value ||
        skullAnimation.value ||
        isNewCardAnimating.value
    ) {
        return;
    }

    isDragging.value = true;
    startX.value = getClientX(event);
    dragDirection.value = null;

    //prevent default to avoid text selection during drag
    if (event.type === "mousedown") {
        event.preventDefault();
    }
}

function onDrag(event) {
    if (!isDragging.value) {
        return;
    }

    const currentX = getClientX(event);
    const deltaX = currentX - startX.value;

    //determine drag direction
    if (deltaX < 0) {
        dragDirection.value = "left";
    } else if (deltaX > 0) {
        dragDirection.value = "right";
    }

    swipeLengthX.value = deltaX;

    //prevent default to avoid scrolling during drag
    if (event.type === "mousemove") {
        event.preventDefault();
    }
}

function endDrag() {
    if (!isDragging.value) {
        return;
    }

    if (Math.abs(swipeLengthX.value) > SWIPE_THRESHOLD) {
        const moveDirection = swipeLengthX.value < 0 ? -1 : 1;
        const targetX = moveDirection * 300;
        swipeLengthX.value = targetX;
        transitionToNext.value = true;

        setTimeout(() => {
            if (dragDirection.value === "left") {
                swipeLeft();
            } else if (dragDirection.value === "right") {
                swipeRight();
            }
            transitionToNext.value = false;
            swipeLengthX.value = 0;
        }, 200);
    } else {
        resetSwipe.value = true;
        swipeLengthX.value = 0;
        setTimeout(() => {
            resetSwipe.value = false;
        }, 300);
    }

    isDragging.value = false;
}

//helper function to get clientX from both mouse and touch events
function getClientX(event) {
    return calculateClientX(event);
}

function fakeSwipe(direction) {
    let progress = 0;
    const duration = 300;
    const maxDistance = 300;
    const multiplier = direction === "left" ? -1 : 1;
    const startTime = performance.now();
    isFakeSwipeAnimating.value = true;

    const animate = (currentTime) => {
        const elapsed = currentTime - startTime;
        progress = Math.min(elapsed / duration, 1);

        //stars fast, eases at the end
        const eased = 1 - Math.pow(1 - progress, 3);

        swipeLengthX.value = multiplier * (maxDistance * eased);

        if (progress < 1) {
            requestAnimationFrame(animate);
        } else {
            transitionToNext.value = true;
            isFakeSwipeAnimating.value = true;
            setTimeout(() => {
                if (direction === "left") swipeLeft();
                else swipeRight();
                swipeLengthX.value = 0;
                transitionToNext.value = false;
                isFakeSwipeAnimating.value = false;
            }, 200);
        }
    };

    requestAnimationFrame(animate);
}

function clickRight() {
    if (
        isFakeSwipeAnimating.value ||
        transitionToNext.value ||
        skullAnimation.value ||
        isNewCardAnimating.value
    ) {
        return;
    }
    bounceButton("right");
    fakeSwipe("right");
}

function clickLeft() {
    if (
        isFakeSwipeAnimating.value ||
        transitionToNext.value ||
        skullAnimation.value ||
        isNewCardAnimating.value
    ) {
        return;
    }
    bounceButton("left");
    fakeSwipe("left");
}

function swipeLeft() {
    router.post(
        route("mix.voting.vote", votableSongs.value[currentIndex.value].id),
        {
            vote_type: "dislike",
        }
    );
    nextSong();
}

function swipeRight() {
    router.post(
        route("mix.voting.vote", votableSongs.value[currentIndex.value].id),
        {
            vote_type: "like",
        }
    );
    nextSong();
}

function kill() {
    router.post(
        route("mix.voting.vote", votableSongs.value[currentIndex.value].id),
        {
            vote_type: "kill",
        }
    );

    skullAnimation.value = true;

    const shakeIntensity = 10;
    const shakeDuration = 50;
    const shakeCount = 5;

    let shakeIteration = 0;
    const shakeInterval = setInterval(() => {
        swipeLengthX.value =
            Math.random() * shakeIntensity * 2 - shakeIntensity;

        shakeIteration++;
        if (shakeIteration >= shakeCount) {
            clearInterval(shakeInterval);

            //start fade out animation after shake
            setTimeout(() => {
                nextSong();
                skullAnimationFading.value = true;

                //reset position and transition to next card
                swipeLengthX.value = 0;
                transitionToNext.value = true;

                setTimeout(() => {
                    transitionToNext.value = false;
                    skullAnimation.value = false;
                    skullAnimationFading.value = false;
                }, 300);
            }, 600);
        }
    }, shakeDuration);
    bounceButton("kill");
}

function nextSong() {
    //if there are more songs, move to the next one
    if (currentIndex.value < songs.value.length - 1) {
        //pause current audio if playing because user swiped
        //meaning that the user made their choice and audio should stop
        if (isPlaying.value && currentPlayingId.value) {
            audioElements.value[currentPlayingId.value]?.pause();
        }

        currentIndex.value++;
        isNewCardAnimating.value = true;

        const currentSong = songs.value[currentIndex.value];
        const nextSong = songs.value[currentIndex.value + 1];

        //check if current song audio is preloaded
        //if not, load it immediately
        if (currentSong && !audioPreviewCache.value[currentSong.spotify_id]) {
            getSongFile(currentSong.spotify_id);
        }

        //check if next song audio is preloaded
        //if not, load it immediately
        if (nextSong && !audioPreviewCache.value[nextSong.spotify_id]) {
            getSongFile(nextSong.spotify_id);
        }

        //reset animation flag after animation completes
        setTimeout(() => {
            isNewCardAnimating.value = false;
        }, 500);
    } else {
        //no more songs = end voting
        emit("endVoting");
    }
}

function cardTransform(index) {
    if (index !== currentIndex.value) return "scale(0.9)";
    if (
        resetSwipe.value ||
        (transitionToNext.value && swipeLengthX.value === 0)
    )
        return "translateX(0px) rotate(0deg)";
    return `translateX(${swipeLengthX.value}px) rotate(${
        swipeLengthX.value / 10
    }deg)`;
}

function cardOpacity(index) {
    if (index !== currentIndex.value) {
        return 1;
    }

    if (skullAnimation.value) {
        return 1;
    }

    return 1 - Math.abs(swipeLengthX.value) / 300;
}

function toggleAudio(trackId) {
    //if we don't have the audio element yet, queue it up
    if (!audioPreviewCache.value[trackId]) {
        getSongFile(trackId);
        return;
    }

    const audioElement = audioElements.value[trackId];
    audioElement.volume = 0.3;

    if (!audioElement) {
        return;
    }

    //if this is the currently playing audio, toggle play/pause
    if (currentPlayingId.value === trackId) {
        if (isPlaying.value) {
            audioElement.pause();
            isPlaying.value = false;
        } else {
            audioElement.play();
            isPlaying.value = true;
        }
    } else {
        //if another audio is playing, pause it
        if (
            currentPlayingId.value &&
            audioElements.value[currentPlayingId.value]
        ) {
            audioElements.value[currentPlayingId.value].pause();
        }

        //play the new audio
        audioElement.play();
        currentPlayingId.value = trackId;
        isPlaying.value = true;
    }
}

function handlePlay(trackId) {
    //update state when an audio starts playing
    currentPlayingId.value = trackId;
    isPlaying.value = true;
}

async function getSongFile(trackId) {
    //if already in cache, don't fetch again
    if (audioPreviewCache.value[trackId]) {
        return;
    }

    //check if track is already in the loading queue
    if (loadingQueue.value.includes(trackId)) {
        return;
    }

    //add track to the loading queue
    loadingQueue.value.push(trackId);

    //if another load is in progress, just queue this one
    if (isLoading.value) {
        return;
    }

    //if nothing is currently loading, start the loading process
    processLoadingQueue();
}

async function processLoadingQueue() {
    //if queue is empty, we're done
    if (loadingQueue.value.length === 0) {
        isLoading.value = false;
        return;
    }

    //set loading flag to start loading process of track
    isLoading.value = true;

    //get the next track ID to load
    const trackId = loadingQueue.value.shift();

    try {
        const response = await axios.post(route("api.spotify.track-preview"), {
            track_id: trackId,
        });

        //store preview URL in cache
        if (response.data && response.data.preview_url) {
            audioPreviewCache.value[trackId] = response.data.preview_url;
        }
    } catch (error) {
        console.error(`Error loading preview for track ${trackId}:`, error);
    } finally {
        //process next item in queue, this is done recursively.
        processLoadingQueue();
    }
}

onMounted(() => {
    //initialize audio loading -- preload first song
    if (songs.value.length > 0) {
        const currentSong = songs.value[currentIndex.value];
        getSongFile(currentSong.spotify_id);

        //queue up next song if available after 1 second
        if (songs.value.length > 1) {
            setTimeout(() => {
                getSongFile(songs.value[1].spotify_id);
            }, 1000);
        }

        //queue remaining songs with a delay of 2 seconds
        if (songs.value.length > 2) {
            setTimeout(() => {
                for (let i = 2; i < songs.value.length; i++) {
                    getSongFile(songs.value[i].spotify_id);
                }
            }, 2000);
        }
    }
});
</script>
