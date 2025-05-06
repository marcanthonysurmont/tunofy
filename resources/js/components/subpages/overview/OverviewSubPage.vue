<template>
    <PlaylistHeader />
    <PlaylistContent />
    <!-- <MixPlayback :mix="mix" /> -->
</template>

<script setup>
import PlaylistHeader from "@/components/subpages/overview/PlaylistHeader.vue";
import PlaylistContent from "@/components/subpages/overview/PlaylistContent.vue";
import MixPlayback from "@/components/MixPlayback.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const mix = computed(() => {
    return page.props.mix || null;
});
</script>
