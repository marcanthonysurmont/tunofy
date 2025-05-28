<template>
    <div class="hidden">
        <audio
            v-for="(preview, trackId) in audioPreviewCache"
            :key="trackId"
            :ref="
                (el) => {
                    if (el) audioElements[trackId] = el;
                }
            "
            :src="preview"
            preload="auto"
            @play="emits('handle-play', trackId)"
            @pause="emits('pause')"
            @ended="emits('ended')"
        ></audio>
    </div>
</template>

<script setup>
const props = defineProps({
    audioPreviewCache: Object,
    audioElements: Object,
});

const emits = defineEmits(["handle-play", "pause", "ended"]);
</script>
