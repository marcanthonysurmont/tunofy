<template>
    <div class="flex flex-row gap-4 w-full justify-between">
        <img :src="imageUrl" class="size-13 object-cover flex-shrink-0" />
        <div
            class="flex flex-col gap-2 justify-between min-w-0 flex-1 overflow-hidden"
        >
            <h2
                class="text-2xl whitespace-nowrap overflow-hidden text-ellipsis"
                style="text-overflow: ellipsis"
            >
                {{ mix.name.trim() }}
            </h2>
            <span class="text-zinc-400 text-sm">Playlist • 5 songs</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    mix: {
        type: Object,
        required: true,
    },
});

const imageUrl = computed(() =>
    props.mix.avatar === null
        ? "images/default-avatar.jpg"
        : "storage/" + props.mix.avatar
);
</script>
