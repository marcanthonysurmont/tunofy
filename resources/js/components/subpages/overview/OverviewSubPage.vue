<template>
    <PlaylistHeader />
    <PlaylistContent />
</template>

<script setup>
import PlaylistHeader from "@/components/subpages/overview/PlaylistHeader.vue";
import PlaylistContent from "@/components/subpages/overview/PlaylistContent.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
</script>
